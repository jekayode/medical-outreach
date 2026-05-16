<?php

namespace Database\Factories;

use App\Enums\OutreachStatus;
use App\Models\Outreach;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Outreach>
 */
class OutreachFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-1 month', '+1 month');

        return [
            'name' => fake()->city().' Medical Outreach',
            'location' => fake()->address(),
            'start_date' => $start,
            'end_date' => (clone $start)->modify('+1 day'),
            'code_prefix' => strtoupper(fake()->lexify('???')),
            'status' => OutreachStatus::Planned,
            'next_check_in_sequence' => 1,
            'notes' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => OutreachStatus::Active]);
    }

    public function closed(): static
    {
        return $this->state(['status' => OutreachStatus::Closed]);
    }
}
