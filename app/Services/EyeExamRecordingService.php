<?php

namespace App\Services;

use App\Enums\InterventionStatus;
use App\Enums\InterventionType;
use App\Enums\OutreachStatus;
use App\Enums\VisitStage;
use App\Models\EyeExam;
use App\Models\Intervention;
use App\Models\Outreach;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class EyeExamRecordingService
{
    /**
     * Persist eye exam, optionally create a prescription for drops, and route the intervention (PRD §5f, §9.3.6).
     *
     * @param  array<string, mixed>  $examInput
     * @param  array<string, string>  $dropsRx
     */
    public function record(Intervention $intervention, User $examiner, Outreach $activeOutreach, array $examInput, array $dropsRx): void
    {
        DB::transaction(function () use ($intervention, $examiner, $activeOutreach, $examInput, $dropsRx): void {
            $this->assertOutreachActive($activeOutreach);

            /** @var Intervention $locked */
            $locked = Intervention::query()->whereKey($intervention->getKey())->lockForUpdate()->firstOrFail();

            $this->assertCanRecord($locked, $activeOutreach);

            $visit = Visit::query()->whereKey($locked->visit_id)->lockForUpdate()->firstOrFail();

            $glassesPrescribed = $this->toBool($examInput['glasses_prescribed'] ?? false);
            $dropsPrescribed = $this->toBool($examInput['drops_prescribed'] ?? false);
            $referralNeeded = $this->toBool($examInput['referral_needed'] ?? false);

            $this->validateExam($examInput, $dropsRx, $glassesPrescribed, $dropsPrescribed, $referralNeeded);

            $glassesDetails = $glassesPrescribed
                ? trim((string) $examInput['glasses_prescription_details'])
                : null;

            $referralNotes = $referralNeeded
                ? trim((string) $examInput['referral_notes'])
                : $this->nullableTrimmedString($examInput['referral_notes'] ?? null);

            EyeExam::query()->updateOrCreate(
                ['intervention_id' => $locked->getKey()],
                [
                    'examined_by_user_id' => $examiner->getKey(),
                    'visual_acuity_left' => $this->nullableTrimmedString($examInput['visual_acuity_left'] ?? null),
                    'visual_acuity_right' => $this->nullableTrimmedString($examInput['visual_acuity_right'] ?? null),
                    'findings' => $this->nullableTrimmedString($examInput['findings'] ?? null),
                    'glasses_prescribed' => $glassesPrescribed,
                    'glasses_prescription_details' => $glassesDetails,
                    'drops_prescribed' => $dropsPrescribed,
                    'referral_needed' => $referralNeeded,
                    'referral_notes' => $referralNotes,
                    'notes' => $this->nullableTrimmedString($examInput['notes'] ?? null),
                ],
            );

            if ($dropsPrescribed) {
                $this->createDropsPrescription($locked, $examiner, $dropsRx);
            }

            $newStatus = $dropsPrescribed
                ? InterventionStatus::AwaitingPharmacy
                : InterventionStatus::AwaitingCounselling;

            $updates = [
                'status' => $newStatus,
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
        if ($intervention->type !== InterventionType::EyeCare) {
            throw ValidationException::withMessages([
                'intervention' => __('Only eye care interventions can be saved from the eye clinic station.'),
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
                'intervention' => __('This eye exam is not awaiting the clinic.'),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $examInput
     * @param  array<string, string>  $dropsRx
     */
    private function validateExam(
        array $examInput,
        array $dropsRx,
        bool $glassesPrescribed,
        bool $dropsPrescribed,
        bool $referralNeeded,
    ): void {
        if ($glassesPrescribed && trim((string) ($examInput['glasses_prescription_details'] ?? '')) === '') {
            throw ValidationException::withMessages([
                'examForm.glasses_prescription_details' => __('Enter glasses prescription details when glasses are prescribed.'),
            ]);
        }

        if ($referralNeeded && trim((string) ($examInput['referral_notes'] ?? '')) === '') {
            throw ValidationException::withMessages([
                'examForm.referral_notes' => __('Enter referral notes when a referral is needed.'),
            ]);
        }

        if (! $dropsPrescribed) {
            return;
        }

        $drug = trim((string) ($dropsRx['drug_name'] ?? ''));
        $dosage = trim((string) ($dropsRx['dosage'] ?? ''));
        $frequency = trim((string) ($dropsRx['frequency'] ?? ''));
        $duration = trim((string) ($dropsRx['duration'] ?? ''));
        $qtyRaw = $dropsRx['quantity'] ?? '';

        if ($drug === '') {
            throw ValidationException::withMessages([
                'dropsRx.drug_name' => __('Drug name is required when drops are prescribed.'),
            ]);
        }

        foreach (['dosage' => $dosage, 'frequency' => $frequency, 'duration' => $duration] as $field => $val) {
            if ($val === '') {
                throw ValidationException::withMessages([
                    "dropsRx.{$field}" => __('This field is required when drops are prescribed.'),
                ]);
            }
        }

        if (! is_numeric($qtyRaw) || (int) $qtyRaw < 1) {
            throw ValidationException::withMessages([
                'dropsRx.quantity' => __('Quantity must be at least 1 when drops are prescribed.'),
            ]);
        }
    }

    /**
     * @param  array<string, string>  $dropsRx
     */
    private function createDropsPrescription(Intervention $intervention, User $examiner, array $dropsRx): void
    {
        $prescription = Prescription::query()->create([
            'intervention_id' => $intervention->getKey(),
            'prescribed_by_user_id' => $examiner->getKey(),
            'notes' => __('Eye clinic — drops'),
        ]);

        PrescriptionItem::query()->create([
            'prescription_id' => $prescription->getKey(),
            'drug_name' => trim((string) $dropsRx['drug_name']),
            'dosage' => trim((string) $dropsRx['dosage']),
            'frequency' => trim((string) $dropsRx['frequency']),
            'duration' => trim((string) $dropsRx['duration']),
            'quantity' => (int) $dropsRx['quantity'],
        ]);
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
