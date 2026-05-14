<?php

namespace Tests\Feature\Stations;

use App\Enums\BeneficiarySource;
use App\Enums\Gender;
use App\Enums\OutreachStatus;
use App\Livewire\Stations\CheckIn;
use App\Models\Beneficiary;
use App\Models\Outreach;
use App\Models\User;
use App\Models\Visit;
use App\Services\CheckInSearchService;
use App\Services\VisitCheckInService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class CheckInStationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Outreach, 1: User}
     */
    private function activeOutreachAndCheckInUser(): array
    {
        $this->seed(RoleSeeder::class);
        $staff = User::factory()->create();
        $staff->assignRole('check_in');
        $outreach = Outreach::query()->create([
            'name' => 'Test Event',
            'location' => 'Hall',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'code_prefix' => 'MOA',
            'status' => OutreachStatus::Active,
            'next_check_in_sequence' => 0,
        ]);

        return [$outreach, $staff];
    }

    private function makeRegisteredBeneficiary(Outreach $outreach, User $createdBy): Beneficiary
    {
        $beneficiary = Beneficiary::query()->create([
            'full_name' => 'Pending Person',
            'gender' => Gender::Female,
            'date_of_birth' => '1990-01-15',
            'phone' => '08011112222',
            'email' => null,
            'residential_address' => '123 Street',
            'source' => BeneficiarySource::GoogleFormImport,
            'imported_at' => now(),
            'created_by_user_id' => $createdBy->getKey(),
            'medical_consent' => true,
        ]);
        $outreach->registeredBeneficiaries()->syncWithoutDetaching([$beneficiary->getKey()]);

        return $beneficiary;
    }

    public function test_visit_check_in_service_allocates_code_and_persists_visit(): void
    {
        [$outreach, $staff] = $this->activeOutreachAndCheckInUser();
        $beneficiary = $this->makeRegisteredBeneficiary($outreach, $staff);

        $visit = app(VisitCheckInService::class)->checkInBeneficiary($outreach, $beneficiary, $staff);

        $this->assertSame('MOA-0001', $visit->check_in_code);
        $this->assertDatabaseHas('visits', [
            'id' => $visit->getKey(),
            'beneficiary_id' => $beneficiary->getKey(),
            'outreach_id' => $outreach->getKey(),
        ]);
    }

    public function test_visit_check_in_service_rejects_second_visit_same_outreach(): void
    {
        [$outreach, $staff] = $this->activeOutreachAndCheckInUser();
        $beneficiary = $this->makeRegisteredBeneficiary($outreach, $staff);
        $svc = app(VisitCheckInService::class);
        $svc->checkInBeneficiary($outreach, $beneficiary, $staff);

        try {
            $svc->checkInBeneficiary($outreach, $beneficiary, $staff);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('beneficiary', $exception->errors());
        }
    }

    public function test_check_in_search_includes_pending_row(): void
    {
        [$outreach, $staff] = $this->activeOutreachAndCheckInUser();
        $this->makeRegisteredBeneficiary($outreach, $staff);

        $rows = app(CheckInSearchService::class)->search($outreach, 'Pending');

        $this->assertTrue($rows->contains(fn (array $row): bool => ($row['kind'] ?? '') === 'pending'));
    }

    public function test_check_in_registry_table_lists_registered_beneficiaries(): void
    {
        [$outreach, $staff] = $this->activeOutreachAndCheckInUser();
        $this->makeRegisteredBeneficiary($outreach, $staff);

        Livewire::actingAs($staff)
            ->test(CheckIn::class)
            ->assertSee(__('Registered guests'))
            ->assertSee('Pending Person');
    }

    public function test_check_in_search_finds_beneficiary_without_outreach_pivot(): void
    {
        [$outreach, $staff] = $this->activeOutreachAndCheckInUser();
        Beneficiary::query()->create([
            'full_name' => 'Ernestina Outreach Guest',
            'gender' => Gender::Female,
            'date_of_birth' => '1992-06-01',
            'phone' => '08099998888',
            'email' => null,
            'residential_address' => '9 Seed Street',
            'source' => BeneficiarySource::WalkIn,
            'imported_at' => null,
            'created_by_user_id' => $staff->getKey(),
            'medical_consent' => true,
        ]);

        $rows = app(CheckInSearchService::class)->search($outreach, 'Ernestina');

        $this->assertTrue($rows->contains(fn (array $row): bool => ($row['kind'] ?? '') === 'pending'
            && str_contains((string) ($row['full_name'] ?? ''), 'Ernestina')));
    }

    public function test_check_in_livewire_check_in_pending_sets_slip(): void
    {
        [$outreach, $staff] = $this->activeOutreachAndCheckInUser();
        $beneficiary = $this->makeRegisteredBeneficiary($outreach, $staff);

        Livewire::actingAs($staff)
            ->test(CheckIn::class)
            ->set('search', 'Pending')
            ->call('performSearch')
            ->call('checkInPending', $beneficiary->getKey())
            ->assertSet('slipVisitId', Visit::query()->where('beneficiary_id', $beneficiary->getKey())->value('id'));
    }
}
