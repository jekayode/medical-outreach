<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'admin',
            'check_in',
            'nurse',
            'doctor',
            'lab',
            'pharmacist',
            'eye_care',
            'dental_care',
            'counsellor',
        ];

        foreach ($roles as $name) {
            Role::query()->firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }
    }
}
