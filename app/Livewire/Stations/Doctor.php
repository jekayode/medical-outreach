<?php

namespace App\Livewire\Stations;

use App\Enums\ConsultationNextAction;
use App\Enums\InterventionStatus;
use App\Enums\InterventionType;
use App\Enums\OutreachStatus;
use App\Models\Intervention;
use App\Models\Outreach;
use App\Models\User;
use App\Models\Visit;
use App\Services\DoctorConsultationService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;

class Doctor extends StationPage
{
    public string $queueTab = 'pending';

    public ?string $selectedInterventionId = null;

    /**
     * @var array<string, string>
     */
    public array $consultationForm = [
        'chief_complaint' => '',
        'observations' => '',
        'diagnosis' => '',
        'notes' => '',
    ];

    public string $nextAction = '';

    /**
     * @var list<array{test_name: string, notes: string}>
     */
    public array $labItems = [['test_name' => '', 'notes' => '']];

    /**
     * @var list<array{drug_name: string, dosage: string, frequency: string, duration: string, quantity: string}>
     */
    public array $prescriptionItems = [['drug_name' => '', 'dosage' => '', 'frequency' => '', 'duration' => '', 'quantity' => '']];

    public ?string $successMessage = null;

    #[On('visit-selected')]
    public function onVisitSelected(string $visitId): void
    {
        $this->selectedVisitId = $visitId;
        $this->successMessage = null;
        $this->resetErrorBag();
        $this->selectedInterventionId = $this->resolveInterventionIdForVisit($visitId);
        $this->syncFormFromSelection();
    }

    public function updatingQueueTab(string $value): void
    {
        if ($value === 'done' || $this->queueTab === 'done') {
            $this->selectedInterventionId = null;
            $this->selectedVisitId = null;
            $this->resetClinicalForms();
        }
    }

    public function updatedQueueTab(): void
    {
        $this->successMessage = null;
    }

    public function selectQueueIntervention(string $interventionId): void
    {
        $this->successMessage = null;
        $this->resetErrorBag();
        $this->selectedInterventionId = $interventionId;
        $intervention = Intervention::query()->find($interventionId);
        $this->selectedVisitId = $intervention instanceof Intervention ? $intervention->visit_id : null;
        $this->syncFormFromSelection();
    }

    public function addLabRow(): void
    {
        $this->labItems[] = ['test_name' => '', 'notes' => ''];
    }

    public function removeLabRow(int $index): void
    {
        unset($this->labItems[$index]);
        $this->labItems = array_values($this->labItems);
        if ($this->labItems === []) {
            $this->labItems = [['test_name' => '', 'notes' => '']];
        }
    }

    public function addPrescriptionRow(): void
    {
        $this->prescriptionItems[] = ['drug_name' => '', 'dosage' => '', 'frequency' => '', 'duration' => '', 'quantity' => ''];
    }

    public function removePrescriptionRow(int $index): void
    {
        unset($this->prescriptionItems[$index]);
        $this->prescriptionItems = array_values($this->prescriptionItems);
        if ($this->prescriptionItems === []) {
            $this->prescriptionItems = [['drug_name' => '', 'dosage' => '', 'frequency' => '', 'duration' => '', 'quantity' => '']];
        }
    }

