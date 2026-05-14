<?php

namespace Tests\Feature\Stations;

use App\Enums\BeneficiarySource;
use App\Enums\Gender;
use App\Enums\InterventionStatus;
use App\Enums\InterventionType;
use App\Enums\OutreachStatus;
use App\Enums\VisitStage;
use App\Enums\VisitStatus;
use App\Livewire\Stations\DentalCare;
use App\Models\Beneficiary;
use App\Models\DentalExam;
use App\Models\Intervention;
use App\Models\Outreach;
use App\Models\User;
use App\Models\Visit;
use App\Models\Vitals;
use App\Services\DentalExamRecordingService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DentalCareStationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{outreach: Outreach, visit: Visit, dentalStaff: User, intervention: Intervention}
     */
    private function outreachVisitDentalStaffAndPendingDentalIntervention(): array
    {
        $this->seed(RoleSeeder::class);
        $dentalStaff = User::factory()->create();
        $dentalStaff->assignRole('dental_care');

        $nurse = User::factory()->create();
        $nurse->assignRole('nurse');

        $outreach = Outreach::query()->create([
            'name' => 'Dental Test Outreach',
            'location' => 'Hall B',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'code_prefix' => 'DTX',
            'status' => OutreachStatus::Active,
            'next_check_in_sequence' => 0,
        ]);

        $beneficiary = Beneficiary::query()->create([
            'full_name' => 'Dental Clinic Patient',
            'gender' => Gender::Male,
            'date_of_birth' => '1990-04-12',
            'phone' => '08044445555',
            'email' => null,
            'residential_address' => '12 Road',
            'source' => BeneficiarySource::WalkIn,
            'imported_at' => null,
            'created_by_user_id' => null,
            'medical_consent' => true,
        ]);

        $visit = Visit::query()->create([
            'beneficiary_id' => $beneficiary->getKey(),
            'outreach_id' => $outreach->getKey(),
            'check_in_code' => 'DTX-0001',
            'checked_in_at' => now(),
            'checked_in_by_user_id' => $nurse->getKey(),
            'current_stage' => VisitStage::VitalsDone,
            'status' => VisitStatus::Open,
        ]);

        Vitals::query()->create([
            'visit_id' => $visit->getKey(),
            'taken_by_user_id' => $nurse->getKey(),
            'pulse' => 72,
            'temperature' => 36.4,
            'weight_kg' => 78,
            'height_cm' => 178,
            'bmi' => 24.6,
            'blood_pressure_systolic' => null,
            'blood_pressure_diastolic' => null,
            'blood_glucose' => null,
            'hiv_status' => null,
            'notes' => null,
            'taken_at' => now(),
        ]);

        $intervention = Intervention::query()->create([
            'visit_id' => $visit->getKey(),
            'type' => InterventionType::DentalCare,
            'status' => InterventionStatus::Pending,
        ]);

        return ['outreach' => $outreach, 'visit' => $visit, 'dentalStaff' => $dentalStaff, 'intervention' => $intervention];
    }

    public function test_dental_exam_service_sets_awaiting_counselling_and_persists_exam(): void
    {
        ['outreach' => $outreach, 'dentalStaff' => $dentalStaff, 'intervention' => $intervention] = $this->outreachVisitDentalStaffAndPendingDentalIntervention();

        app(DentalExamRecordingService::class)->record($intervention, $dentalStaff, $outreach, [
            'findings' => 'Caries #14',
            'treatment_performed' => 'Extraction',
            'referral_needed' => false,
            'referral_notes' => '',
            'notes' => 'Follow-up in 1 week',
        ]);

        $this->assertDatabaseHas('dental_exams', [
            'intervention_id' => $intervention->getKey(),
            'examined_by_user_id' => $dentalStaff->getKey(),
            'findings' => 'Caries #14',
        ]);

        $this->assertSame(InterventionStatus::AwaitingCounselling, $intervention->fresh()->status);
        $this->assertSame(1, DentalExam::query()->where('intervention_id', $intervention->getKey())->count());
    }

    public function test_dental_care_livewire_save_exam(): void
    {
        ['outreach' => $outreach, 'visit' => $visit, 'dentalStaff' => $dentalStaff, 'intervention' => $intervention] = $this->outreachVisitDentalStaffAndPendingDentalIntervention();

        Livewire::actingAs($dentalStaff)
            ->test(DentalCare::class)
            ->set('selectedVisitId', $visit->getKey())
            ->set('selectedInterventionId', $intervention->getKey())
            ->set('examForm.findings', 'Healthy gums')
            ->set('examForm.referral_needed', false)
            ->call('saveExam')
            ->assertHasNoErrors();

        $this->assertSame(InterventionStatus::AwaitingCounselling, $intervention->fresh()->status);
    }
}
