<?php

namespace App\Livewire\Stations;

use App\Enums\AvailabilityStatus;
use App\Enums\DispensedStatus;
use App\Enums\InterventionStatus;
use App\Enums\OutreachStatus;
use App\Models\Intervention;
use App\Models\Outreach;
use App\Models\PrescriptionItem;
use App\Models\User;
use App\Models\Visit;
use App\Services\PharmacyDispenseService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;

class Pharmacy extends StationPage
{
    public ?string $selectedInterventionId = null;

    /**
     * prescription_item id => [ availability, dispensed_status ] value strings
     *
     * @var array<string, array{availability: string, dispensed_status: string}>
     */
    public array $itemDispense = [];

    public ?string $successMessage = null;

    #[On('visit-selected')]
    public function onVisitSelected(string $visitId): void
    {
        $this->selectedVisitId = $visitId;
        $this->successMessage = null;
        $this->resetErrorBag();
        $this->selectedInterventionId = $this->resolveInterventionIdForVisit($visitId);
        $this->hydrateItemDispenseFromSelection();
    }

    public function selectQueueIntervention(string $interventionId): void
    {
        $this->successMessage = null;
        $this->resetErrorBag();
        $this->selectedInterventionId = $interventionId;
        $intervention = Intervention::query()->find($interventionId);
        $this->selectedVisitId = $intervention instanceof Intervention ? $intervention->visit_id : null;
        $this->hydrateItemDispenseFromSelection();
    }

    public function saveDispense(PharmacyDispenseService $service, bool $referForCounselling = false): void
    {
        $this->resetErrorBag();
        $this->successMessage = null;

        $activeOutreach = Outreach::query()->where('status', OutreachStatus::Active)->first();
        if (! $activeOutreach instanceof Outreach) {
            $this->addError('form', __('There is no active outreach.'));

            return;
        }

        if (! $this->selectedInterventionId) {
            $this->addError('form', __('Select a patient from the pharmacy queue or search first.'));

            return;
        }

        $intervention = Intervention::query()->find($this->selectedInterventionId);
        if (! $intervention instanceof Intervention) {
            $this->addError('form', __('Intervention not found.'));

            return;
        }

        $this->validate([
            'itemDispense' => ['required', 'array'],
            'itemDispense.*.availability' => ['required', Rule::enum(AvailabilityStatus::class)],
            'itemDispense.*.dispensed_status' => ['required', Rule::enum(DispensedStatus::class)],
        ]);

        $user = auth()->user();
        if (! $user instanceof User) {
            $this->addError('form', __('You must be signed in.'));

            return;
        }

        try {
            $service->record($intervention, $user, $activeOutreach, $this->itemDispense, $referForCounselling);
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        $this->selectedInterventionId = null;
        $this->selectedVisitId = null;
        $this->itemDispense = [];

        $this->successMessage = $referForCounselling
            ? __('Dispense recorded. Patient referred to counselling.')
            : __('Dispense recorded. Visit complete.');
    }

    protected function stationHeading(): string
    {
        return __('Pharmacy station');
    }

    public function render(): View
    {
        $activeOutreach = Outreach::query()->where('status', OutreachStatus::Active)->first();

        $queueInterventions = collect();
        if ($activeOutreach instanceof Outreach) {
            $queueInterventions = Intervention::query()
                ->where('interventions.status', InterventionStatus::AwaitingPharmacy)
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
                    'prescriptions.items',
                ])
                ->find($this->selectedInterventionId);
        }

        $prescriptionItems = collect();
        if ($selectedIntervention instanceof Intervention) {
            $prescriptionItems = $this->prescriptionItemsForIntervention($selectedIntervention->getKey());
        }

        $canRecord = $selectedIntervention instanceof Intervention
            && $selectedIntervention->status === InterventionStatus::AwaitingPharmacy
            && $prescriptionItems->isNotEmpty();

        return view('livewire.stations.pharmacy-station', [
            'activeOutreach' => $activeOutreach,
            'queueInterventions' => $queueInterventions,
            'selectedVisit' => $selectedVisit,
            'selectedIntervention' => $selectedIntervention,
            'canRecord' => $canRecord,
            'prescriptionItems' => $prescriptionItems,
            'availabilityCases' => AvailabilityStatus::cases(),
            'dispensedCases' => DispensedStatus::cases(),
        ]);
    }

    private function resolveInterventionIdForVisit(string $visitId): ?string
    {
        $intervention = Intervention::query()
            ->where('visit_id', $visitId)
            ->where('status', InterventionStatus::AwaitingPharmacy)
            ->orderBy('created_at')
            ->first();

        return $intervention?->getKey();
    }

    private function hydrateItemDispenseFromSelection(): void
    {
        $this->itemDispense = [];

        if (! $this->selectedInterventionId) {
            return;
        }

        $items = $this->prescriptionItemsForIntervention($this->selectedInterventionId);

        foreach ($items as $item) {
            $availability = $item->availability instanceof AvailabilityStatus
                ? $item->availability->value
                : AvailabilityStatus::Available->value;

            $dispensed = $item->dispensed_status instanceof DispensedStatus
                ? $item->dispensed_status->value
                : DispensedStatus::Pending->value;

            $this->itemDispense[$item->getKey()] = [
                'availability' => $availability,
                'dispensed_status' => $dispensed,
            ];
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
            ->orderBy('prescription_id')
            ->orderBy('created_at')
            ->get();
    }
}
