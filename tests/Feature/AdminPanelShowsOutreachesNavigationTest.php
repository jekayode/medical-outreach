<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelShowsOutreachesNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_includes_link_to_outreaches(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)->get('/admin');

        $response->assertOk();
        $response->assertSee('/admin/outreaches', false);
    }
}
