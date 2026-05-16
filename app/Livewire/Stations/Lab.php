<?php

namespace App\Livewire\Stations;

use App\Enums\InterventionStatus;
use App\Enums\InterventionType;
use App\Enums\LabOrderStatus;
use App\Enums\OutreachStatus;
use App\Models\Intervention;
use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\Outreach;
use App\Models\User;
use App\Models\Visit;
use App\Services\LabResultRecordingService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;

class Lab extends StationPage
{
    public ?string $selectedInterventionId = null;

    /**
     * lab_order_item id => result text
     *
     * @var array<string, string>
     */
    public array $itemResults = [];

    public string $rapidBloodGlucose = '';

    public string $labComment = '';

    public ?string $successMessage = null;

    #[On('visit-selected')]
    public function onVisitSelected(string $visitId): void
    {
        $this->selectedVisitId = $visitId;
        $this->successMessage = null;
        $this->rapidBloodGlucose = '';
        $this->labComment = '';
        $this->resetErrorBag();
        $this->selectedInterventionId = $this->resolveInterventionIdForVisit($visitId);
        $this->hydrateItemResultsFromSelection();
    }

    public function selectQueueIntervention(string $interventionId): void
    {
        $this->successMessage = null;
        $this->rapidBloodGlucose = '';
        $this->labComment = '';
        $this->resetErrorBag();
        $this->selectedInterventionId = $interventionId;
        $intervention = Intervention::query()->find($interventionId);
        $this->selectedVisitId = $intervention instanceof Intervention ? $intervention->visit_id : null;
        $this->hydrateItemResultsFromSelection();
    }

