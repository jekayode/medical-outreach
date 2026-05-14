<?php

namespace Tests\Feature;

use App\Enums\OutreachStatus;
use App\Models\Beneficiary;
use App\Models\Outreach;
use App\Models\User;
use App\Services\BeneficiaryImportService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BeneficiaryImportGoogleFormCsvTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_accepts_data_of_birth_header_typo(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $outreach = Outreach::query()->create([
            'name' => 'May Outreach',
            'location' => 'Lagos',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'code_prefix' => 'TST',
            'status' => OutreachStatus::Planned,
            'next_check_in_sequence' => 0,
        ]);

        $csv = <<<'CSV'
Timestamp,Email address,Full Name ,Gender,Data of Birth,Phone Number ,Residential Address
28/04/2026 11:35:33,test@example.com,Jane Import,Female,16/10/1989,08000000001,1 Test Street
CSV;

        $path = sys_get_temp_dir().'/beneficiary-import-test-'.uniqid('', true).'.csv';
        file_put_contents($path, $csv);

        try {
            $result = app(BeneficiaryImportService::class)->importFromUpload($outreach, $path, $admin);
        } finally {
            @unlink($path);
        }

        $this->assertSame(1, $result['created']);
        $this->assertSame(0, $result['failed']);
        $this->assertDatabaseHas('beneficiaries', [
            'full_name' => 'Jane Import',
            'phone' => '08000000001',
        ]);
        $this->assertSame(1, Beneficiary::query()->count());
    }
}
