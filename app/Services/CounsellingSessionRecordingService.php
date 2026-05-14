<?php

namespace App\Services;

use App\Enums\CounsellingType;
use App\Enums\InterventionStatus;
use App\Enums\OutreachStatus;
use App\Enums\VisitStage;
use App\Enums\VisitStatus;
use App\Models\CounsellingSession;
use App\Models\Outreach;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CounsellingSessionRecordingService
{
    /**
     * Record counselling session, complete awaiting interventions, and close the visit (PRD §9.3.8).
     *
     * @param  array<string, mixed>  $sessionInput
     */
    public function record(Visit $visit, User $counsellor, Outreach $activeOutreach, array $sessionInput): void
    {
        DB::transaction(function () use ($visit, $counsellor, $activeOutreach, $sessionInput): void {
            $this->assertOutreachActive($activeOutreach);

            /** @var Visit $lockedVisit */
            $lockedVisit = Visit::query()->whereKey($visit->getKey())->lockForUpdate()->firstOrFail();

            $lockedVisit->load(['interventions' => function ($query): void {
                $query->lockForUpdate();
            }]);

            $this->assertCanRecord($lockedVisit, $activeOutreach);

            $types = $this->validatedTypes($sessionInput);
            $notes = $this->nullableTrimmedString($sessionInput['notes'] ?? null);

            CounsellingSession::query()->create([
                'visit_id' => $lockedVisit->getKey(),
                'counsellor_user_id' => $counsellor->getKey(),
                'types' => array_map(fn (CounsellingType $t) => $t->value, $types),
                'notes' => $notes,
            ]);

            $now = now();

            foreach ($lockedVisit->interventions as $intervention) {
                if ($intervention->status === InterventionStatus::AwaitingCounselling) {
                    $updates = [
                        'status' => InterventionStatus::Completed,
                    ];

                    if ($intervention->completed_at === null) {
                        $updates['completed_at'] = $now;
                    }

                    $intervention->update($updates);
                }
            }

            $lockedVisit->update([
                'status' => VisitStatus::Completed,
                'current_stage' => VisitStage::Completed,
                'completed_at' => $now,
            ]);
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

    private function assertCanRecord(Visit $visit, Outreach $activeOutreach): void
    {
        if ($visit->outreach_id !== $activeOutreach->getKey()) {
            throw ValidationException::withMessages([
                'visit' => __('This visit does not belong to the active outreach.'),
            ]);
        }

        if (CounsellingSession::query()->where('visit_id', $visit->getKey())->exists()) {
            throw ValidationException::withMessages([
                'visit' => __('Counselling has already been recorded for this visit.'),
            ]);
        }

        $interventions = $visit->interventions;

        if ($interventions->isEmpty()) {
            throw ValidationException::withMessages([
                'visit' => __('This visit has no interventions to complete.'),
            ]);
        }

        $hasAwaitingCounselling = false;

        foreach ($interventions as $intervention) {
            $status = $intervention->status;

            if ($status === InterventionStatus::AwaitingCounselling) {
                $hasAwaitingCounselling = true;

                continue;
            }

            if ($status === InterventionStatus::Completed) {
                continue;
            }

            throw ValidationException::withMessages([
                'visit' => __('Counselling cannot be recorded until all interventions are finished or awaiting counselling.'),
            ]);
        }

        if (! $hasAwaitingCounselling) {
            throw ValidationException::withMessages([
                'visit' => __('No interventions are awaiting counselling for this visit.'),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $sessionInput
     * @return list<CounsellingType>
     */
    private function validatedTypes(array $sessionInput): array
    {
        $raw = $sessionInput['types'] ?? [];

        if (! is_array($raw)) {
            throw ValidationException::withMessages([
                'sessionFormTypes' => __('Select at least one counselling type.'),
            ]);
        }

        $types = CounsellingType::tryFromMany($raw);

        if ($types === []) {
            throw ValidationException::withMessages([
                'sessionFormTypes' => __('Select at least one counselling type.'),
            ]);
        }

        return $types;
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
