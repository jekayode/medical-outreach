<?php

namespace App\Services;

use App\Enums\BeneficiarySource;
use App\Enums\Gender;
use App\Enums\VisitStage;
use App\Enums\VisitStatus;
use App\Models\Beneficiary;
use App\Models\Outreach;
use App\Models\User;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class VisitCheckInService
{
    public function __construct(
        private CheckInCodeGenerator $codeGenerator,
    ) {}

    /**
     * Create a visit for an already-registered beneficiary (pre-reg or walk-in record).
     *
     * @throws ValidationException When a visit already exists for this beneficiary and outreach.
     */
    public function checkInBeneficiary(Outreach $outreach, Beneficiary $beneficiary, User $checkedInBy): Visit
    {
        $this->assertNoExistingVisit($outreach, $beneficiary);

        return DB::transaction(function () use ($outreach, $beneficiary, $checkedInBy): Visit {
            $code = $this->codeGenerator->generate($outreach);

            return $this->persistVisit($outreach, $beneficiary, $checkedInBy, $code);
        });
    }

    /**
     * Register a walk-in beneficiary and check them in in one transaction.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function registerWalkInAndCheckIn(Outreach $outreach, array $data, User $checkedInBy): Visit
    {
        return DB::transaction(function () use ($outreach, $data, $checkedInBy): Visit {
            $beneficiary = Beneficiary::query()->create([
                'full_name' => (string) $data['full_name'],
                'gender' => Gender::from((string) $data['gender']),
                'date_of_birth' => Carbon::parse((string) $data['date_of_birth'])->toDateString(),
                'phone' => (string) $data['phone'],
                'email' => $data['email'] ?? null,
                'residential_address' => (string) $data['residential_address'],
                'existing_medical_conditions' => $data['existing_medical_conditions'] ?? null,
                'medication_status' => $data['medication_status'] ?? null,
                'medication_list' => $data['medication_list'] ?? null,
                'allergies' => $data['allergies'] ?? null,
                'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
                'emergency_contact_relationship' => $data['emergency_contact_relationship'] ?? null,
                'emergency_contact_number' => $data['emergency_contact_number'] ?? null,
                'medical_consent' => (bool) ($data['medical_consent'] ?? false),
                'communication_preference' => $data['communication_preference'] ?? null,
                'source' => BeneficiarySource::WalkIn,
                'imported_at' => null,
                'created_by_user_id' => $checkedInBy->getKey(),
            ]);

            $outreach->registeredBeneficiaries()->syncWithoutDetaching([$beneficiary->getKey()]);

            $code = $this->codeGenerator->generate($outreach);

            return $this->persistVisit($outreach, $beneficiary, $checkedInBy, $code);
        });
    }

    private function assertNoExistingVisit(Outreach $outreach, Beneficiary $beneficiary): void
    {
        if (Visit::query()
            ->where('beneficiary_id', $beneficiary->getKey())
            ->where('outreach_id', $outreach->getKey())
            ->exists()) {
            throw ValidationException::withMessages([
                'beneficiary' => __('This person is already checked in for this outreach.'),
            ]);
        }
    }

    private function persistVisit(Outreach $outreach, Beneficiary $beneficiary, User $checkedInBy, string $code): Visit
    {
        $outreach->registeredBeneficiaries()->syncWithoutDetaching([$beneficiary->getKey()]);

        $visit = Visit::query()->create([
            'beneficiary_id' => $beneficiary->getKey(),
            'outreach_id' => $outreach->getKey(),
            'check_in_code' => $code,
            'checked_in_at' => now(),
            'checked_in_by_user_id' => $checkedInBy->getKey(),
            'current_stage' => VisitStage::CheckedIn,
            'status' => VisitStatus::Open,
        ]);

        return $visit->load(['beneficiary', 'outreach']);
    }
}