    public function saveConsultation(DoctorConsultationService $service): void
    {
        $this->resetErrorBag();
        $this->successMessage = null;

        $activeOutreach = Outreach::query()->where('status', OutreachStatus::Active)->first();
        if (! $activeOutreach instanceof Outreach) {
            $this->addError('form', __('There is no active outreach.'));

            return;
        }

        if (! $this->selectedInterventionId) {
            $this->addError('form', __('Select a general consultation from the queue or search results first.'));

            return;
        }

        $intervention = Intervention::query()->find($this->selectedInterventionId);
        if (! $intervention instanceof Intervention) {
            $this->addError('form', __('Intervention not found.'));

            return;
        }

        $this->validate([
            'consultationForm.chief_complaint' => ['required', 'string', 'max:10000'],
            'consultationForm.observations' => ['nullable', 'string', 'max:10000'],
            'consultationForm.diagnosis' => ['nullable', 'string', 'max:10000'],
            'consultationForm.notes' => ['nullable', 'string', 'max:10000'],
            'nextAction' => ['required', Rule::enum(ConsultationNextAction::class)],
            'labItems' => ['array'],
            'labItems.*.test_name' => ['nullable', 'string', 'max:255'],
            'labItems.*.notes' => ['nullable', 'string', 'max:2000'],
            'prescriptionItems' => ['array'],
            'prescriptionItems.*.drug_name' => ['nullable', 'string', 'max:255'],
            'prescriptionItems.*.dosage' => ['nullable', 'string', 'max:255'],
            'prescriptionItems.*.frequency' => ['nullable', 'string', 'max:255'],
            'prescriptionItems.*.duration' => ['nullable', 'string', 'max:255'],
            'prescriptionItems.*.quantity' => ['nullable', 'integer', 'min:0'],
        ]);

        $user = auth()->user();
        if (! $user instanceof User) {
            $this->addError('form', __('You must be signed in.'));

            return;
        }

        try {
            $service->save(
                $intervention,
                $user,
                $activeOutreach,
                $this->consultationForm,
                ConsultationNextAction::from($this->nextAction),
                $this->labItems,
                $this->prescriptionItems,
            );
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        $this->selectedInterventionId = null;
        $this->selectedVisitId = null;
        $this->resetClinicalForms();
        $this->successMessage = __('Consultation saved.');
    }

    protected function stationHeading(): string
    {
        return __('General doctor station');
    }

    public function render(): View
    {
        $activeOutreach = Outreach::query()->where('status', OutreachStatus::Active)->first();

        $queueInterventions = collect();
        $doneInterventions = collect();

        if ($activeOutreach instanceof Outreach) {
            if ($this->queueTab === 'done') {
                $doneStatuses = [
                    InterventionStatus::AwaitingLab,
                    InterventionStatus::AwaitingPharmacy,
                    InterventionStatus::AwaitingCounselling,
                    InterventionStatus::Completed,
                ];

                $doneInterventions = Intervention::query()
                    ->where('interventions.type', InterventionType::GeneralConsultation)
                    ->whereIn('interventions.status', $doneStatuses)
                    ->whereHas('visit', fn ($q) => $q->where('outreach_id', $activeOutreach->getKey()))
                    ->join('visits', 'visits.id', '=', 'interventions.visit_id')
                    ->orderByDesc('interventions.updated_at')
                    ->select('interventions.*')
                    ->with(['visit.beneficiary'])
                    ->limit(100)
                    ->get();
            } else {
                $queueStatus = $this->queueTab === 'pending'
                    ? InterventionStatus::Pending
                    : InterventionStatus::ConsultationReview;

                $queueInterventions = Intervention::query()
                    ->where('interventions.type', InterventionType::GeneralConsultation)
                    ->where('interventions.status', $queueStatus)
                    ->whereHas('visit', fn ($q) => $q->where('outreach_id', $activeOutreach->getKey()))
                    ->join('visits', 'visits.id', '=', 'interventions.visit_id')
                    ->orderBy('visits.checked_in_at')
                    ->select('interventions.*')
                    ->with(['visit.beneficiary'])
                    ->limit(50)
                    ->get();
            }
        }

        $selectedVisit = null;
        if ($this->selectedVisitId) {
            $selectedVisit = Visit::query()
                ->with(['beneficiary', 'vitals'])
                ->find($this->selectedVisitId);
        }

        $selectedIntervention = null;
        if ($this->selectedInterventionId) {
            $selectedIntervention = Intervention::query()
                ->with(['visit.beneficiary', 'visit.vitals', 'consultation.labOrders.items', 'prescriptions.items'])
                ->find($this->selectedInterventionId);
        }

        $canSave = $selectedIntervention instanceof Intervention
            && $selectedIntervention->type === InterventionType::GeneralConsultation
            && in_array($selectedIntervention->status, [InterventionStatus::Pending, InterventionStatus::ConsultationReview], true);

        $isDoneListIntervention = $selectedIntervention instanceof Intervention
            && $selectedIntervention->type === InterventionType::GeneralConsultation
            && in_array($selectedIntervention->status, [
                InterventionStatus::AwaitingLab,
                InterventionStatus::AwaitingPharmacy,
                InterventionStatus::AwaitingCounselling,
                InterventionStatus::Completed,
            ], true);

        $canOrderLab = $selectedIntervention instanceof Intervention
            && $selectedIntervention->status === InterventionStatus::Pending;

        $availableNextActions = ConsultationNextAction::cases();
        if (! $canOrderLab) {
            $availableNextActions = array_values(array_filter(
                $availableNextActions,
                fn (ConsultationNextAction $a): bool => $a !== ConsultationNextAction::Lab
            ));
        }

        $partnerMedicationSuggestions = array_values(array_unique(array_filter(
            array_map(strval(...), config('partner_medication_suggestions.labels', [])),
            fn (string $label): bool => $label !== '',
        )));
        natcasesort($partnerMedicationSuggestions);
        $partnerMedicationSuggestions = array_values($partnerMedicationSuggestions);

        return view('livewire.stations.doctor-station', [
            'activeOutreach' => $activeOutreach,
            'queueInterventions' => $queueInterventions,
            'doneInterventions' => $doneInterventions,
            'selectedVisit' => $selectedVisit,
            'selectedIntervention' => $selectedIntervention,
            'canSave' => $canSave,
            'isDoneListIntervention' => $isDoneListIntervention,
            'canOrderLab' => $canOrderLab,
            'availableNextActions' => $availableNextActions,
            'partnerMedicationSuggestions' => $partnerMedicationSuggestions,
        ]);
    }

    private function resolveInterventionIdForVisit(string $visitId): ?string
    {
        $intervention = Intervention::query()
            ->where('visit_id', $visitId)
            ->where('type', InterventionType::GeneralConsultation)
            ->whereIn('status', [InterventionStatus::Pending, InterventionStatus::ConsultationReview])
            ->first();

        return $intervention?->getKey();
    }

    private function syncFormFromSelection(): void
    {
        if (! $this->selectedInterventionId) {
            $this->resetClinicalForms();

            return;
        }

        $intervention = Intervention::query()
            ->with(['consultation'])
            ->find($this->selectedInterventionId);

        if (! $intervention instanceof Intervention) {
            $this->resetClinicalForms();

            return;
        }

        $consultation = $intervention->consultation;
        if ($consultation) {
            $this->consultationForm = [
                'chief_complaint' => (string) $consultation->chief_complaint,
                'observations' => (string) ($consultation->observations ?? ''),
                'diagnosis' => (string) ($consultation->diagnosis ?? ''),
                'notes' => (string) ($consultation->notes ?? ''),
            ];
        } else {
            $this->consultationForm = [
                'chief_complaint' => '',
                'observations' => '',
                'diagnosis' => '',
                'notes' => '',
            ];
        }

        $this->nextAction = '';
        $this->labItems = [['test_name' => '', 'notes' => '']];
        $this->prescriptionItems = [['drug_name' => '', 'dosage' => '', 'frequency' => '', 'duration' => '', 'quantity' => '']];
    }

    private function resetClinicalForms(): void
    {
        $this->consultationForm = [
            'chief_complaint' => '',
            'observations' => '',
            'diagnosis' => '',
            'notes' => '',
        ];
        $this->nextAction = '';
        $this->labItems = [['test_name' => '', 'notes' => '']];
        $this->prescriptionItems = [['drug_name' => '', 'dosage' => '', 'frequency' => '', 'duration' => '', 'quantity' => '']];
    }
}
