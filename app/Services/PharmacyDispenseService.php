<?php

namespace App\Services;

use App\Enums\AvailabilityStatus;
use App\Enums\DispensedStatus;
use App\Enums\InterventionStatus;
use App\Enums\OutreachStatus;
use App\Enums\VisitStage;
use App\Enums\VisitStatus;
use App\Models\Intervention;
use App\Models\Outreach;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PharmacyDispenseService
{
    /**
     * Persist availability and dispensed status for all prescription lines on this intervention, then mark this intervention line complete.
     *
     * Each visit can hold multiple interventions (general consultation, eye care, dental care, etc.); pharmacy only completes the intervention row it is dispensing for. When every intervention on the visit is completed, the visit is closed.
     *
     * @param  array<string, array{availability: string, dispensed_status: string}>  $stateByItemId  prescription_item id => row
     */
    public function record(Intervention $intervention, User $pharmacist, Outreach $activeOutreach, array $stateByItemId): void
    {
        DB::transaction(function () use ($intervention, $pharmacist, $activeOutreach, $stateByItemId): void {
            $this->assertOutreachActive($activeOutreach);

            /** @var Intervention $locked */
            $locked = Intervention::query()->whereKey($intervention->getKey())->lockForUpdate()->firstOrFail();

            $this->assertCanRecord($locked, $activeOutreach);

            $items = $this->prescriptionItemsForIntervention($locked->getKey());

            if ($items->isEmpty()) {
                throw ValidationException::withMessages([
                    'form' => __('There are no prescription lines for this intervention.'),
                ]);
            }

            foreach ($items as $item) {
                $key = $item->getKey();
                $row = $stateByItemId[$key] ?? $stateByItemId[(string) $key] ?? null;

                if (! is_array($row)) {
                    throw ValidationException::withMessages([
                        "itemDispense.{$key}" => __('Missing dispense data for a prescription line.'),
                    ]);
                }

                $availabilityRaw = isset($row['availability']) ? trim((string) $row['availability']) : '';
                $dispensedRaw = isset($row['dispensed_status']) ? trim((string) $row['dispensed_status']) : '';

                if ($availabilityRaw === '') {
                    throw ValidationException::withMessages([
                        "itemDispense.{$key}.availability" => __('Choose availability.'),
                    ]);
                }

                if ($dispensedRaw === '') {
                    throw ValidationException::withMessages([
                        "itemDispense.{$key}.dispensed_status" => __('Choose dispensed status.'),
                    ]);
                }

                try {
                    $availability = AvailabilityStatus::from($availabilityRaw);
                    $dispensed = DispensedStatus::from($dispensedRaw);
                } catch (\ValueError) {
                    throw ValidationException::withMessages([
                        "itemDispense.{$key}" => __('Invalid availability or dispensed status.'),
                    ]);
                }

                $this->assertItemBelongsToIntervention($item, $locked->getKey());

                $dispensedAt = $dispensed === DispensedStatus::Dispensed ? now() : null;
                $dispensedBy = $dispensed === DispensedStatus::Dispensed ? $pharmacist->getKey() : null;

                PrescriptionItem::query()->whereKey($item->getKey())->update([
                    'availability' => $availability,
                    'dispensed_status' => $dispensed,
                    'dispensed_by_user_id' => $dispensedBy,
                    'dispensed_at' => $dispensedAt,
                ]);
            }

            $now = now();

            $interventionUpdates = [
                'status' => InterventionStatus::Completed,
            ];

            if ($locked->completed_at === null) {
                $interventionUpdates['completed_at'] = $now;
            }

            $locked->update($interventionUpdates);

            /** @var Visit $visit */
            $visit = Visit::query()->whereKey($locked->visit_id)->lockForUpdate()->firstOrFail();

            $hasIncompleteIntervention = Intervention::query()
                ->where('visit_id', $visit->getKey())
                ->where('status', '!=', InterventionStatus::Completed)
                ->exists();

            if (! $hasIncompleteIntervention && $visit->status !== VisitStatus::Completed) {
                $visit->update([
                    'status' => VisitStatus::Completed,
                    'current_stage' => VisitStage::Completed,
                    'completed_at' => $visit->completed_at ?? $now,
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
        $visit = $intervention->visit;
        if ($visit === null || $visit->outreach_id !== $activeOutreach->getKey()) {
            throw ValidationException::withMessages([
                'intervention' => __('This intervention does not belong to the active outreach.'),
            ]);
        }

        if ($intervention->status !== InterventionStatus::AwaitingPharmacy) {
            throw ValidationException::withMessages([
                'intervention' => __('This patient is not awaiting pharmacy.'),
            ]);
        }
    }

    /**
     * @return Collection<int, PrescriptionItem>
     */
    private function prescriptionItemsForIntervention(string $interventionId): Collection
    {
        return PrescriptionItem::query()
            ->whereHas('prescription', fn ($q) => $q->where('intervention_id', $interventionId))
            ->with('prescription')
            ->lockForUpdate()
            ->orderBy('prescription_id')
            ->orderBy('id')
            ->get();
    }

    private function assertItemBelongsToIntervention(PrescriptionItem $item, string $interventionId): void
    {
        $exists = Prescription::query()
            ->whereKey($item->prescription_id)
            ->where('intervention_id', $interventionId)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'form' => __('Invalid prescription line reference.'),
            ]);
        }
    }
}
