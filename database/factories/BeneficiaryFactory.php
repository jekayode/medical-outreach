<?php

namespace Database\Factories;

use App\Enums\BeneficiarySource;
use App\Enums\Gender;
use App\Enums\MedicationStatus;
use App\Models\Beneficiary;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Beneficiary>
 */
class BeneficiaryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'gender' => fake()->randomElement(Gender::cases()),
            'date_of_birth' => fake()->dateTimeBetween('-80 years', '-18 years')->format('Y-m-d'),
            'phone' => fake()->numerify('080########'),
            'email' => null,
            'residential_address' => fake()->address(),
            'existing_medical_conditions' => null,
            'medication_status' => MedicationStatus::None,
            'medication_list' => null,
            'allergies' => null,
            'emergency_contact_name' => null,
            'emergency_contact_relationship' => null,
            'emergency_contact_number' => null,
            'medical_consent' => true,
            'communication_preference' => null,
            'source' => BeneficiarySource::WalkIn,
            'imported_at' => null,
            'created_by_user_id' => null,
        ];
    }

    public function male(): static
    {
        return $this->state(['gender' => Gender::Male]);
    }

    public function female(): static
    {
        return $this->state(['gender' => Gender::Female]);
    }

    public function imported(): static
    {
        return $this->state([
            'source' => BeneficiarySource::GoogleFormImport,
            'imported_at' => now(),
        ]);
    }
}
