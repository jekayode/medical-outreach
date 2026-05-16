<?php

namespace Database\Factories;

use App\Enums\VisitStage;
use App\Enums\VisitStatus;
use App\Models\Beneficiary;
use App\Models\Outreach;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Visit>
 */
class VisitFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'beneficiary_id' => BeneficiaryFactory::new(),
            'outreach_id' => OutreachFactory::new(),
            'check_in_code' => strtoupper(fake()->lexify('???')).'-'.fake()->numerify('####'),
            'checked_in_at' => now(),
            'checked_in_by_user_id' => User::factory(),
            'current_stage' => VisitStage::CheckedIn,
            'status' => VisitStatus::Open,
            'completed_at' => null,
        ];
    }

    public function forOutreach(Outreach $outreach): static
    {
        return $this->state(['outreach_id' => $outreach->getKey()]);
    }

    public function forBeneficiary(Beneficiary $beneficiary): static
    {
        return $this->state(['beneficiary_id' => $beneficiary->getKey()]);
    }

    public function completed(): static
    {
        return $this->state([
            'current_stage' => VisitStage::Completed,
            'status' => VisitStatus::Completed,
            'completed_at' => now(),
        ]);
    }
}
