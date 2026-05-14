<?php

namespace Database\Seeders;

use App\Enums\OutreachStatus;
use App\Models\Outreach;
use Illuminate\Database\Seeder;

class OutreachSeeder extends Seeder
{
    public function run(): void
    {
        Outreach::query()->updateOrCreate(
            ['name' => 'Demo Medical Outreach'],
            [
                'name' => 'Demo Medical Outreach',
                'location' => 'Demo Venue',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addDay()->toDateString(),
                'code_prefix' => 'MOA',
                'status' => OutreachStatus::Active,
                'next_check_in_sequence' => 0,
                'notes' => null,
            ],
        );
    }
}
