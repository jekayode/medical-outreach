<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    public function test_admin_can_list_users(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->get(UserResource::getUrl('index'))
            ->assertOk();
    }

    public function test_admin_can_access_create_user_page(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->get(UserResource::getUrl('create'))
            ->assertOk();
    }

    public function test_admin_can_access_edit_user_page(): void
    {
        $admin = $this->adminUser();
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->get(UserResource::getUrl('edit', ['record' => $target]))
            ->assertOk();
    }

    public function test_non_admin_cannot_access_user_list(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('doctor');

        $this->actingAs($user)
            ->get(UserResource::getUrl('index'))
            ->assertForbidden();
    }
}
