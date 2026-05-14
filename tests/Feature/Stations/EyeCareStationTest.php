<?php

namespace Tests\Feature\Stations;

use App\Enums\BeneficiarySource;
use App\Enums\Gender;
use App\Enums\InterventionStatus;
use App\Enums\InterventionType;
use App\Enums\OutreachStatus;
use App\Enums\VisitStage;
use App\Enums\VisitStatus;
use App\Livewire\Stations\EyeCare;
use App\Models\Beneficiary;
use App\Models\EyeExam;
use App\Models\Intervention;
use App\Models\Outreach;
use App\Models\Prescription;
use App\Models\User;
use App\Models\Visit;
use App\Models\Vitals;
use App\Services\EyeExamRecordingService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EyeCareStationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{outreach: Outreach, visit: Visit, eyeTech: User, intervention: Intervention}
     */
    private function outreachVisitEyeTechAndPendingEyeIntervention(): array
    {
        $this->seed(RoleSeeder::class);
        $eyeTech = User::factory()->create();
        $eyeTech->assignRole('eye_care');

        $nurse = User::factory()->create();
        $nurse->assignRole('nurse');

        $outreach = Outreach::query()->create([
            'name' => 'Eye Test Outreach',
            'location' => 'Hall',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'code_prefix' => 'ETX',
            'status' => OutreachStatus::Active,
            'next_check_in_sequence' => 0,
        ]);

        $beneficiary = Beneficiary::query()->create([
            'full_name' => 'Eye Clinic Patient',
            'gender' => Gender::Female,
            'date_of_birth' => '1978-11-02',
            'phone' => '08055556666',
            'email' => null,
            'residential_address' => '5 Lane',
            'source' => BeneficiarySource::WalkIn,
            'imported_at' => null,
            'created_by_user_id' => null,
            'medical_consent' => true,
        ]);

        $visit = Visit::query()->create([
            'beneficiary_id' => $beneficiary->getKey(),
            'outreach_id' => $outreach->getKey(),
            'check_in_code' => 'ETX-0001',
            'checked_in_at' => now(),
            'checked_in_by_user_id' => $nurse->getKey(),
            'current_stage' => VisitStage::VitalsDone,
            'status' => VisitStatus::Open,
        ]);

        Vitals::query()->create([
            'visit_id' => $visit->getKey(),
            'taken_by_user_id' => $nurse->getKey(),
            'pulse' => 70,
            'temperature' => 36.5,
            'weight_kg' => 62,
            'height_cm' => 162,
            'bmi' => 23.6,
            'blood_pressure_systolic' => null,
            'blood_pressure_diastolic' => null,
            'blood_glucose' => null,
            'hiv_status' => null,
            'notes' => null,
            'taken_at' => now(),
        ]);

        $intervention = Intervention::query()->create([
            'visit_id' => $visit->getKey(),
            'type' => InterventionType::EyeCare,
            'status' => InterventionStatus::Pending,
        ]);

        return ['outreach' => $outreach, 'visit' => $visit, 'eyeTech' => $eyeTech, 'intervention' => $intervention];
    }

    public function test_eye_exam_service_without_drops_goes_to_counselling(): void
    {
        ['outreach' => $outreach, 'eyeTech' => $eyeTech, 'intervention' => $intervention] = $this->outreachVisitEyeTechAndPendingEyeIntervention();

        app(EyeExamRecordingService::class)->record($intervention, $eyeTech, $outreach, [
            'visual_acuity_left' => '20/30',
            'visual_acuity_right' => '20/25',
            'findings' => 'Normal',
            'glasses_prescribed' => false,
            'glasses_prescription_details' => '',
            'drops_prescribed' => false,
            'referral_needed' => false,
            'referral_notes' => '',
            'notes' => '',
        ], []);

        $this->assertDatabaseHas('eye_exams', [
            'intervention_id' => $intervention->getKey(),
            'examined_by_user_id' => $eyeTech->getKey(),
        ]);

        $this->assertSame(InterventionStatus::AwaitingCounselling, $intervention->fresh()->status);
        $this->assertSame(0, Prescription::query()->where('intervention_id', $intervention->getKey())->count());
    }

    public function test_eye_exam_service_with_drops_creates_prescription_and_pharmacy_queue(): void
    {
        ['outreach' => $outreach, 'eyeTech' => $eyeTech, 'intervention' => $intervention] = $this->outreachVisitEyeTechAndPendingEyeIntervention();

        app(EyeExamRecordingService::class)->record($intervention, $eyeTech, $outreach, [
            'visual_acuity_left' => '',
            'visual_acuity_right' => '',
            'findings' => 'Dry eye',
            'glasses_prescribed' => false,
            'glasses_prescription_details' => '',
            'drops_prescribed' => true,
            'referral_needed' => false,
            'referral_notes' => '',
            'notes' => '',
        ], [
            'drug_name' => 'Artificial tears',
            'dosage' => '0.5%',
            'frequency' => 'QID',
            'duration' => '14d',
            'quantity' => '2',
        ]);

        $this->assertTrue(EyeExam::query()->where('intervention_id', $intervention->getKey())->value('drops_prescribed'));
        $this->assertSame(1, Prescription::query()->where('intervention_id', $intervention->getKey())->count());
        $this->assertSame(InterventionStatus::AwaitingPharmacy, $intervention->fresh()->status);
    }

    public function test_eye_care_livewire_save_exam(): void
    {
        ['outreach' => $outreach, 'visit' => $visit, 'eyeTech' => $eyeTech, 'intervention' => $intervention] = $this->outreachVisitEyeTechAndPendingEyeIntervention();

        Livewire::actingAs($eyeTech)
            ->test(EyeCare::class)
            ->set('selectedVisitId', $visit->getKey())
            ->set('selectedInterventionId', $intervention->getKey())
            ->set('examForm.findings', 'OK')
            ->set('examForm.glasses_prescribed', false)
            ->set('examForm.drops_prescribed', false)
            ->set('examForm.referral_needed', false)
            ->call('saveExam')
            ->assertHasNoErrors();

        $this->assertSame(InterventionStatus::AwaitingCounselling, $intervention->fresh()->status);
    }
}
