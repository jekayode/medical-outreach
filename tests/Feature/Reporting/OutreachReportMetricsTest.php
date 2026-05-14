<?php

namespace Tests\Feature\Reporting;

use App\Enums\BeneficiarySource;
use App\Enums\DispensedStatus;
use App\Enums\Gender;
use App\Enums\HivStatus;
use App\Enums\InterventionStatus;
use App\Enums\InterventionType;
use App\Enums\LabOrderStatus;
use App\Enums\OutreachStatus;
use App\Enums\VisitStage;
use App\Enums\VisitStatus;
use App\Models\Beneficiary;
use App\Models\Consultation;
use App\Models\Intervention;
use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\Outreach;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\User;
use App\Models\Visit;
use App\Models\Vitals;
use App\Services\Reporting\OutreachReportMetrics;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutreachReportMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_headlines_count_visits_interventions_dispensed_drugs_and_lab_results(): void
    {
        $this->seed(RoleSeeder::class);

        $doctor = User::factory()->create();
        $doctor->assignRole('doctor');

        $nurse = User::factory()->create();
        $nurse->assignRole('nurse');

        $outreach = Outreach::query()->create([
            'name' => 'Metrics Outreach',
            'location' => 'Hall A',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'code_prefix' => 'MRX',
            'status' => OutreachStatus::Active,
            'next_check_in_sequence' => 0,
        ]);

        $beneficiary = Beneficiary::query()->create([
            'full_name' => 'Report Patient',
            'gender' => Gender::Male,
            'date_of_birth' => '2010-01-15',
            'phone' => '08000001111',
            'email' => null,
            'residential_address' => '1 Street',
            'source' => BeneficiarySource::WalkIn,
            'imported_at' => null,
            'created_by_user_id' => null,
            'medical_consent' => true,
        ]);

        $visit = Visit::query()->create([
            'beneficiary_id' => $beneficiary->getKey(),
            'outreach_id' => $outreach->getKey(),
            'check_in_code' => 'MRX-0001',
            'checked_in_at' => now()->setTime(10, 30),
            'checked_in_by_user_id' => $nurse->getKey(),
            'current_stage' => VisitStage::Completed,
            'status' => VisitStatus::Completed,
            'completed_at' => now(),
        ]);

        Vitals::query()->create([
            'visit_id' => $visit->getKey(),
            'taken_by_user_id' => $nurse->getKey(),
            'pulse' => 70,
            'temperature' => 36.5,
            'weight_kg' => 40,
            'height_cm' => 150,
            'bmi' => 17.8,
            'blood_pressure_systolic' => 118,
            'blood_pressure_diastolic' => 76,
            'blood_glucose' => null,
            'hiv_status' => HivStatus::Negative,
            'notes' => null,
            'taken_at' => now(),
        ]);

        $intervention = Intervention::query()->create([
            'visit_id' => $visit->getKey(),
            'type' => InterventionType::GeneralConsultation,
            'status' => InterventionStatus::Completed,
            'completed_at' => now(),
        ]);

        $consultation = Consultation::query()->create([
            'intervention_id' => $intervention->getKey(),
            'doctor_user_id' => $doctor->getKey(),
            'chief_complaint' => 'Cough',
            'observations' => null,
            'diagnosis' => 'Upper respiratory infection',
            'next_action' => null,
            'notes' => null,
        ]);

        $labOrder = LabOrder::query()->create([
            'consultation_id' => $consultation->getKey(),
            'ordered_by_user_id' => $doctor->getKey(),
            'status' => LabOrderStatus::Completed,
        ]);

        LabOrderItem::query()->create([
            'lab_order_id' => $labOrder->getKey(),
            'test_name' => 'Malaria RDT',
            'notes' => null,
            'result' => 'Negative',
            'result_recorded_by_user_id' => $doctor->getKey(),
            'result_recorded_at' => now(),
        ]);

        $prescription = Prescription::query()->create([
            'intervention_id' => $intervention->getKey(),
            'prescribed_by_user_id' => $doctor->getKey(),
            'notes' => null,
        ]);

        PrescriptionItem::query()->create([
            'prescription_id' => $prescription->getKey(),
            'drug_name' => 'Paracetamol',
            'dosage' => '500mg',
            'frequency' => 'TDS',
            'duration' => '3d',
            'quantity' => 9,
            'availability' => null,
            'dispensed_status' => DispensedStatus::Dispensed,
            'dispensed_by_user_id' => $doctor->getKey(),
            'dispensed_at' => now(),
            'notes' => null,
        ]);

        $metrics = app(OutreachReportMetrics::class);
        $scoped = $metrics->headlines($outreach->getKey());

        $this->assertSame(1, $scoped['beneficiaries_served']);
        $this->assertSame(1, $scoped['interventions_delivered']);
        $this->assertSame(1, $scoped['drugs_dispensed']);
        $this->assertSame(1, $scoped['lab_tests_completed']);

        $global = $metrics->headlines(null);
        $this->assertSame(1, $global['beneficiaries_served']);
    }

    public function test_interventions_delivered_includes_awaiting_counselling_lines(): void
    {
        $this->seed(RoleSeeder::class);

        $nurse = User::factory()->create();
        $nurse->assignRole('nurse');

        $outreach = Outreach::query()->create([
            'name' => 'Metrics Outreach 2',
            'location' => 'Hall B',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'code_prefix' => 'MR2',
            'status' => OutreachStatus::Active,
            'next_check_in_sequence' => 0,
        ]);

        $beneficiary = Beneficiary::query()->create([
            'full_name' => 'Eye Only Patient',
            'gender' => Gender::Female,
            'date_of_birth' => '2005-03-20',
            'phone' => '08000002222',
            'email' => null,
            'residential_address' => '2 Street',
            'source' => BeneficiarySource::WalkIn,
            'imported_at' => null,
            'created_by_user_id' => null,
            'medical_consent' => true,
        ]);

        $visit = Visit::query()->create([
            'beneficiary_id' => $beneficiary->getKey(),
            'outreach_id' => $outreach->getKey(),
            'check_in_code' => 'MR2-0001',
            'checked_in_at' => now(),
            'checked_in_by_user_id' => $nurse->getKey(),
            'current_stage' => VisitStage::InProgress,
            'status' => VisitStatus::Open,
        ]);

        Intervention::query()->create([
            'visit_id' => $visit->getKey(),
            'type' => InterventionType::EyeCare,
            'status' => InterventionStatus::AwaitingCounselling,
        ]);

        $metrics = app(OutreachReportMetrics::class);

        $this->assertSame(1, $metrics->headlines($outreach->getKey())['interventions_delivered']);
    }
}
