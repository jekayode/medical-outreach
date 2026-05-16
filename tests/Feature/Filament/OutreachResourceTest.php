<?php

namespace Tests\Feature\Filament;

use App\Enums\OutreachStatus;
use App\Filament\Resources\Outreaches\OutreachResource;
use App\Models\User;
use Database\Factories\OutreachFactory;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OutreachResourceTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    public function test_admin_can_list_outreaches(): void
    {
        $admin = $this->adminUser();
        OutreachFactory::new()->count(3)->create();

        $this->actingAs($admin)
            ->get(OutreachResource::getUrl('index'))
            ->assertOk();
    }

    public function test_admin_can_access_create_outreach_page(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->get(OutreachResource::getUrl('create'))
            ->assertOk();
    }

    public function test_admin_can_access_edit_outreach_page(): void
    {
        $admin = $this->adminUser();
        $outreach = OutreachFactory::new()->create();

        $this->actingAs($admin)
            ->get(OutreachResource::getUrl('edit', ['record' => $outreach]))
            ->assertOk();
    }

    public function test_non_admin_cannot_access_outreach_list(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('nurse');

        $this->actingAs($user)
            ->get(OutreachResource::getUrl('index'))
            ->assertForbidden();
    }

    public function test_active_outreach_guard_prevents_second_active(): void
    {
        OutreachFactory::new()->active()->create();

        $this->expectException(ValidationException::class);

        OutreachFactory::new()->active()->create();
    }

    public function test_second_outreach_can_be_planned_when_one_is_active(): void
    {
        OutreachFactory::new()->active()->create();

        $planned = OutreachFactory::new()->create(['status' => OutreachStatus::Planned]);

        $this->assertDatabaseHas('outreaches', ['id' => $planned->getKey(), 'status' => OutreachStatus::Planned->value]);
    }

    public function test_active_outreach_guard_allows_re_saving_the_same_record(): void
    {
        $outreach = OutreachFactory::new()->active()->create();

        $outreach->update(['name' => 'Updated Name']);

        $this->assertDatabaseHas('outreaches', ['id' => $outreach->getKey(), 'name' => 'Updated Name']);
    }
}
