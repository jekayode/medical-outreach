<?php

namespace App\Livewire\Stations;

use App\Enums\HivStatus;
use App\Enums\InterventionType;
use App\Enums\OutreachStatus;
use App\Enums\VisitStage;
use App\Models\Outreach;
use App\Models\User;
use App\Models\Visit;
use App\Services\VitalsRecordingService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;

class Vitals extends StationPage
{
    /**
     * @var array<string, mixed>
     */
    public array $form = [
        'blood_pressure_systolic' => '',
        'blood_pressure_diastolic' => '',
        'pulse' => '',
        'temperature' => '',
        'weight_kg' => '',
        'height_cm' => '',
        'blood_glucose' => '',
        'hiv_status' => '',
        'notes' => '',
    ];

    /** @var list<string> */
    public array $interventionSelections = [];

    public ?string $successMessage = null;

    public function updatedSelectedVisitId(?string $value): void
    {
        $this->resetErrorBag();
        $this->successMessage = null;
        $this->resetFormDefaults();
    }

    public function selectQueueVisit(string $visitId): void
    {
        $this->selectedVisitId = $visitId;
    }

    public function saveVitals(VitalsRecordingService $recording): void
    {
        $this->resetErrorBag();
        $this->successMessage = null;

        $activeOutreach = Outreach::query()->where('status', OutreachStatus::Active)->first();
        if (! $activeOutreach) {
            $this->addError('form', __('There is no active outreach.'));

            return;
        }

        if (! $this->selectedVisitId) {
            $this->addError('form', __('Select a visit first.'));

            return;
        }

        $visit = Visit::query()->find($this->selectedVisitId);
        if (! $visit) {
            $this->addError('form', __('Visit not found.'));

            return;
        }

        $this->validate([
            'form.pulse' => ['required', 'integer', 'between:30,250'],
            'form.temperature' => ['required', 'numeric', 'between:30,45'],
            'form.weight_kg' => ['required', 'numeric', 'between:1,400'],
            'form.height_cm' => ['required', 'numeric', 'between:30,272'],
            'form.blood_pressure_systolic' => ['nullable', 'integer', 'between:50,300'],
            'form.blood_pressure_diastolic' => ['nullable', 'integer', 'between:30,200'],
            'form.blood_glucose' => ['nullable', 'numeric', 'min:0'],
            'form.hiv_status' => ['nullable', Rule::enum(HivStatus::class)],
            'form.notes' => ['nullable', 'string', 'max:2000'],
            'interventionSelections' => ['required', 'array', 'min:1'],
            'interventionSelections.*' => ['required', Rule::enum(InterventionType::class)],
        ]);

        $user = auth()->user();
        if (! $user instanceof User) {
            $this->addError('form', __('You must be signed in.'));

            return;
        }

        try {
            $recording->record(
                $visit,
                $user,
                $activeOutreach,
                $this->form,
                $this->interventionSelections,
            );
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        $this->selectedVisitId = null;
        $this->resetFormDefaults();
        $this->successMessage = __('Vitals saved and service lines queued for this visit.');
    }

    #[Computed]
    public function estimatedBmi(): ?float
    {
        $w = $this->form['weight_kg'] ?? '';
        $h = $this->form['height_cm'] ?? '';
        if ($w === '' || $h === '') {
            return null;
        }

        $weight = (float) $w;
        $heightCm = (float) $h;
        if ($heightCm <= 0.0) {
            return null;
        }

        $heightM = $heightCm / 100;

        return round($weight / ($heightM * $heightM), 1);
    }

    protected function stationHeading(): string
    {
        return __('Vitals station');
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $activeOutreach = Outreach::query()->where('status', OutreachStatus::Active)->first();

        $queueVisits = collect();
        if ($activeOutreach instanceof Outreach) {
            $queueVisits = Visit::query()
                ->awaitingVitals($activeOutreach)
                ->with(['beneficiary'])
                ->orderBy('checked_in_at')
                ->limit(50)
                ->get();
        }

        $selectedVisit = null;
        if ($this->selectedVisitId) {
            $selectedVisit = Visit::query()
                ->with(['beneficiary', 'outreach', 'vitals'])
                ->find($this->selectedVisitId);
        }

        $canRecordVitals = $selectedVisit instanceof Visit
            && $selectedVisit->current_stage === VisitStage::CheckedIn
            && $selectedVisit->vitals === null;

        return view('livewire.stations.vitals-station', [
            'activeOutreach' => $activeOutreach,
            'queueVisits' => $queueVisits,
            'selectedVisit' => $selectedVisit,
            'canRecordVitals' => $canRecordVitals,
        ]);
    }

    private function resetFormDefaults(): void
    {
        $this->form = [
            'blood_pressure_systolic' => '',
            'blood_pressure_diastolic' => '',
            'pulse' => '',
            'temperature' => '',
            'weight_kg' => '',
            'height_cm' => '',
            'blood_glucose' => '',
            'hiv_status' => '',
            'notes' => '',
        ];
        $this->interventionSelections = [];
    }
}
