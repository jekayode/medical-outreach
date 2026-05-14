<?php

namespace Database\Seeders;

use App\Enums\VisitStage;
use App\Enums\VisitStatus;
use App\Models\Beneficiary;
use App\Models\Outreach;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Seeder;

/**
 * One checked-in visit (no vitals yet) so station screens can exercise lookup and vitals queues.
 */
class DemoVisitSeeder extends Seeder
{
    public function run(): void
    {
        $outreach = Outreach::query()->where('name', 'Demo Medical Outreach')->first();
        $beneficiary = Beneficiary::query()->orderBy('created_at')->first();
        $user = User::query()->where('email', 'checkin@example.com')->first();

        if (! $outreach || ! $beneficiary || ! $user) {
            return;
        }

        $visit = Visit::query()->updateOrCreate(
            ['check_in_code' => 'MOA-0001'],
            [
                'beneficiary_id' => $beneficiary->getKey(),
                'outreach_id' => $outreach->getKey(),
                'checked_in_at' => now(),
                'checked_in_by_user_id' => $user->getKey(),
                'current_stage' => VisitStage::CheckedIn,
                'status' => VisitStatus::Open,
            ],
        );

        if ($outreach->next_check_in_sequence < 1) {
            $outreach->next_check_in_sequence = 1;
            $outreach->save();
        }

        $outreach->registeredBeneficiaries()->syncWithoutDetaching([$beneficiary->getKey()]);
    }
}
