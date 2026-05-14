<?php

namespace Tests\Feature\Stations;

use App\Enums\AvailabilityStatus;
use App\Enums\BeneficiarySource;
use App\Enums\ConsultationNextAction;
use App\Enums\DispensedStatus;
use App\Enums\Gender;
use App\Enums\InterventionStatus;
use App\Enums\InterventionType;
use App\Enums\OutreachStatus;
use App\Enums\VisitStage;
use App\Enums\VisitStatus;
use App\Livewire\Stations\Pharmacy;
use App\Models\Beneficiary;
use App\Models\Intervention;
use App\Models\Outreach;
use App\Models\PrescriptionItem;
use App\Models\User;
use App\Models\Visit;
use App\Models\Vitals;
use App\Services\DoctorConsultationService;
use App\Services\PharmacyDispenseService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PharmacyStationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{outreach: Outreach, visit: Visit, pharmacist: User, intervention: Intervention}
     */
    private function outreachVisitPharmacistAndAwaitingPharmacyIntervention(): array
    {
        $this->seed(RoleSeeder::class);
        $doctor = User::factory()->create();
        $doctor->assignRole('doctor');

        $pharmacist = User::factory()->create();
        $pharmacist->assignRole('pharmacist');

        $nurse = User::factory()->create();
        $nurse->assignRole('nurse');

        $outreach = Outreach::query()->create([
            'name' => 'Pharmacy Test Outreach',
            'location' => 'Hall',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'code_prefix' => 'PTX',
            'status' => OutreachStatus::Active,
            'next_check_in_sequence' => 0,
        ]);

        $beneficiary = Beneficiary::query()->create([
            'full_name' => 'Pharmacy Queue Patient',
            'gender' => Gender::Female,
            'date_of_birth' => '1990-04-12',
            'phone' => '08044445555',
            'email' => null,
            'residential_address' => '4 Lane',
            'source' => BeneficiarySource::WalkIn,
            'imported_at' => null,
            'created_by_user_id' => null,
            'medical_consent' => true,
        ]);

        $visit = Visit::query()->create([
            'beneficiary_id' => $beneficiary->getKey(),
            'outreach_id' => $outreach->getKey(),
            'check_in_code' => 'PTX-0001',
            'checked_in_at' => now(),
            'checked_in_by_user_id' => $nurse->getKey(),
            'current_stage' => VisitStage::VitalsDone,
            'status' => VisitStatus::Open,
        ]);

        Vitals::query()->create([
            'visit_id' => $visit->getKey(),
            'taken_by_user_id' => $nurse->getKey(),
            'blood_pressure_systolic' => 110,
            'blood_pressure_diastolic' => 70,
            'pulse' => 65,
            'temperature' => 36.2,
            'weight_kg' => 55,
            'height_cm' => 160,
            'bmi' => 21.5,
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
                'chief_complaint' => 'Pain',
                'observations' => null,
                'diagnosis' => null,
                'notes' => null,
            ],
            ConsultationNextAction::Pharmacy,
            [],
            [
                ['drug_name' => 'Paracetamol', 'dosage' => '500mg', 'frequency' => 'TID', 'duration' => '3d', 'quantity' => 9],
            ],
        );

        $intervention = $intervention->fresh();

        return ['outreach' => $outreach, 'visit' => $visit, 'pharmacist' => $pharmacist, 'intervention' => $intervention];
    }

    public function test_pharmacy_dispense_service_updates_items_and_advances_intervention(): void
    {
        ['outreach' => $outreach, 'visit' => $visit, 'pharmacist' => $pharmacist, 'intervention' => $intervention] = $this->outreachVisitPharmacistAndAwaitingPharmacyIntervention();

        $this->assertSame(InterventionStatus::AwaitingPharmacy, $intervention->status);

        $item = PrescriptionItem::query()->first();
        $this->assertNotNull($item);

        app(PharmacyDispenseService::class)->record($intervention, $pharmacist, $outreach, [
            $item->getKey() => [
                'availability' => AvailabilityStatus::Available->value,
                'dispensed_status' => DispensedStatus::Dispensed->value,
            ],
        ]);

        $item = $item->fresh();
        $this->assertSame(AvailabilityStatus::Available, $item->availability);
        $this->assertSame(DispensedStatus::Dispensed, $item->dispensed_status);
        $this->assertSame($pharmacist->getKey(), $item->dispensed_by_user_id);
        $this->assertNotNull($item->dispensed_at);
        $this->assertSame(InterventionStatus::Completed, $intervention->fresh()->status);
        $this->assertNotNull($intervention->fresh()->completed_at);
        $this->assertSame(VisitStatus::Completed, $visit->fresh()->status);
    }

    public function test_pharmacy_livewire_save_dispense(): void
    {
        ['outreach' => $outreach, 'visit' => $visit, 'pharmacist' => $pharmacist, 'intervention' => $intervention] = $this->outreachVisitPharmacistAndAwaitingPharmacyIntervention();

        $item = PrescriptionItem::query()->first();
        $this->assertNotNull($item);

        Livewire::actingAs($pharmacist)
            ->test(Pharmacy::class)
            ->set('selectedVisitId', $visit->getKey())
            ->set('selectedInterventionId', $intervention->getKey())
            ->set('itemDispense', [
                $item->getKey() => [
                    'availability' => AvailabilityStatus::Partial->value,
                    'dispensed_status' => DispensedStatus::Pending->value,
                ],
            ])
            ->call('saveDispense')
            ->assertHasNoErrors();

        $this->assertSame(AvailabilityStatus::Partial, $item->fresh()->availability);
        $this->assertSame(InterventionStatus::Completed, $intervention->fresh()->status);
    }

    public function test_visit_stays_open_when_another_intervention_on_visit_is_not_completed(): void
    {
        ['outreach' => $outreach, 'visit' => $visit, 'pharmacist' => $pharmacist, 'intervention' => $intervention] = $this->outreachVisitPharmacistAndAwaitingPharmacyIntervention();

        Intervention::query()->create([
            'visit_id' => $visit->getKey(),
            'type' => InterventionType::EyeCare,
            'status' => InterventionStatus::Pending,
        ]);

        $item = PrescriptionItem::query()->first();
        $this->assertNotNull($item);

        app(PharmacyDispenseService::class)->record($intervention, $pharmacist, $outreach, [
            $item->getKey() => [
                'availability' => AvailabilityStatus::Available->value,
                'dispensed_status' => DispensedStatus::Dispensed->value,
            ],
        ]);

        $this->assertSame(InterventionStatus::Completed, $intervention->fresh()->status);
        $this->assertSame(VisitStatus::Open, $visit->fresh()->status);
    }
}
