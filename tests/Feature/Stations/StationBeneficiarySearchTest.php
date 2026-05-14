<?php

namespace Tests\Feature\Stations;

use App\Enums\BeneficiarySource;
use App\Enums\Gender;
use App\Enums\InterventionStatus;
use App\Enums\InterventionType;
use App\Enums\OutreachStatus;
use App\Enums\VisitStage;
use App\Enums\VisitStatus;
use App\Models\Beneficiary;
use App\Models\Intervention;
use App\Models\Outreach;
use App\Models\User;
use App\Models\Visit;
use App\Services\StationBeneficiarySearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StationBeneficiarySearchTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{outreach: Outreach, visit: Visit}
     */
    private function createActiveOutreachWithVisit(): array
    {
        $user = User::factory()->create();

        $outreach = Outreach::query()->create([
            'name' => 'Active Event',
            'location' => 'Hall',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'code_prefix' => 'MOA',
            'status' => OutreachStatus::Active,
            'next_check_in_sequence' => 7,
        ]);

        $beneficiary = Beneficiary::query()->create([
            'full_name' => 'Ann Other',
            'gender' => Gender::Female,
            'date_of_birth' => now()->subYears(40)->toDateString(),
            'phone' => '+155512345678',
            'email' => null,
            'residential_address' => '1 Test Street',
            'source' => BeneficiarySource::WalkIn,
        ]);

        $visit = Visit::query()->create([
            'beneficiary_id' => $beneficiary->getKey(),
            'outreach_id' => $outreach->getKey(),
            'check_in_code' => 'MOA-0007',
            'checked_in_at' => now(),
            'checked_in_by_user_id' => $user->getKey(),
            'current_stage' => VisitStage::CheckedIn,
            'status' => VisitStatus::Open,
        ]);

        Intervention::query()->create([
            'visit_id' => $visit->getKey(),
            'type' => InterventionType::GeneralConsultation,
            'status' => InterventionStatus::Pending,
        ]);

        return ['outreach' => $outreach, 'visit' => $visit];
    }

    public function test_finds_visit_by_short_numeric_code(): void
    {
        ['outreach' => $outreach] = $this->createActiveOutreachWithVisit();

        $svc = app(StationBeneficiarySearch::class);
        $results = $svc->search($outreach, '7');

        $this->assertCount(1, $results);
        $this->assertSame('MOA-0007', $results->first()['check_in_code']);
    }

    public function test_finds_visit_by_full_check_in_code(): void
    {
        ['outreach' => $outreach] = $this->createActiveOutreachWithVisit();

        $svc = app(StationBeneficiarySearch::class);
        $results = $svc->search($outreach, 'moa-7');

        $this->assertCount(1, $results);
    }

    public function test_finds_visit_by_phone_digits(): void
    {
        ['outreach' => $outreach] = $this->createActiveOutreachWithVisit();

        $svc = app(StationBeneficiarySearch::class);
        $results = $svc->search($outreach, '155512345678');

        $this->assertCount(1, $results);
        $this->assertSame('Ann Other', $results->first()['full_name']);
    }

    public function test_finds_visit_by_name(): void
    {
        ['outreach' => $outreach] = $this->createActiveOutreachWithVisit();

        $svc = app(StationBeneficiarySearch::class);
        $results = $svc->search($outreach, 'Ann Oth');

        $this->assertCount(1, $results);
    }

    public function test_returns_empty_for_blank_term(): void
    {
        ['outreach' => $outreach] = $this->createActiveOutreachWithVisit();

        $svc = app(StationBeneficiarySearch::class);

        $this->assertCount(0, $svc->search($outreach, '   '));
    }
}
