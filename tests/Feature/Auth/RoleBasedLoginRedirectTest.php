<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class RoleBasedLoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_login_redirects_admin_to_filament_dashboard(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('filament.admin.pages.dashboard', absolute: false));
    }

    public function test_login_redirects_nurse_to_vitals_station(): void
    {
        $user = User::factory()->create();
        $user->assignRole('nurse');

        Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('stations.vitals', absolute: false));
    }

    public function test_login_redirects_check_in_staff_to_check_in_station(): void
    {
        $user = User::factory()->create();
        $user->assignRole('check_in');

        Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('stations.check-in', absolute: false));
    }

    public function test_nurse_cannot_access_check_in_station(): void
    {
        $user = User::factory()->create();
        $user->assignRole('nurse');

        $this->actingAs($user);

        $this->get(route('stations.check-in'))->assertForbidden();
    }

    public function test_inactive_user_cannot_authenticate(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password')
            ->call('login')
            ->assertHasErrors('form.email');

        $this->assertGuest();
    }
}
