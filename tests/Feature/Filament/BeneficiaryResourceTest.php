<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Beneficiaries\BeneficiaryResource;
use App\Models\User;
use Database\Factories\BeneficiaryFactory;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BeneficiaryResourceTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    public function test_admin_can_list_beneficiaries(): void
    {
        $admin = $this->adminUser();
        BeneficiaryFactory::new()->count(3)->create();

        $this->actingAs($admin)
            ->get(BeneficiaryResource::getUrl('index'))
            ->assertOk();
    }

    public function test_admin_can_access_create_beneficiary_page(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->get(BeneficiaryResource::getUrl('create'))
            ->assertOk();
    }

    public function test_admin_can_access_edit_beneficiary_page(): void
    {
        $admin = $this->adminUser();
        $beneficiary = BeneficiaryFactory::new()->create();

        $this->actingAs($admin)
            ->get(BeneficiaryResource::getUrl('edit', ['record' => $beneficiary]))
            ->assertOk();
    }

    public function test_non_admin_cannot_access_beneficiary_list(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('nurse');

        $this->actingAs($user)
            ->get(BeneficiaryResource::getUrl('index'))
            ->assertForbidden();
    }

    public function test_soft_deleted_beneficiary_is_still_accessible_for_editing(): void
    {
        $admin = $this->adminUser();
        $beneficiary = BeneficiaryFactory::new()->create();
        $beneficiary->delete();

        $this->actingAs($admin)
            ->get(BeneficiaryResource::getUrl('edit', ['record' => $beneficiary]))
            ->assertOk();
    }
}
