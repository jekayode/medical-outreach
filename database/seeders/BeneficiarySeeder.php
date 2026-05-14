<?php

namespace Database\Seeders;

use App\Enums\BeneficiarySource;
use App\Enums\Gender;
use App\Models\Beneficiary;
use App\Models\Outreach;
use Illuminate\Database\Seeder;

class BeneficiarySeeder extends Seeder
{
    public function run(): void
    {
        $ids = [];

        for ($i = 0; $i < 20; $i++) {
            $beneficiary = Beneficiary::query()->create([
                'full_name' => fake()->name(),
                'gender' => fake()->randomElement(Gender::cases()),
                'date_of_birth' => fake()->dateTimeBetween('-80 years', '-18 years')->format('Y-m-d'),
                'phone' => fake()->unique()->numerify('080########'),
                'email' => fake()->optional()->safeEmail(),
                'residential_address' => fake()->address(),
                'existing_medical_conditions' => fake()->optional()->sentence(),
                'medication_status' => null,
                'medication_list' => null,
                'allergies' => fake()->optional()->sentence(),
                'emergency_contact_name' => fake()->optional()->name(),
                'emergency_contact_relationship' => fake()->optional()->randomElement(['Spouse', 'Sibling', 'Parent']),
                'emergency_contact_number' => fake()->optional()->numerify('080########'),
                'medical_consent' => true,
                'communication_preference' => null,
                'source' => BeneficiarySource::WalkIn,
                'imported_at' => null,
                'created_by_user_id' => null,
            ]);
            $ids[] = $beneficiary->getKey();
        }

        $outreach = Outreach::query()->where('name', 'Demo Medical Outreach')->first();
        if ($outreach !== null && $ids !== []) {
            $outreach->registeredBeneficiaries()->syncWithoutDetaching($ids);
        }
    }
}
