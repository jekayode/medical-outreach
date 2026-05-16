<?php

namespace App\Services;

use App\Enums\HivStatus;
use App\Enums\InterventionStatus;
use App\Enums\InterventionType;
use App\Enums\VisitStage;
use App\Models\Intervention;
use App\Models\Outreach;
use App\Models\User;
use App\Models\Visit;
use App\Models\Vitals;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class VitalsRecordingService
{
    /**
     * Persist vitals, create selected interventions (pending), and advance the visit (PRD §4 step 4, §9.3.2).
     *
     * @param  array<string, mixed>  $vitalsInput
     * @param  list<string>  $interventionTypeValues  {@see InterventionType} value strings
     */
    public function record(
        Visit $visit,
        User $nurse,
        Outreach $activeOutreach,
        array $vitalsInput,
        array $interventionTypeValues,
    ): Vitals {
        if ($interventionTypeValues === []) {
            throw ValidationException::withMessages([
                'interventionSelections' => __('Select at least one service line for this visit.'),
            ]);
        }

        $types = collect($interventionTypeValues)
            ->unique()
            ->values()
            ->map(fn (string $value): InterventionType => InterventionType::from($value))
            ->all();

        return DB::transaction(function () use ($visit, $nurse, $activeOutreach, $vitalsInput, $types): Vitals {
            /** @var Visit $locked */
            $locked = Visit::query()->whereKey($visit->getKey())->lockForUpdate()->firstOrFail();

            $this->assertCanRecord($locked, $activeOutreach);

            $vitals = Vitals::query()->create([
                'visit_id' => $locked->getKey(),
                'taken_by_user_id' => $nurse->getKey(),
                'blood_pressure_systolic' => $this->nullableInt($vitalsInput['blood_pressure_systolic'] ?? null),
                'blood_pressure_diastolic' => $this->nullableInt($vitalsInput['blood_pressure_diastolic'] ?? null),
                'pulse' => $this->nullableInt($vitalsInput['pulse'] ?? null),
                'temperature' => $this->nullableDecimal($vitalsInput['temperature'] ?? null),
                'weight_kg' => $this->nullableDecimal($vitalsInput['weight_kg'] ?? null),
                'height_cm' => $this->nullableDecimal($vitalsInput['height_cm'] ?? null),
                'blood_glucose' => $this->nullableDecimal($vitalsInput['blood_glucose'] ?? null),
                'hiv_status' => $this->nullableHivStatus($vitalsInput['hiv_status'] ?? null),
                'notes' => $this->nullableString($vitalsInput['notes'] ?? null),
                'taken_at' => now(),
            ]);

            foreach ($types as $type) {
                Intervention::query()->create([
                    'visit_id' => $locked->getKey(),
                    'type' => $type,
                    'status' => $type === InterventionType::GeneralConsultation
                        ? InterventionStatus::AwaitingLab
                        : InterventionStatus::Pending,
                ]);
            }

            $locked->update([
                'current_stage' => VisitStage::VitalsDone,
            ]);

            return $vitals->refresh();
        });
    }

    private function assertCanRecord(Visit $visit, Outreach $activeOutreach): void
    {
        if ($visit->outreach_id !== $activeOutreach->getKey()) {
            throw ValidationException::withMessages([
                'visit' => __('This visit does not belong to the active outreach.'),
            ]);
        }

        if ($visit->current_stage !== VisitStage::CheckedIn) {
            throw ValidationException::withMessages([
                'visit' => __('Vitals can only be recorded while the visit is in the checked-in stage.'),
            ]);
        }

        if ($visit->vitals()->exists()) {
            throw ValidationException::withMessages([
                'visit' => __('Vitals have already been recorded for this visit.'),
            ]);
        }
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function nullableDecimal(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $s = trim((string) $value);

        return $s === '' ? null : $s;
    }

    private function nullableHivStatus(mixed $value): ?HivStatus
    {
        if ($value === null || $value === '') {
            return null;
        }

        return HivStatus::from((string) $value);
    }
}