    public function saveResults(LabResultRecordingService $service): void
    {
        $this->resetErrorBag();
        $this->successMessage = null;

        $activeOutreach = Outreach::query()->where('status', OutreachStatus::Active)->first();
        if (! $activeOutreach instanceof Outreach) {
            $this->addError('form', __('There is no active outreach.'));

            return;
        }

        if (! $this->selectedInterventionId) {
            $this->addError('form', __('Select a patient from the lab queue or search first.'));

            return;
        }

        $intervention = Intervention::query()->find($this->selectedInterventionId);
        if (! $intervention instanceof Intervention) {
            $this->addError('form', __('Intervention not found.'));

            return;
        }

        $user = auth()->user();
        if (! $user instanceof User) {
            $this->addError('form', __('You must be signed in.'));

            return;
        }

        $comment = trim($this->labComment) !== '' ? trim($this->labComment) : null;

        try {
            $service->record($intervention, $user, $activeOutreach, $this->itemResults, $comment);
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        $this->selectedInterventionId = null;
        $this->selectedVisitId = null;
        $this->itemResults = [];
        $this->labComment = '';
        $this->successMessage = __('Lab results saved. Patient returned to the doctor queue.');
    }

    public function saveRapidTest(LabResultRecordingService $service): void
    {
        $this->resetErrorBag();
        $this->successMessage = null;

        $activeOutreach = Outreach::query()->where('status', OutreachStatus::Active)->first();
        if (! $activeOutreach instanceof Outreach) {
            $this->addError('form', __('There is no active outreach.'));

            return;
        }

        if (! $this->selectedInterventionId) {
            $this->addError('form', __('Select a patient from the lab queue or search first.'));

            return;
        }

        $intervention = Intervention::query()->find($this->selectedInterventionId);
        if (! $intervention instanceof Intervention) {
            $this->addError('form', __('Intervention not found.'));

            return;
        }

        $this->validate([
            'rapidBloodGlucose' => ['nullable', 'numeric', 'min:0'],
        ]);

        $user = auth()->user();
        if (! $user instanceof User) {
            $this->addError('form', __('You must be signed in.'));

            return;
        }

        $bloodGlucose = $this->rapidBloodGlucose !== '' ? (float) $this->rapidBloodGlucose : null;
        $comment = trim($this->labComment) !== '' ? trim($this->labComment) : null;

        try {
            $service->recordRapidBloodGlucose($intervention, $user, $activeOutreach, $bloodGlucose, $comment);
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        $this->selectedInterventionId = null;
        $this->selectedVisitId = null;
        $this->rapidBloodGlucose = '';
        $this->labComment = '';
        $this->successMessage = __('Rapid test recorded. Patient sent to the doctor queue.');
    }

    protected function stationHeading(): string
    {
        return __('Lab station');
    }

    public function render(): View
    {
        $activeOutreach = Outreach::query()->where('status', OutreachStatus::Active)->first();

        $queueInterventions = collect();
        if ($activeOutreach instanceof Outreach) {
            $queueInterventions = Intervention::query()
                ->where('interventions.type', InterventionType::GeneralConsultation)
                ->where('interventions.status', InterventionStatus::AwaitingLab)
                ->whereHas('visit', fn ($q) => $q->where('outreach_id', $activeOutreach->getKey()))
                ->join('visits', 'visits.id', '=', 'interventions.visit_id')
                ->orderBy('visits.checked_in_at')
                ->select('interventions.*')
                ->with(['visit.beneficiary'])
                ->limit(50)
                ->get();
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
                ->with([
                    'visit.beneficiary',
                    'visit.vitals',
                    'consultation.labOrders.items',
                ])
                ->find($this->selectedInterventionId);
        }

        $pendingLabItems = collect();
        if ($selectedIntervention instanceof Intervention) {
            $pendingLabItems = $this->pendingLabItems($selectedIntervention);
        }

        $isRapidTestMode = $selectedIntervention instanceof Intervention
            && $selectedIntervention->type === InterventionType::GeneralConsultation
            && $selectedIntervention->status === InterventionStatus::AwaitingLab
            && $selectedIntervention->consultation === null;

        $canRecordRapidTest = $isRapidTestMode;

        $canRecord = $selectedIntervention instanceof Intervention
            && $selectedIntervention->type === InterventionType::GeneralConsultation
            && $selectedIntervention->status === InterventionStatus::AwaitingLab
            && $selectedIntervention->consultation !== null
            && $pendingLabItems->isNotEmpty();

        return view('livewire.stations.lab-station', [
            'activeOutreach' => $activeOutreach,
            'queueInterventions' => $queueInterventions,
            'selectedVisit' => $selectedVisit,
            'selectedIntervention' => $selectedIntervention,
            'isRapidTestMode' => $isRapidTestMode,
            'canRecordRapidTest' => $canRecordRapidTest,
            'canRecord' => $canRecord,
            'pendingLabItems' => $pendingLabItems,
        ]);
    }

    private function resolveInterventionIdForVisit(string $visitId): ?string
    {
        $intervention = Intervention::query()
            ->where('visit_id', $visitId)
            ->where('type', InterventionType::GeneralConsultation)
            ->where('status', InterventionStatus::AwaitingLab)
            ->first();

        return $intervention?->getKey();
    }

    private function hydrateItemResultsFromSelection(): void
    {
        $this->itemResults = [];

        if (! $this->selectedInterventionId) {
            return;
        }

        $intervention = Intervention::query()
            ->with(['consultation.labOrders' => function ($q): void {
                $q->where('status', LabOrderStatus::Pending)->with('items');
            }])
            ->find($this->selectedInterventionId);

        if (! $intervention instanceof Intervention || ! $intervention->consultation) {
            return;
        }

        foreach ($intervention->consultation->labOrders as $order) {
            foreach ($order->items as $item) {
                $this->itemResults[$item->getKey()] = (string) ($item->result ?? '');
            }
        }
    }

    /**
     * @return Collection<int, LabOrderItem>
     */
    private function pendingLabItems(Intervention $intervention): Collection
    {
        $consultation = $intervention->consultation;
        if (! $consultation) {
            return collect();
        }

        return LabOrder::query()
            ->where('consultation_id', $consultation->getKey())
            ->where('status', LabOrderStatus::Pending)
            ->with('items')
            ->get()
            ->flatMap(fn (LabOrder $order): Collection => $order->items);
    }
}
