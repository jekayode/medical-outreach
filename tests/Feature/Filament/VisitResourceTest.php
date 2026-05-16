<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Visits\VisitResource;
use App\Models\User;
use Database\Factories\VisitFactory;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitResourceTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    public function test_admin_can_list_visits(): void
    {
        $admin = $this->adminUser();
        VisitFactory::new()->count(3)->create();

        $this->actingAs($admin)
            ->get(VisitResource::getUrl('index'))
            ->assertOk();
    }

    public function test_admin_can_view_a_visit(): void
    {
        $admin = $this->adminUser();
        $visit = VisitFactory::new()->create();

        $this->actingAs($admin)
            ->get(VisitResource::getUrl('view', ['record' => $visit]))
            ->assertOk();
    }

    public function test_non_admin_cannot_access_visits_list(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('nurse');

        $this->actingAs($user)
            ->get(VisitResource::getUrl('index'))
            ->assertForbidden();
    }
}
