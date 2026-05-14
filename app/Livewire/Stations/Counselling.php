<?php

namespace App\Livewire\Stations;

use App\Enums\CounsellingType;
use App\Enums\InterventionStatus;
use App\Enums\OutreachStatus;
use App\Models\Outreach;
use App\Models\User;
use App\Models\Visit;
use App\Services\CounsellingSessionRecordingService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;

class Counselling extends StationPage
{
    /**
     * @var array<int, string>
     */
    public array $sessionFormTypes = [];

    public string $sessionFormNotes = '';

    public ?string $successMessage = null;

    #[On('visit-selected')]
    public function onVisitSelected(string $visitId): void
    {
        $this->selectedVisitId = $visitId;
        $this->successMessage = null;
        $this->resetErrorBag();
        $this->resetSessionForm();
    }

    public function selectQueueVisit(string $visitId): void
    {
        $this->selectedVisitId = $visitId;
        $this->successMessage = null;
        $this->resetErrorBag();
        $this->resetSessionForm();
    }

    public function saveSession(CounsellingSessionRecordingService $service): void
    {
        $this->resetErrorBag();
        $this->successMessage = null;

        $activeOutreach = Outreach::query()->where('status', OutreachStatus::Active)->first();
        if (! $activeOutreach instanceof Outreach) {
            $this->addError('form', __('There is no active outreach.'));

            return;
        }

        if (! $this->selectedVisitId) {
            $this->addError('form', __('Select a patient from the counselling queue or search first.'));

            return;
        }

        $visit = Visit::query()->find($this->selectedVisitId);
        if (! $visit instanceof Visit) {
            $this->addError('form', __('Visit not found.'));

            return;
        }

        $this->validate([
            'sessionFormNotes' => ['nullable', 'string', 'max:10000'],
        ]);

        $user = auth()->user();
        if (! $user instanceof User) {
            $this->addError('form', __('You must be signed in.'));

            return;
        }

        try {
            $service->record($visit, $user, $activeOutreach, [
                'types' => $this->sessionFormTypes,
                'notes' => $this->sessionFormNotes,
            ]);
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        $this->selectedVisitId = null;
        $this->resetSessionForm();
        $this->successMessage = __('Counselling session saved. Visit completed.');
    }

    protected function stationHeading(): string
    {
        return __('Counselling station');
    }

    public function render(): View
    {
        $activeOutreach = Outreach::query()->where('status', OutreachStatus::Active)->first();

        $queueVisits = collect();
        if ($activeOutreach instanceof Outreach) {
            $queueVisits = Visit::query()
                ->where('outreach_id', $activeOutreach->getKey())
                ->whereDoesntHave('counsellingSession')
                ->whereHas('interventions', fn ($q) => $q->where('status', InterventionStatus::AwaitingCounselling))
                ->orderBy('checked_in_at')
                ->with(['beneficiary'])
                ->limit(50)
                ->get();
        }

        $selectedVisit = null;
        if ($this->selectedVisitId) {
            $selectedVisit = Visit::query()
                ->with(['beneficiary', 'vitals', 'interventions', 'counsellingSession'])
                ->find($this->selectedVisitId);
        }

        $canRecord = $this->resolveCanRecord($selectedVisit);

        return view('livewire.stations.counselling-station', [
            'activeOutreach' => $activeOutreach,
            'queueVisits' => $queueVisits,
            'selectedVisit' => $selectedVisit,
            'canRecord' => $canRecord,
            'counsellingTypeCases' => CounsellingType::cases(),
        ]);
    }

    private function resetSessionForm(): void
    {
        $this->sessionFormTypes = [];
        $this->sessionFormNotes = '';
    }

    private function resolveCanRecord(?Visit $visit): bool
    {
        if (! $visit instanceof Visit) {
            return false;
        }

        if ($visit->counsellingSession !== null) {
            return false;
        }

        $interventions = $visit->interventions;

        if ($interventions->isEmpty()) {
            return false;
        }

        $hasAwaitingCounselling = false;

        foreach ($interventions as $intervention) {
            $status = $intervention->status;

            if ($status === InterventionStatus::AwaitingCounselling) {
                $hasAwaitingCounselling = true;

                continue;
            }

            if ($status === InterventionStatus::Completed) {
                continue;
            }

            return false;
        }

        return $hasAwaitingCounselling;
    }
}
