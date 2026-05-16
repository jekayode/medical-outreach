<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Imports\ImportResource;
use App\Models\Import;
use App\Models\User;
use Database\Factories\OutreachFactory;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportResourceTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    public function test_admin_can_list_imports(): void
    {
        $admin = $this->adminUser();

        $outreach = OutreachFactory::new()->create();
        Import::query()->create([
            'outreach_id' => $outreach->getKey(),
            'imported_by_user_id' => $admin->getKey(),
            'filename' => 'test.csv',
            'total_rows' => 10,
            'successful_rows' => 9,
            'failed_rows' => 1,
        ]);

        $this->actingAs($admin)
            ->get(ImportResource::getUrl('index'))
            ->assertOk();
    }

    public function test_non_admin_cannot_access_imports_list(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('check_in');

        $this->actingAs($user)
            ->get(ImportResource::getUrl('index'))
            ->assertForbidden();
    }
}
