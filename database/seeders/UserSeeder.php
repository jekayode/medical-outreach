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
            [
                'name' => 'Ayomide Ajisefinni',
                'email' => 'ayomideajisefinni@gmail.com',
                'roles' => ['admin', 'check_in'],
            ],
            [
                'name' => 'Mobolaji Ade',
                'email' => 'worshipoutlaw@gmail.com',
                'roles' => ['nurse'],
            ],
            [
                'name' => 'Esther Olajitan',
                'email' => 'estherolajitan@gmail.com',
                'roles' => ['doctor', 'eye_care', 'dental_care'],
            ],
            [
                'name' => 'Rejoice Idornigie (Eshiema)',
                'email' => 'ridornigie@gmail.com',
                'roles' => ['pharmacist'],
            ],
            [
                'name' => 'Dafe Eyoufe',
                'email' => 'setopgroup@gmail.com',
                'roles' => ['counsellor'],
            ],
            [
                'name' => 'Emmanuel Joseph',
                'email' => 'hi@hekayode.com',
                'roles' => ['admin'],
            ],
        ];

        foreach ($users as $row) {
            $user = User::query()->updateOrCreate(
                ['email' => strtolower($row['email'])],
                [
                    'name' => $row['name'],
                    'password' => $password,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ],
            );
            $user->syncRoles($row['roles']);
        }
    }
}
