<?php

namespace Tests\Feature\Stations;

use App\Enums\BeneficiarySource;
use App\Enums\Gender;
use App\Enums\InterventionStatus;
use App\Enums\InterventionType;
use App\Enums\OutreachStatus;
use App\Enums\VisitStage;
use App\Enums\VisitStatus;
use App\Livewire\Stations\Vitals;
use App\Models\Beneficiary;
use App\Models\Outreach;
use App\Models\User;
use App\Models\Visit;
use App\Services\VitalsRecordingService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VitalsStationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{outreach: Outreach, visit: Visit, nurse: User}
     */
    private function outreachVisitAndNurse(): array
    {
        $this->seed(RoleSeeder::class);
        $nurse = User::factory()->create();
        $nurse->assignRole('nurse');

        $outreach = Outreach::query()->create([
            'name' => 'Vitals Test Outreach',
            'location' => 'Hall',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'code_prefix' => 'VTX',
            'status' => OutreachStatus::Active,
            'next_check_in_sequence' => 0,
        ]);

        $beneficiary = Beneficiary::query()->create([
            'full_name' => 'Queue Patient',
            'gender' => Gender::Male,
            'date_of_birth' => '1988-03-20',
            'phone' => '08022223333',
            'email' => null,
            'residential_address' => '1 Lane',
            'source' => BeneficiarySource::WalkIn,
            'imported_at' => null,
            'created_by_user_id' => null,
            'medical_consent' => true,
        ]);

        $visit = Visit::query()->create([
            'beneficiary_id' => $beneficiary->getKey(),
            'outreach_id' => $outreach->getKey(),
            'check_in_code' => 'VTX-0001',
            'checked_in_at' => now(),
            'checked_in_by_user_id' => $nurse->getKey(),
            'current_stage' => VisitStage::CheckedIn,
            'status' => VisitStatus::Open,
        ]);

        return ['outreach' => $outreach, 'visit' => $visit, 'nurse' => $nurse];
    }

    public function test_vitals_recording_service_persists_vitals_interventions_and_stage(): void
    {
        ['outreach' => $outreach, 'visit' => $visit, 'nurse' => $nurse] = $this->outreachVisitAndNurse();

        $vitals = app(VitalsRecordingService::class)->record(
            $visit,
            $nurse,
            $outreach,
            [
                'pulse' => 72,
                'temperature' => 36.6,
                'weight_kg' => 70,
                'height_cm' => 175,
                'blood_pressure_systolic' => null,
                'blood_pressure_diastolic' => null,
                'blood_glucose' => null,
                'hiv_status' => null,
                'notes' => 'OK',
            ],
            [InterventionType::GeneralConsultation->value, InterventionType::EyeCare->value],
        );

        $this->assertDatabaseHas('vitals', [
            'id' => $vitals->getKey(),
            'visit_id' => $visit->getKey(),
            'pulse' => 72,
        ]);

        $this->assertSame(2, $visit->fresh()->interventions()->count());
        $this->assertTrue(
            $visit->fresh()->interventions->every(fn ($i): bool => $i->status === InterventionStatus::Pending)
        );
        $this->assertSame(VisitStage::VitalsDone, $visit->fresh()->current_stage);
    }

    public function test_vitals_livewire_save_records_vitals(): void
    {
        ['outreach' => $outreach, 'visit' => $visit, 'nurse' => $nurse] = $this->outreachVisitAndNurse();

        Livewire::actingAs($nurse)
            ->test(Vitals::class)
            ->set('selectedVisitId', $visit->getKey())
            ->set('form.pulse', '80')
            ->set('form.temperature', '37.0')
            ->set('form.weight_kg', '68')
            ->set('form.height_cm', '170')
            ->set('interventionSelections', [InterventionType::GeneralConsultation->value])
            ->call('saveVitals')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('vitals', [
            'visit_id' => $visit->getKey(),
            'pulse' => 80,
        ]);
        $this->assertSame(VisitStage::VitalsDone, $visit->fresh()->current_stage);
    }
}
