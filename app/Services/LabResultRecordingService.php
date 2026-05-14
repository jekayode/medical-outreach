<?php

namespace App\Services;

use App\Enums\InterventionStatus;
use App\Enums\InterventionType;
use App\Enums\LabOrderStatus;
use App\Enums\OutreachStatus;
use App\Models\Consultation;
use App\Models\Intervention;
use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\Outreach;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class LabResultRecordingService
{
    /**
     * Record free-text results for all pending lab items on this consultation and return the patient to the doctor queue (PRD §5b, §9.3.4).
     *
     * @param  array<string, string>  $resultByItemId  lab_order_item id => result text
     */
    public function record(Intervention $intervention, User $labUser, Outreach $activeOutreach, array $resultByItemId): void
    {
        DB::transaction(function () use ($intervention, $labUser, $activeOutreach, $resultByItemId): void {
            $this->assertOutreachActive($activeOutreach);

            /** @var Intervention $locked */
            $locked = Intervention::query()->whereKey($intervention->getKey())->lockForUpdate()->firstOrFail();

            $this->assertCanRecord($locked, $activeOutreach);

            $consultation = Consultation::query()
                ->where('intervention_id', $locked->getKey())
                ->lockForUpdate()
                ->first();

            if (! $consultation instanceof Consultation) {
                throw ValidationException::withMessages([
                    'form' => __('No consultation record is linked to this intervention.'),
                ]);
            }

            $pendingOrders = LabOrder::query()
                ->where('consultation_id', $consultation->getKey())
                ->where('status', LabOrderStatus::Pending)
                ->with('items')
                ->lockForUpdate()
                ->get();

            /** @var Collection<int, LabOrderItem> $items */
            $items = $pendingOrders->flatMap(fn (LabOrder $order): Collection => $order->items);

            if ($items->isEmpty()) {
                throw ValidationException::withMessages([
                    'form' => __('There are no pending lab tests for this patient.'),
                ]);
            }

            foreach ($items as $item) {
                $key = $item->getKey();
                $raw = $resultByItemId[$key] ?? $resultByItemId[(string) $key] ?? '';
                $text = trim((string) $raw);

                if ($text === '') {
                    throw ValidationException::withMessages([
                        "itemResults.{$key}" => __('Enter a result for each test.'),
                    ]);
                }

                $this->assertItemBelongsToConsultation($item, $consultation->getKey());

                LabOrderItem::query()->whereKey($item->getKey())->update([
                    'result' => $text,
                    'result_recorded_by_user_id' => $labUser->getKey(),
                    'result_recorded_at' => now(),
                ]);
            }

            foreach ($pendingOrders as $order) {
                $order->update([
                    'status' => LabOrderStatus::Completed,
                ]);
            }

            $locked->update([
                'status' => InterventionStatus::ConsultationReview,
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

    private function assertCanRecord(Intervention $intervention, Outreach $activeOutreach): void
    {
        if ($intervention->type !== InterventionType::GeneralConsultation) {
            throw ValidationException::withMessages([
                'intervention' => __('Only general consultation lab queues can be processed here.'),
            ]);
        }

        $visit = $intervention->visit;
        if ($visit === null || $visit->outreach_id !== $activeOutreach->getKey()) {
            throw ValidationException::withMessages([
                'intervention' => __('This intervention does not belong to the active outreach.'),
            ]);
        }

        if ($intervention->status !== InterventionStatus::AwaitingLab) {
            throw ValidationException::withMessages([
                'intervention' => __('This patient is not awaiting lab results.'),
            ]);
        }
    }

    private function assertItemBelongsToConsultation(LabOrderItem $item, string $consultationId): void
    {
        $orderId = $item->lab_order_id;
        $exists = LabOrder::query()
            ->whereKey($orderId)
            ->where('consultation_id', $consultationId)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'form' => __('Invalid lab item reference.'),
            ]);
        }
    }
}
