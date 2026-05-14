<?php

namespace Tests\Feature\Stations;

use App\Enums\BeneficiarySource;
use App\Enums\Gender;
use App\Enums\InterventionStatus;
use App\Enums\InterventionType;
use App\Enums\OutreachStatus;
use App\Enums\VisitStage;
use App\Enums\VisitStatus;
use App\Livewire\Stations\Counselling;
use App\Models\Beneficiary;
use App\Models\CounsellingSession;
use App\Models\Intervention;
use App\Models\Outreach;
use App\Models\User;
use App\Models\Visit;
use App\Services\CounsellingSessionRecordingService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class CounsellingStationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{outreach: Outreach, visit: Visit, counsellor: User, intervention: Intervention}
     */
    private function outreachVisitCounsellorWithAwaitingCounsellingIntervention(): array
    {
        $this->seed(RoleSeeder::class);

        $counsellor = User::factory()->create();
        $counsellor->assignRole('counsellor');

        $nurse = User::factory()->create();
        $nurse->assignRole('nurse');

        $outreach = Outreach::query()->create([
            'name' => 'Counselling Test Outreach',
            'location' => 'Hall C',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'code_prefix' => 'CTX',
            'status' => OutreachStatus::Active,
            'next_check_in_sequence' => 0,
        ]);

        $beneficiary = Beneficiary::query()->create([
            'full_name' => 'Counselling Patient',
            'gender' => Gender::Female,
            'date_of_birth' => '1988-06-01',
            'phone' => '08011112222',
            'email' => null,
            'residential_address' => '45 Lane',
            'source' => BeneficiarySource::WalkIn,
            'imported_at' => null,
            'created_by_user_id' => null,
            'medical_consent' => true,
        ]);

        $visit = Visit::query()->create([
            'beneficiary_id' => $beneficiary->getKey(),
            'outreach_id' => $outreach->getKey(),
            'check_in_code' => 'CTX-0001',
            'checked_in_at' => now(),
            'checked_in_by_user_id' => $nurse->getKey(),
            'current_stage' => VisitStage::InProgress,
            'status' => VisitStatus::Open,
        ]);

        $intervention = Intervention::query()->create([
            'visit_id' => $visit->getKey(),
            'type' => InterventionType::DentalCare,
            'status' => InterventionStatus::AwaitingCounselling,
        ]);

        return ['outreach' => $outreach, 'visit' => $visit, 'counsellor' => $counsellor, 'intervention' => $intervention];
    }

    public function test_counselling_service_records_session_and_completes_visit(): void
    {
        ['outreach' => $outreach, 'visit' => $visit, 'counsellor' => $counsellor, 'intervention' => $intervention] = $this->outreachVisitCounsellorWithAwaitingCounsellingIntervention();

        app(CounsellingSessionRecordingService::class)->record($visit, $counsellor, $outreach, [
            'types' => ['wellness', 'prayer'],
            'notes' => 'Encouraged follow-up',
        ]);

        $this->assertSame(1, CounsellingSession::query()->where('visit_id', $visit->getKey())->count());

        $session = CounsellingSession::query()->where('visit_id', $visit->getKey())->first();
        $this->assertNotNull($session);
        $this->assertSame($counsellor->getKey(), $session->counsellor_user_id);
        $this->assertSame(['wellness', 'prayer'], $session->types);
        $this->assertSame('Encouraged follow-up', $session->notes);

        $this->assertSame(InterventionStatus::Completed, $intervention->fresh()->status);
        $this->assertNotNull($intervention->fresh()->completed_at);

        $visitFresh = $visit->fresh();
        $this->assertSame(VisitStatus::Completed, $visitFresh->status);
        $this->assertSame(VisitStage::Completed, $visitFresh->current_stage);
        $this->assertNotNull($visitFresh->completed_at);
    }

    public function test_counselling_service_rejects_empty_types(): void
    {
        ['outreach' => $outreach, 'visit' => $visit, 'counsellor' => $counsellor] = $this->outreachVisitCounsellorWithAwaitingCounsellingIntervention();

        $this->expectException(ValidationException::class);

        app(CounsellingSessionRecordingService::class)->record($visit, $counsellor, $outreach, [
            'types' => [],
            'notes' => null,
        ]);
    }

    public function test_counselling_service_rejects_when_intervention_not_ready_for_counselling(): void
    {
        ['outreach' => $outreach, 'visit' => $visit, 'counsellor' => $counsellor] = $this->outreachVisitCounsellorWithAwaitingCounsellingIntervention();

        Intervention::query()->create([
            'visit_id' => $visit->getKey(),
            'type' => InterventionType::GeneralConsultation,
            'status' => InterventionStatus::AwaitingPharmacy,
        ]);

        $this->expectException(ValidationException::class);

        app(CounsellingSessionRecordingService::class)->record($visit, $counsellor, $outreach, [
            'types' => ['missions'],
            'notes' => null,
        ]);
    }

    public function test_counselling_livewire_save_session(): void
    {
        ['visit' => $visit, 'counsellor' => $counsellor] = $this->outreachVisitCounsellorWithAwaitingCounsellingIntervention();

        Livewire::actingAs($counsellor)
            ->test(Counselling::class)
            ->set('selectedVisitId', $visit->getKey())
            ->set('sessionFormTypes', ['wellness'])
            ->set('sessionFormNotes', '')
            ->call('saveSession')
            ->assertHasNoErrors();

        $this->assertSame(VisitStatus::Completed, $visit->fresh()->status);
    }

    public function test_counselling_livewire_requires_at_least_one_type(): void
    {
        ['visit' => $visit, 'counsellor' => $counsellor] = $this->outreachVisitCounsellorWithAwaitingCounsellingIntervention();

        Livewire::actingAs($counsellor)
            ->test(Counselling::class)
            ->set('selectedVisitId', $visit->getKey())
            ->set('sessionFormTypes', [])
            ->call('saveSession')
            ->assertHasErrors(['sessionFormTypes']);
    }
}
