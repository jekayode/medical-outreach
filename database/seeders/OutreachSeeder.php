<?php

namespace Database\Seeders;

use App\Enums\OutreachStatus;
use App\Models\Outreach;
use Illuminate\Database\Seeder;

class OutreachSeeder extends Seeder
{
    public const NAME = 'LifePointe Free Medical Outreach';

    public function run(): void
    {
        Outreach::query()->updateOrCreate(
            ['name' => self::NAME],
            [
                'name' => self::NAME,
                'location' => 'Synlab, Km 26 DAT Mall, Sangotedo, Lagos',
                'start_date' => '2026-05-16',
                'end_date' => '2026-05-16',
                'code_prefix' => 'LPF',
                'status' => OutreachStatus::Active,
                'next_check_in_sequence' => 0,
                'notes' => null,
            ],
        );
    }
}
