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
use App\Livewire\Stations\Doctor;
use App\Models\Beneficiary;
use App\Models\Consultation;
use App\Models\Intervention;
use App\Models\Outreach;
use App\Models\User;
use App\Models\Visit;
use App\Models\Vitals;
use App\Services\DoctorConsultationService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class DoctorStationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{outreach: Outreach, visit: Visit, doctor: User, intervention: Intervention}
     */
    private function outreachVisitDoctorAndIntervention(): array
    {
        $this->seed(RoleSeeder::class);
        $doctor = User::factory()->create();
        $doctor->assignRole('doctor');

        $nurse = User::factory()->create();
        $nurse->assignRole('nurse');

        $outreach = Outreach::query()->create([
            'name' => 'Doctor Test Outreach',
            'location' => 'Hall',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'code_prefix' => 'DTX',
            'status' => OutreachStatus::Active,
            'next_check_in_sequence' => 0,
        ]);

        $beneficiary = Beneficiary::query()->create([
            'full_name' => 'Doctor Queue Patient',
            'gender' => Gender::Female,
            'date_of_birth' => '1992-06-01',
            'phone' => '08011112222',
            'email' => null,
            'residential_address' => '2 Lane',
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
            'blood_pressure_systolic' => 120,
            'blood_pressure_diastolic' => 80,
            'pulse' => 70,
            'temperature' => 36.5,
            'weight_kg' => 60,
            'height_cm' => 165,
            'bmi' => 22.0,
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

        return ['outreach' => $outreach, 'visit' => $visit, 'doctor' => $doctor, 'intervention' => $intervention];
    }

    public function test_doctor_consultation_service_pharmacy_creates_prescription_and_advances_status(): void
    {
        ['outreach' => $outreach, 'visit' => $visit, 'doctor' => $doctor, 'intervention' => $intervention] = $this->outreachVisitDoctorAndIntervention();

        app(DoctorConsultationService::class)->save(
            $intervention,
            $doctor,
            $outreach,
            [
                'chief_complaint' => 'Cough',
                'observations' => 'Clear lungs',
                'diagnosis' => 'URI',
                'notes' => null,
            ],
            ConsultationNextAction::Pharmacy,
            [],
            [
                ['drug_name' => 'Paracetamol', 'dosage' => '500mg', 'frequency' => 'TID', 'duration' => '5d', 'quantity' => 15],
            ],
        );

        $this->assertDatabaseHas('consultations', [
            'intervention_id' => $intervention->getKey(),
            'chief_complaint' => 'Cough',
        ]);

        $this->assertDatabaseHas('prescriptions', [
            'intervention_id' => $intervention->getKey(),
            'prescribed_by_user_id' => $doctor->getKey(),
        ]);

        $this->assertDatabaseHas('prescription_items', [
            'drug_name' => 'Paracetamol',
            'quantity' => 15,
        ]);

        $this->assertSame(InterventionStatus::AwaitingPharmacy, $intervention->fresh()->status);
        $this->assertSame(VisitStage::InProgress, $visit->fresh()->current_stage);
    }

    public function test_doctor_consultation_service_lab_creates_order_and_items(): void
    {
        ['outreach' => $outreach, 'doctor' => $doctor, 'intervention' => $intervention] = $this->outreachVisitDoctorAndIntervention();

        app(DoctorConsultationService::class)->save(
            $intervention,
            $doctor,
            $outreach,
            [
                'chief_complaint' => 'Fatigue',
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

        $consultation = Consultation::query()->where('intervention_id', $intervention->getKey())->first();
        $this->assertNotNull($consultation);

        $this->assertDatabaseHas('lab_orders', [
            'consultation_id' => $consultation->getKey(),
            'ordered_by_user_id' => $doctor->getKey(),
            'status' => LabOrderStatus::Pending->value,
        ]);

        $this->assertDatabaseHas('lab_order_items', [
            'test_name' => 'Malaria RDT',
        ]);

        $this->assertSame(InterventionStatus::AwaitingLab, $intervention->fresh()->status);
    }

    public function test_doctor_consultation_service_rejects_lab_when_returned_for_review(): void
    {
        ['outreach' => $outreach, 'doctor' => $doctor, 'intervention' => $intervention] = $this->outreachVisitDoctorAndIntervention();

        Consultation::query()->create([
            'intervention_id' => $intervention->getKey(),
            'doctor_user_id' => $doctor->getKey(),
            'chief_complaint' => 'Prior',
            'observations' => null,
            'diagnosis' => null,
            'next_action' => ConsultationNextAction::Lab,
            'notes' => null,
        ]);

        $intervention->update([
            'status' => InterventionStatus::ConsultationReview,
        ]);

        $this->expectException(ValidationException::class);

        app(DoctorConsultationService::class)->save(
            $intervention->fresh(),
            $doctor,
            $outreach,
            [
                'chief_complaint' => 'Follow-up',
                'observations' => null,
                'diagnosis' => null,
                'notes' => null,
            ],
            ConsultationNextAction::Lab,
            [['test_name' => 'Repeat', 'notes' => null]],
            [],
        );
    }

    public function test_doctor_livewire_save_with_prescription(): void
    {
        ['visit' => $visit, 'doctor' => $doctor, 'intervention' => $intervention] = $this->outreachVisitDoctorAndIntervention();

        Livewire::actingAs($doctor)
            ->test(Doctor::class)
            ->set('selectedVisitId', $visit->getKey())
            ->set('selectedInterventionId', $intervention->getKey())
            ->set('consultationForm.chief_complaint', 'Fever')
            ->set('nextAction', ConsultationNextAction::Pharmacy->value)
            ->set('prescriptionItems', [
                ['drug_name' => 'Amoxicillin', 'dosage' => '500mg', 'frequency' => 'BD', 'duration' => '7d', 'quantity' => '14'],
            ])
            ->call('saveConsultation')
            ->assertHasNoErrors();

        $this->assertSame(InterventionStatus::AwaitingPharmacy, $intervention->fresh()->status);
    }

    public function test_partner_medication_suggestions_config_lists_partner_drugs(): void
    {
        $labels = config('partner_medication_suggestions.labels');

        $this->assertIsArray($labels);
        $this->assertNotEmpty($labels);
        $this->assertContains('Nifedipine 20mg - 100tabs', $labels);
        $this->assertContains('Metronidazole 200mg Tablet (Metrone-200)', $labels);
    }

    public function test_doctor_pharmacy_form_renders_medication_datalist(): void
    {
        ['visit' => $visit, 'doctor' => $doctor, 'intervention' => $intervention] = $this->outreachVisitDoctorAndIntervention();

        $html = Livewire::actingAs($doctor)
            ->test(Doctor::class)
            ->set('selectedVisitId', $visit->getKey())
            ->set('selectedInterventionId', $intervention->getKey())
            ->set('nextAction', ConsultationNextAction::Pharmacy->value)
            ->html();

        $this->assertStringContainsString('id="partner-medication-suggestions"', $html);
        $this->assertStringContainsString('list="partner-medication-suggestions"', $html);
        $this->assertStringContainsString('Nifedipine 20mg - 100tabs', $html);
    }

    public function test_doctor_done_tab_shows_routed_patient_name(): void
    {
        ['visit' => $visit, 'doctor' => $doctor, 'intervention' => $intervention] = $this->outreachVisitDoctorAndIntervention();

        app(DoctorConsultationService::class)->save(
            $intervention,
            $doctor,
            $visit->outreach,
            [
                'chief_complaint' => 'Headache',
                'observations' => null,
                'diagnosis' => null,
                'notes' => null,
            ],
            ConsultationNextAction::Pharmacy,
            [],
            [
                ['drug_name' => 'Ibuprofen', 'dosage' => '200mg', 'frequency' => 'PRN', 'duration' => '3d', 'quantity' => 10],
            ],
        );

        Livewire::actingAs($doctor)
            ->test(Doctor::class)
            ->set('queueTab', 'done')
            ->assertSee('Doctor Queue Patient')
            ->assertSee('DTX-0001');
    }
}
