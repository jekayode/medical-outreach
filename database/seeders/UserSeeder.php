<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        $users = [
            ['name' => 'Admin User', 'email' => 'admin@example.com', 'role' => 'admin'],
            ['name' => 'Check-in Staff', 'email' => 'checkin@example.com', 'role' => 'check_in'],
            ['name' => 'Nurse', 'email' => 'nurse@example.com', 'role' => 'nurse'],
            ['name' => 'Doctor', 'email' => 'doctor@example.com', 'role' => 'doctor'],
            ['name' => 'Lab Tech', 'email' => 'lab@example.com', 'role' => 'lab'],
            ['name' => 'Pharmacist', 'email' => 'pharmacy@example.com', 'role' => 'pharmacist'],
            ['name' => 'Eye Care', 'email' => 'eye@example.com', 'role' => 'eye_care'],
            ['name' => 'Dental Care', 'email' => 'dental@example.com', 'role' => 'dental_care'],
            ['name' => 'Counsellor', 'email' => 'counsellor@example.com', 'role' => 'counsellor'],
        ];

        foreach ($users as $row) {
            $user = User::query()->updateOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'password' => $password,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ],
            );
            $user->syncRoles([$row['role']]);
        }
    }
}
