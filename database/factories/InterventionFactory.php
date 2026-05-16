<?php

namespace Database\Factories;

use App\Enums\InterventionStatus;
use App\Enums\InterventionType;
use App\Models\Intervention;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Intervention>
 */
class InterventionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'visit_id' => VisitFactory::new(),
            'type' => fake()->randomElement(InterventionType::cases()),
            'status' => InterventionStatus::Pending,
            'started_at' => null,
            'completed_at' => null,
        ];
    }

    public function forVisit(Visit $visit): static
    {
        return $this->state(['visit_id' => $visit->getKey()]);
    }

    public function generalConsultation(): static
    {
        return $this->state(['type' => InterventionType::GeneralConsultation]);
    }

    public function eyeCare(): static
    {
        return $this->state(['type' => InterventionType::EyeCare]);
    }

    public function dentalCare(): static
    {
        return $this->state(['type' => InterventionType::DentalCare]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => InterventionStatus::Completed,
            'started_at' => now()->subMinutes(30),
            'completed_at' => now(),
        ]);
    }
}
