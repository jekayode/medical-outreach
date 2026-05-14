<?php

namespace Tests\Feature\Stations;

use App\Enums\BeneficiarySource;
use App\Enums\Gender;
use App\Enums\OutreachStatus;
use App\Enums\VisitStage;
use App\Enums\VisitStatus;
use App\Livewire\Stations\BeneficiaryLookup;
use App\Models\Beneficiary;
use App\Models\Outreach;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BeneficiaryLookupComponentTest extends TestCase
{
    use RefreshDatabase;

    public function test_lookup_populates_results_and_dispatches_selection(): void
    {
        $user = User::factory()->create();

        $outreach = Outreach::query()->create([
            'name' => 'Active Event',
            'location' => 'Hall',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'code_prefix' => 'MOA',
            'status' => OutreachStatus::Active,
            'next_check_in_sequence' => 1,
        ]);

        $beneficiary = Beneficiary::query()->create([
            'full_name' => 'Livewire Patient',
            'gender' => Gender::Male,
            'date_of_birth' => now()->subYears(25)->toDateString(),
            'phone' => '+19990001111',
            'email' => null,
            'residential_address' => '2 Demo Road',
            'source' => BeneficiarySource::WalkIn,
        ]);

        $visit = Visit::query()->create([
            'beneficiary_id' => $beneficiary->getKey(),
            'outreach_id' => $outreach->getKey(),
            'check_in_code' => 'MOA-0001',
            'checked_in_at' => now(),
            'checked_in_by_user_id' => $user->getKey(),
            'current_stage' => VisitStage::CheckedIn,
            'status' => VisitStatus::Open,
        ]);

        Livewire::test(BeneficiaryLookup::class)
            ->set('search', '1')
            ->call('performSearch')
            ->assertSet('results.0.visit_id', $visit->getKey())
            ->call('selectVisit', $visit->getKey())
            ->assertDispatched('visit-selected', visitId: $visit->getKey());
    }
}
