<?php

namespace App\Services;

use App\Enums\ConsultationNextAction;
use App\Enums\InterventionStatus;
use App\Enums\InterventionType;
use App\Enums\LabOrderStatus;
use App\Enums\OutreachStatus;
use App\Enums\VisitStage;
use App\Models\Consultation;
use App\Models\Intervention;
use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\Outreach;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DoctorConsultationService
{
    /**
     * Persist consultation notes, optional lab order and/or prescription, and advance the general-consultation intervention (PRD §3.3, §5a, §9.3.3).
     *
     * @param  array<string, mixed>  $consultationInput  chief_complaint, observations, diagnosis, notes
     * @param  list<array{test_name: string, notes?: string|null}>  $labItems
     * @param  list<array{drug_name: string, dosage: string, frequency: string, duration: string, quantity: int|string}>  $prescriptionItems
     */
    public function save(
        Intervention $intervention,
        User $doctor,
        Outreach $activeOutreach,
        array $consultationInput,
        ConsultationNextAction $nextAction,
        array $labItems,
        array $prescriptionItems,
    ): void {
        $this->validateConsultationInput($consultationInput);
        $this->validateBranchPayload($intervention, $nextAction, $labItems, $prescriptionItems);

        DB::transaction(function () use ($intervention, $doctor, $activeOutreach, $consultationInput, $nextAction, $labItems, $prescriptionItems): void {
            $this->assertOutreachActive($activeOutreach);

            /** @var Intervention $locked */
            $locked = Intervention::query()->whereKey($intervention->getKey())->lockForUpdate()->firstOrFail();

            $this->assertCanSave($locked, $activeOutreach);

            $visit = Visit::query()->whereKey($locked->visit_id)->lockForUpdate()->firstOrFail();

            $consultation = $this->upsertConsultation($locked, $doctor, $consultationInput, $nextAction);

            if ($nextAction === ConsultationNextAction::Lab) {
                $this->createLabOrder($consultation, $doctor, $labItems);
            }

            if ($nextAction === ConsultationNextAction::Pharmacy) {
                $this->createPrescription($locked, $doctor, $prescriptionItems);
            }

            $newStatus = $this->resolveInterventionStatus($nextAction);
            $interventionUpdates = [
                'status' => $newStatus,
            ];

            if ($locked->started_at === null && $locked->status === InterventionStatus::Pending) {
                $interventionUpdates['started_at'] = now();
            }

            if ($newStatus === InterventionStatus::Completed) {
                $interventionUpdates['completed_at'] = now();
            }

            $locked->update($interventionUpdates);

            if ($visit->current_stage === VisitStage::VitalsDone) {
                $visit->update([
                    'current_stage' => VisitStage::InProgress,
                ]);
            }
        });
    }

    /**
     * @param  array<string, mixed>  $consultationInput
     */
    private function validateConsultationInput(array $consultationInput): void
    {
        $complaint = isset($consultationInput['chief_complaint']) ? trim((string) $consultationInput['chief_complaint']) : '';

        if ($complaint === '') {
            throw ValidationException::withMessages([
                'consultationForm.chief_complaint' => __('Chief complaint is required.'),
            ]);
        }
    }

    /**
     * @param  list<array{test_name: string, notes?: string|null}>  $labItems
     * @param  list<array{drug_name: string, dosage: string, frequency: string, duration: string, quantity: int|string}>  $prescriptionItems
     */
    private function validateBranchPayload(
        Intervention $intervention,
        ConsultationNextAction $nextAction,
        array $labItems,
        array $prescriptionItems,
    ): void {
        if ($nextAction === ConsultationNextAction::Counselling) {
            throw ValidationException::withMessages([
                'nextAction' => __('Refer to counselling from the pharmacy station, not the doctor station.'),
            ]);
        }

        if ($intervention->status === InterventionStatus::ConsultationReview && $nextAction === ConsultationNextAction::Lab) {
            throw ValidationException::withMessages([
                'nextAction' => __('Ordering new lab tests is only available before the first lab round.'),
            ]);
        }

        if ($nextAction === ConsultationNextAction::Lab) {
            $clean = $this->cleanLabItems($labItems);
            if ($clean === []) {
                throw ValidationException::withMessages([
                    'labItems' => __('Add at least one lab test when ordering labs.'),
                ]);
            }
        }

        if ($nextAction === ConsultationNextAction::Pharmacy) {
            $cleanRx = $this->cleanPrescriptionItems($prescriptionItems);
            if ($cleanRx === []) {
                throw ValidationException::withMessages([
                    'prescriptionItems' => __('Add at least one prescription line when sending to pharmacy.'),
                ]);
            }

            foreach ($cleanRx as $index => $row) {
                foreach (['drug_name', 'dosage', 'frequency', 'duration'] as $field) {
                    if ($row[$field] === '') {
                        throw ValidationException::withMessages([
                            "prescriptionItems.{$index}.{$field}" => __('This field is required.'),
                        ]);
                    }
                }

                if ($row['quantity'] < 1) {
                    throw ValidationException::withMessages([
                        "prescriptionItems.{$index}.quantity" => __('Quantity must be at least 1.'),
                    ]);
                }
            }
        }
    }

    private function assertOutreachActive(Outreach $outreach): void
    {
        if ($outreach->status !== OutreachStatus::Active) {
            throw ValidationException::withMessages([
                'form' => __('The selected outreach is not active.'),
            ]);
        }
    }

    private function assertCanSave(Intervention $intervention, Outreach $activeOutreach): void
    {
        if ($intervention->type !== InterventionType::GeneralConsultation) {
            throw ValidationException::withMessages([
                'intervention' => __('Only general consultation interventions can be saved from the doctor station.'),
            ]);
        }

        $visit = $intervention->visit;
        if ($visit === null || $visit->outreach_id !== $activeOutreach->getKey()) {
            throw ValidationException::withMessages([
                'intervention' => __('This intervention does not belong to the active outreach.'),
            ]);
        }

        if (! in_array($intervention->status, [InterventionStatus::Pending, InterventionStatus::ConsultationReview], true)) {
            throw ValidationException::withMessages([
                'intervention' => __('This consultation is not awaiting the doctor.'),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $consultationInput
     */
    private function upsertConsultation(
        Intervention $intervention,
        User $doctor,
        array $consultationInput,
        ConsultationNextAction $nextAction,
    ): Consultation {
        $payload = [
            'doctor_user_id' => $doctor->getKey(),
            'chief_complaint' => trim((string) $consultationInput['chief_complaint']),
            'observations' => $this->nullableTrimmedString($consultationInput['observations'] ?? null),
            'diagnosis' => $this->nullableTrimmedString($consultationInput['diagnosis'] ?? null),
            'next_action' => $nextAction,
            'notes' => $this->nullableTrimmedString($consultationInput['notes'] ?? null),
        ];

        $existing = Consultation::query()->where('intervention_id', $intervention->getKey())->first();

        if ($existing instanceof Consultation) {
            $existing->update($payload);

            return $existing->refresh();
        }

        return Consultation::query()->create(array_merge($payload, [
            'intervention_id' => $intervention->getKey(),
        ]));
    }

    /**
     * @param  list<array{test_name: string, notes?: string|null}>  $labItems
     */
    private function createLabOrder(Consultation $consultation, User $doctor, array $labItems): void
    {
        $clean = $this->cleanLabItems($labItems);

        $order = LabOrder::query()->create([
            'consultation_id' => $consultation->getKey(),
            'ordered_by_user_id' => $doctor->getKey(),
            'status' => LabOrderStatus::Pending,
        ]);

        foreach ($clean as $row) {
            LabOrderItem::query()->create([
                'lab_order_id' => $order->getKey(),
                'test_name' => $row['test_name'],
                'notes' => $row['notes'],
            ]);
        }
    }

    /**
     * @param  list<array{drug_name: string, dosage: string, frequency: string, duration: string, quantity: int|string}>  $prescriptionItems
     */
    private function createPrescription(Intervention $intervention, User $doctor, array $prescriptionItems): void
    {
        $cleanRx = $this->cleanPrescriptionItems($prescriptionItems);

        $prescription = Prescription::query()->create([
            'intervention_id' => $intervention->getKey(),
            'prescribed_by_user_id' => $doctor->getKey(),
            'notes' => null,
        ]);

        foreach ($cleanRx as $row) {
            PrescriptionItem::query()->create([
                'prescription_id' => $prescription->getKey(),
                'drug_name' => $row['drug_name'],
                'dosage' => $row['dosage'],
                'frequency' => $row['frequency'],
                'duration' => $row['duration'],
                'quantity' => $row['quantity'],
            ]);
        }
    }

    private function resolveInterventionStatus(ConsultationNextAction $nextAction): InterventionStatus
    {
        return match ($nextAction) {
            ConsultationNextAction::Lab => InterventionStatus::AwaitingLab,
            ConsultationNextAction::Pharmacy => InterventionStatus::AwaitingPharmacy,
            ConsultationNextAction::Counselling => InterventionStatus::AwaitingCounselling,
            ConsultationNextAction::Done => InterventionStatus::Completed,
        };
    }

    /**
     * @param  list<array{test_name: string, notes?: string|null}>  $labItems
     * @return list<array{test_name: string, notes: ?string}>
     */
    private function cleanLabItems(array $labItems): array
    {
        $out = [];

        foreach ($labItems as $row) {
            $name = isset($row['test_name']) ? trim((string) $row['test_name']) : '';
            if ($name === '') {
                continue;
            }

            $notes = isset($row['notes']) ? trim((string) $row['notes']) : '';
            $out[] = [
                'test_name' => $name,
                'notes' => $notes === '' ? null : $notes,
            ];
        }

        return $out;
    }

    /**
     * @param  list<array{drug_name: string, dosage: string, frequency: string, duration: string, quantity: int|string}>  $items
     * @return list<array{drug_name: string, dosage: string, frequency: string, duration: string, quantity: int}>
     */
    private function cleanPrescriptionItems(array $items): array
    {
        $out = [];

        foreach ($items as $row) {
            $drug = isset($row['drug_name']) ? trim((string) $row['drug_name']) : '';
            $dosage = isset($row['dosage']) ? trim((string) $row['dosage']) : '';
            $frequency = isset($row['frequency']) ? trim((string) $row['frequency']) : '';
            $duration = isset($row['duration']) ? trim((string) $row['duration']) : '';
            $qtyRaw = $row['quantity'] ?? '';
            $quantity = is_numeric($qtyRaw) ? (int) $qtyRaw : 0;

            if ($drug === '' && $dosage === '' && $frequency === '' && $duration === '' && ($qtyRaw === '' || $qtyRaw === null)) {
                continue;
            }

            $out[] = [
                'drug_name' => $drug,
                'dosage' => $dosage,
                'frequency' => $frequency,
                'duration' => $duration,
                'quantity' => $quantity,
            ];
        }

        return $out;
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
