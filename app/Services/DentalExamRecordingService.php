<?php

namespace App\Services;

use App\Enums\InterventionStatus;
use App\Enums\InterventionType;
use App\Enums\OutreachStatus;
use App\Enums\VisitStage;
use App\Models\DentalExam;
use App\Models\Intervention;
use App\Models\Outreach;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DentalExamRecordingService
{
    /**
     * Persist dental exam and route the intervention to counselling (PRD §5f, §9.3.7).
     *
     * @param  array<string, mixed>  $examInput
     */
    public function record(Intervention $intervention, User $examiner, Outreach $activeOutreach, array $examInput): void
    {
        DB::transaction(function () use ($intervention, $examiner, $activeOutreach, $examInput): void {
            $this->assertOutreachActive($activeOutreach);

            /** @var Intervention $locked */
            $locked = Intervention::query()->whereKey($intervention->getKey())->lockForUpdate()->firstOrFail();

            $this->assertCanRecord($locked, $activeOutreach);

            $visit = Visit::query()->whereKey($locked->visit_id)->lockForUpdate()->firstOrFail();

            $referralNeeded = $this->toBool($examInput['referral_needed'] ?? false);

            $this->validateExam($examInput, $referralNeeded);

            $referralNotes = $referralNeeded
                ? trim((string) $examInput['referral_notes'])
                : $this->nullableTrimmedString($examInput['referral_notes'] ?? null);

            DentalExam::query()->updateOrCreate(
                ['intervention_id' => $locked->getKey()],
                [
                    'examined_by_user_id' => $examiner->getKey(),
                    'findings' => trim((string) $examInput['findings']),
                    'treatment_performed' => $this->nullableTrimmedString($examInput['treatment_performed'] ?? null),
                    'referral_needed' => $referralNeeded,
                    'referral_notes' => $referralNotes,
                    'notes' => $this->nullableTrimmedString($examInput['notes'] ?? null),
                ],
            );

            $updates = [
                'status' => InterventionStatus::AwaitingCounselling,
            ];

            if ($locked->started_at === null) {
                $updates['started_at'] = now();
            }

            $locked->update($updates);

            if ($visit->current_stage === VisitStage::VitalsDone) {
                $visit->update([
                    'current_stage' => VisitStage::InProgress,
                ]);
            }
        });
    }

    private function assertOutreachActive(Outreach $outreach): void
    {
        if ($outreach->status !== OutreachStatus::Active) {
            throw ValidationException::withMessages([
                'form' => __('The selected outreach is not active.'),
            ]);
        }
    }

    private function assertCanRecord(Intervention $intervention, Outreach $activeOutreach): void
    {
        if ($intervention->type !== InterventionType::DentalCare) {
            throw ValidationException::withMessages([
                'intervention' => __('Only dental care interventions can be saved from the dental clinic station.'),
            ]);
        }

        $visit = $intervention->visit;
        if ($visit === null || $visit->outreach_id !== $activeOutreach->getKey()) {
            throw ValidationException::withMessages([
                'intervention' => __('This intervention does not belong to the active outreach.'),
            ]);
        }

        if ($intervention->status !== InterventionStatus::Pending) {
            throw ValidationException::withMessages([
                'intervention' => __('This dental exam is not awaiting the clinic.'),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $examInput
     */
    private function validateExam(array $examInput, bool $referralNeeded): void
    {
        if (trim((string) ($examInput['findings'] ?? '')) === '') {
            throw ValidationException::withMessages([
                'examForm.findings' => __('Enter clinical findings before saving.'),
            ]);
        }

        if ($referralNeeded && trim((string) ($examInput['referral_notes'] ?? '')) === '') {
            throw ValidationException::withMessages([
                'examForm.referral_notes' => __('Enter referral notes when a referral is needed.'),
            ]);
        }
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if ($value === 1 || $value === '1' || $value === 'true' || $value === 'on') {
            return true;
        }

        return false;
    }

    private function nullableTrimmedString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $s = trim((string) $value);

        return $s === '' ? null : $s;
    }
}
