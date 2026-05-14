<?php

namespace App\Livewire\Stations;

use App\Enums\InterventionStatus;
use App\Enums\InterventionType;
use App\Enums\OutreachStatus;
use App\Models\Intervention;
use App\Models\Outreach;
use App\Models\User;
use App\Models\Visit;
use App\Services\EyeExamRecordingService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;

class EyeCare extends StationPage
{
    public ?string $selectedInterventionId = null;

    /**
     * @var array<string, mixed>
     */
    public array $examForm = [
        'visual_acuity_left' => '',
        'visual_acuity_right' => '',
        'findings' => '',
        'glasses_prescribed' => false,
        'glasses_prescription_details' => '',
        'drops_prescribed' => false,
        'referral_needed' => false,
        'referral_notes' => '',
        'notes' => '',
    ];

    /**
     * @var array<string, string>
     */
    public array $dropsRx = [
        'drug_name' => '',
        'dosage' => '',
        'frequency' => '',
        'duration' => '',
        'quantity' => '',
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

    public function saveExam(EyeExamRecordingService $service): void
    {
        $this->resetErrorBag();
        $this->successMessage = null;

        $activeOutreach = Outreach::query()->where('status', OutreachStatus::Active)->first();
        if (! $activeOutreach instanceof Outreach) {
            $this->addError('form', __('There is no active outreach.'));

            return;
        }

        if (! $this->selectedInterventionId) {
            $this->addError('form', __('Select a patient from the eye clinic queue or search first.'));

            return;
        }

        $intervention = Intervention::query()->find($this->selectedInterventionId);
        if (! $intervention instanceof Intervention) {
            $this->addError('form', __('Intervention not found.'));

            return;
        }

        $this->validate([
            'examForm.visual_acuity_left' => ['nullable', 'string', 'max:50'],
            'examForm.visual_acuity_right' => ['nullable', 'string', 'max:50'],
            'examForm.findings' => ['nullable', 'string', 'max:10000'],
            'examForm.glasses_prescription_details' => ['nullable', 'string', 'max:10000'],
            'examForm.referral_notes' => ['nullable', 'string', 'max:10000'],
            'examForm.notes' => ['nullable', 'string', 'max:10000'],
            'examForm.glasses_prescribed' => ['boolean'],
            'examForm.drops_prescribed' => ['boolean'],
            'examForm.referral_needed' => ['boolean'],
            'dropsRx.drug_name' => ['nullable', 'string', 'max:255'],
            'dropsRx.dosage' => ['nullable', 'string', 'max:255'],
            'dropsRx.frequency' => ['nullable', 'string', 'max:255'],
            'dropsRx.duration' => ['nullable', 'string', 'max:255'],
            'dropsRx.quantity' => ['nullable', 'string', 'max:10'],
        ]);

        $user = auth()->user();
        if (! $user instanceof User) {
            $this->addError('form', __('You must be signed in.'));

            return;
        }

        try {
            $service->record($intervention, $user, $activeOutreach, $this->examForm, $this->dropsRx);
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        $this->selectedInterventionId = null;
        $this->selectedVisitId = null;
        $this->resetExamForm();
        $this->successMessage = __('Eye exam saved.');
    }

    protected function stationHeading(): string
    {
        return __('Eye care station');
    }

    public function render(): View
    {
        $activeOutreach = Outreach::query()->where('status', OutreachStatus::Active)->first();

        $queueInterventions = collect();
        if ($activeOutreach instanceof Outreach) {
            $queueInterventions = Intervention::query()
                ->where('interventions.type', InterventionType::EyeCare)
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
                ->with(['visit.beneficiary', 'visit.vitals', 'eyeExam'])
                ->find($this->selectedInterventionId);
        }

        $canRecord = $selectedIntervention instanceof Intervention
            && $selectedIntervention->type === InterventionType::EyeCare
            && $selectedIntervention->status === InterventionStatus::Pending;

        return view('livewire.stations.eye-care-station', [
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
            ->where('type', InterventionType::EyeCare)
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

        $intervention = Intervention::query()->with('eyeExam')->find($this->selectedInterventionId);
        if (! $intervention instanceof Intervention || ! $intervention->eyeExam) {
            return;
        }

        $e = $intervention->eyeExam;
        $this->examForm = [
            'visual_acuity_left' => (string) ($e->visual_acuity_left ?? ''),
            'visual_acuity_right' => (string) ($e->visual_acuity_right ?? ''),
            'findings' => (string) ($e->findings ?? ''),
            'glasses_prescribed' => (bool) $e->glasses_prescribed,
            'glasses_prescription_details' => (string) ($e->glasses_prescription_details ?? ''),
            'drops_prescribed' => (bool) $e->drops_prescribed,
            'referral_needed' => (bool) $e->referral_needed,
            'referral_notes' => (string) ($e->referral_notes ?? ''),
            'notes' => (string) ($e->notes ?? ''),
        ];
    }

    private function resetExamForm(): void
    {
        $this->examForm = [
            'visual_acuity_left' => '',
            'visual_acuity_right' => '',
            'findings' => '',
            'glasses_prescribed' => false,
            'glasses_prescription_details' => '',
            'drops_prescribed' => false,
            'referral_needed' => false,
            'referral_notes' => '',
            'notes' => '',
        ];
        $this->dropsRx = [
            'drug_name' => '',
            'dosage' => '',
            'frequency' => '',
            'duration' => '',
            'quantity' => '',
        ];
    }
}
