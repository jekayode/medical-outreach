<?php

namespace Database\Seeders;

use App\Models\Outreach;
use App\Models\User;
use App\Services\BeneficiaryImportService;
use Illuminate\Database\Seeder;
use RuntimeException;

class BeneficiarySeeder extends Seeder
{
    public const REGISTRATION_CSV = 'seeders/data/outreach-registration-may-2026.csv';

    public function run(): void
    {
        $path = database_path(self::REGISTRATION_CSV);

        if (! is_readable($path)) {
            throw new RuntimeException(__('Registration CSV not found at :path', ['path' => $path]));
        }

        $outreach = Outreach::query()->where('name', OutreachSeeder::NAME)->first();
        $importedBy = User::query()->where('email', 'ayomideajisefinni@gmail.com')->first();

        if ($outreach === null || $importedBy === null) {
            return;
        }

        app(BeneficiaryImportService::class)->importFromUpload($outreach, $path, $importedBy);
    }
}
