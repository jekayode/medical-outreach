<?php

namespace Tests\Feature\Stations;

use App\Enums\BeneficiarySource;
use App\Enums\ConsultationNextAction;
use App\Enums\Gender;
use App\Enums\InterventionStatus;
use App\Enums\InterventionType;
use App\Enums\LabOrderStatus;
use App\Enums\OutreachStatus;
use App\Enums\VisitStage;
use App\Enums\VisitStatus;
use App\Livewire\Stations\Lab;
use App\Models\Beneficiary;
use App\Models\Intervention;
use App\Models\LabOrderItem;
use App\Models\Outreach;
use App\Models\User;
use App\Models\Visit;
use App\Models\Vitals;
use App\Services\DoctorConsultationService;
use App\Services\LabResultRecordingService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LabStationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{outreach: Outreach, visit: Visit, lab: User, intervention: Intervention}
     */
    private function outreachVisitLabAndAwaitingLabIntervention(): array
    {
        $this->seed(RoleSeeder::class);
        $doctor = User::factory()->create();
        $doctor->assignRole('doctor');

        $lab = User::factory()->create();
        $lab->assignRole('lab');

        $nurse = User::factory()->create();
        $nurse->assignRole('nurse');

        $outreach = Outreach::query()->create([
            'name' => 'Lab Test Outreach',
            'location' => 'Hall',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'code_prefix' => 'LTX',
            'status' => OutreachStatus::Active,
            'next_check_in_sequence' => 0,
        ]);

        $beneficiary = Beneficiary::query()->create([
            'full_name' => 'Lab Queue Patient',
            'gender' => Gender::Male,
            'date_of_birth' => '1985-01-10',
            'phone' => '08033334444',
            'email' => null,
            'residential_address' => '3 Lane',
            'source' => BeneficiarySource::WalkIn,
            'imported_at' => null,
            'created_by_user_id' => null,
            'medical_consent' => true,
        ]);

        $visit = Visit::query()->create([
            'beneficiary_id' => $beneficiary->getKey(),
            'outreach_id' => $outreach->getKey(),
            'check_in_code' => 'LTX-0001',
            'checked_in_at' => now(),
            'checked_in_by_user_id' => $nurse->getKey(),
            'current_stage' => VisitStage::VitalsDone,
            'status' => VisitStatus::Open,
        ]);

        Vitals::query()->create([
            'visit_id' => $visit->getKey(),
            'taken_by_user_id' => $nurse->getKey(),
            'blood_pressure_systolic' => 118,
            'blood_pressure_diastolic' => 76,
            'pulse' => 68,
            'temperature' => 36.4,
            'weight_kg' => 72,
            'height_cm' => 178,
            'bmi' => 22.7,
            'blood_glucose' => null,
            'hiv_status' => null,
            'notes' => null,
            'taken_at' => now(),
        ]);

        $intervention = Intervention::query()->create([
            'visit_id' => $visit->getKey(),
            'type' => InterventionType::GeneralConsultation,
            'status' => InterventionStatus::Pending,
        ]);

        app(DoctorConsultationService::class)->save(
            $intervention,
            $doctor,
            $outreach,
            [
                'chief_complaint' => 'Fever',
                'observations' => null,
                'diagnosis' => null,
                'notes' => null,
            ],
            ConsultationNextAction::Lab,
            [
                ['test_name' => 'Malaria RDT', 'notes' => 'STAT'],
            ],
            [],
        );

        $intervention = $intervention->fresh();

        return ['outreach' => $outreach, 'visit' => $visit, 'lab' => $lab, 'intervention' => $intervention];
    }

    public function test_lab_result_service_records_results_and_returns_to_doctor_queue(): void
    {
        ['outreach' => $outreach, 'lab' => $lab, 'intervention' => $intervention] = $this->outreachVisitLabAndAwaitingLabIntervention();

        $this->assertSame(InterventionStatus::AwaitingLab, $intervention->status);

        $item = LabOrderItem::query()->first();
        $this->assertNotNull($item);

        app(LabResultRecordingService::class)->record($intervention, $lab, $outreach, [
            $item->getKey() => 'Negative',
        ]);

        $this->assertSame('Negative', $item->fresh()->result);
        $this->assertSame(InterventionStatus::ConsultationReview, $intervention->fresh()->status);
        $this->assertSame(LabOrderStatus::Completed, $item->labOrder->fresh()->status);
    }

    public function test_lab_livewire_save_results(): void
    {
        ['outreach' => $outreach, 'visit' => $visit, 'lab' => $lab, 'intervention' => $intervention] = $this->outreachVisitLabAndAwaitingLabIntervention();

        $item = LabOrderItem::query()->first();
        $this->assertNotNull($item);

        Livewire::actingAs($lab)
            ->test(Lab::class)
            ->set('selectedVisitId', $visit->getKey())
            ->set('selectedInterventionId', $intervention->getKey())
            ->set('itemResults', [$item->getKey() => 'Positive'])
            ->call('saveResults')
            ->assertHasNoErrors();

        $this->assertSame('Positive', $item->fresh()->result);
        $this->assertSame(InterventionStatus::ConsultationReview, $intervention->fresh()->status);
    }
}
