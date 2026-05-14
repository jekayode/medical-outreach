<?php

namespace App\Livewire\Stations;

use App\Enums\InterventionStatus;
use App\Enums\InterventionType;
use App\Enums\OutreachStatus;
use App\Models\Intervention;
use App\Models\Outreach;
use App\Models\User;
use App\Models\Visit;
use App\Services\DentalExamRecordingService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;

class DentalCare extends StationPage
{
    public ?string $selectedInterventionId = null;

    /**
     * @var array<string, mixed>
     */
    public array $examForm = [
        'findings' => '',
        'treatment_performed' => '',
        'referral_needed' => false,
        'referral_notes' => '',
        'notes' => '',
    ];

    public ?string $successMessage = null;

    #[On('visit-selected')]
    public function onVisitSelected(string $visitId): void
    {
        $this->selectedVisitId = $visitId;
        $this->successMessage = null;
        $this->resetErrorBag();
        $this->selectedInterventionId = $this->resolveInterventionIdForVisit($visitId);
        $this->hydrateFormFromSelection();
    }

    public function selectQueueIntervention(string $interventionId): void
    {
        $this->successMessage = null;
        $this->resetErrorBag();
        $this->selectedInterventionId = $interventionId;
        $intervention = Intervention::query()->find($interventionId);
        $this->selectedVisitId = $intervention instanceof Intervention ? $intervention->visit_id : null;
        $this->hydrateFormFromSelection();
    }

    public function saveExam(DentalExamRecordingService $service): void
    {
        $this->resetErrorBag();
        $this->successMessage = null;

        $activeOutreach = Outreach::query()->where('status', OutreachStatus::Active)->first();
        if (! $activeOutreach instanceof Outreach) {
            $this->addError('form', __('There is no active outreach.'));

            return;
        }

        if (! $this->selectedInterventionId) {
            $this->addError('form', __('Select a patient from the dental clinic queue or search first.'));

            return;
        }

        $intervention = Intervention::query()->find($this->selectedInterventionId);
        if (! $intervention instanceof Intervention) {
            $this->addError('form', __('Intervention not found.'));

            return;
        }

        $this->validate([
            'examForm.findings' => ['required', 'string', 'max:10000'],
            'examForm.treatment_performed' => ['nullable', 'string', 'max:10000'],
            'examForm.referral_notes' => ['nullable', 'string', 'max:10000'],
            'examForm.notes' => ['nullable', 'string', 'max:10000'],
            'examForm.referral_needed' => ['boolean'],
        ]);

        $user = auth()->user();
        if (! $user instanceof User) {
            $this->addError('form', __('You must be signed in.'));

            return;
        }

        try {
            $service->record($intervention, $user, $activeOutreach, $this->examForm);
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        $this->selectedInterventionId = null;
        $this->selectedVisitId = null;
        $this->resetExamForm();
        $this->successMessage = __('Dental exam saved.');
    }

    protected function stationHeading(): string
    {
        return __('Dental care station');
    }

    public function render(): View
    {
        $activeOutreach = Outreach::query()->where('status', OutreachStatus::Active)->first();

        $queueInterventions = collect();
        if ($activeOutreach instanceof Outreach) {
            $queueInterventions = Intervention::query()
                ->where('interventions.type', InterventionType::DentalCare)
                ->where('interventions.status', InterventionStatus::Pending)
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
                ->with(['visit.beneficiary', 'visit.vitals', 'dentalExam'])
                ->find($this->selectedInterventionId);
        }

        $canRecord = $selectedIntervention instanceof Intervention
            && $selectedIntervention->type === InterventionType::DentalCare
            && $selectedIntervention->status === InterventionStatus::Pending;

        return view('livewire.stations.dental-care-station', [
            'activeOutreach' => $activeOutreach,
            'queueInterventions' => $queueInterventions,
            'selectedVisit' => $selectedVisit,
            'selectedIntervention' => $selectedIntervention,
            'canRecord' => $canRecord,
        ]);
    }

    private function resolveInterventionIdForVisit(string $visitId): ?string
    {
        $intervention = Intervention::query()
            ->where('visit_id', $visitId)
            ->where('type', InterventionType::DentalCare)
            ->where('status', InterventionStatus::Pending)
            ->orderBy('created_at')
            ->first();

        return $intervention?->getKey();
    }

    private function hydrateFormFromSelection(): void
    {
        $this->resetExamForm();

        if (! $this->selectedInterventionId) {
            return;
        }

        $intervention = Intervention::query()->with('dentalExam')->find($this->selectedInterventionId);
        if (! $intervention instanceof Intervention || ! $intervention->dentalExam) {
            return;
        }

        $e = $intervention->dentalExam;
        $this->examForm = [
            'findings' => (string) ($e->findings ?? ''),
            'treatment_performed' => (string) ($e->treatment_performed ?? ''),
            'referral_needed' => (bool) $e->referral_needed,
            'referral_notes' => (string) ($e->referral_notes ?? ''),
            'notes' => (string) ($e->notes ?? ''),
        ];
    }

    private function resetExamForm(): void
    {
        $this->examForm = [
            'findings' => '',
            'treatment_performed' => '',
            'referral_needed' => false,
            'referral_notes' => '',
            'notes' => '',
        ];
    }
}
