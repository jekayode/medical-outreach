<?php

namespace Tests\Feature;

use App\Models\Beneficiary;
use App\Models\Outreach;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OutreachSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoDatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_outreach_staff_and_imports_usable_registrations(): void
    {
        $this->seed(DatabaseSeeder::class);

        $outreach = Outreach::query()->where('name', OutreachSeeder::NAME)->first();
        $this->assertNotNull($outreach);
        $this->assertSame('LPF', $outreach->code_prefix);
        $this->assertSame('2026-05-16', $outreach->start_date->toDateString());

        $ayomide = User::query()->where('email', 'ayomideajisefinni@gmail.com')->first();
        $this->assertNotNull($ayomide);
        $this->assertTrue($ayomide->hasRole('admin'));
        $this->assertTrue($ayomide->hasRole('check_in'));

        $mobolaji = User::query()->where('email', 'worshipoutlaw@gmail.com')->first();
        $this->assertNotNull($mobolaji);
        $this->assertTrue($mobolaji->hasRole('nurse'));

        $esther = User::query()->where('email', 'estherolajitan@gmail.com')->first();
        $this->assertNotNull($esther);
        $this->assertTrue($esther->hasRole('doctor'));
        $this->assertTrue($esther->hasRole('eye_care'));
        $this->assertTrue($esther->hasRole('dental_care'));

        $rejoice = User::query()->where('email', 'ridornigie@gmail.com')->first();
        $this->assertNotNull($rejoice);
        $this->assertTrue($rejoice->hasRole('pharmacist'));

        $dafe = User::query()->where('email', 'setopgroup@gmail.com')->first();
        $this->assertNotNull($dafe);
        $this->assertTrue($dafe->hasRole('counsellor'));

        $emmanuel = User::query()->where('email', 'hi@hekayode.com')->first();
        $this->assertNotNull($emmanuel);
        $this->assertTrue($emmanuel->hasRole('admin'));

        $this->assertGreaterThan(0, Beneficiary::query()->count());
        $this->assertGreaterThan(0, $outreach->registeredBeneficiaries()->count());
    }
}
