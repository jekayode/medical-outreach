<?php

namespace App\Livewire\Stations;

use App\Enums\Gender;
use App\Enums\OutreachStatus;
use App\Models\Beneficiary;
use App\Models\Outreach;
use App\Models\Visit;
use App\Services\CheckInSearchService;
use App\Services\VisitCheckInService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

#[Layout('layouts.app')]
class CheckIn extends Component
{
    use WithPagination;

    public string $search = '';

    public string $registryFilter = '';

    /** @var list<array<string, mixed>> */
    public array $results = [];

    public bool $walkInModalOpen = false;

    /**
     * @var array{full_name: string, gender: string, date_of_birth: string, phone: string, residential_address: string, medical_consent: bool}
     */
    public array $walkIn = [
        'full_name' => '',
        'gender' => '',
        'date_of_birth' => '',
        'phone' => '',
        'residential_address' => '',
        'medical_consent' => false,
    ];

    public ?string $slipVisitId = null;

    public function updatedSearch(): void
    {
        if (strlen(trim($this->search)) < 2) {
            $this->results = [];

            return;
        }

        $this->performSearch();
    }

    public function updatedRegistryFilter(): void
    {
        $this->resetPage('registryPage');
    }

    public function performSearch(): void
    {
        /** @var CheckInSearchService $svc */
        $svc = app(CheckInSearchService::class);

        $outreach = Outreach::query()->where('status', OutreachStatus::Active)->first();
        if (! $outreach || trim($this->search) === '') {
            $this->results = [];

            return;
        }

        $this->results = $svc->search($outreach, $this->search)->all();
    }

    public function checkInPending(string $beneficiaryId): void
    {
        $this->resetErrorBag();

        $outreach = Outreach::query()->where('status', OutreachStatus::Active)->first();
        $user = auth()->user();
        if (! $outreach || ! $user) {
            $this->addError('check_in', __('No active outreach or you are not signed in.'));

            return;
        }

        $beneficiary = Beneficiary::query()->findOrFail($beneficiaryId);

        try {
            /** @var VisitCheckInService $checkIn */
            $checkIn = app(VisitCheckInService::class);
            $visit = $checkIn->checkInBeneficiary($outreach, $beneficiary, $user);
            $this->slipVisitId = $visit->getKey();
            $this->search = '';
            $this->results = [];
            $this->resetPage('registryPage');
        } catch (ValidationException $e) {
            $this->addError('check_in', (string) Collection::make($e->errors())->flatten()->first());
        }
    }

    public function reprintSlip(string $visitId): void
    {
        $this->slipVisitId = $visitId;
    }

    public function clearSlip(): void
    {
        $this->slipVisitId = null;
    }

    public function openWalkIn(): void
    {
        $this->resetErrorBag();
        $this->walkInModalOpen = true;
    }

    public function closeWalkIn(): void
    {
        $this->walkInModalOpen = false;
    }

    public function saveWalkIn(): void
    {
        $this->resetErrorBag();

        $this->validate([
            'walkIn.full_name' => ['required', 'string', 'max:255'],
            'walkIn.gender' => ['required', Rule::enum(Gender::class)],
            'walkIn.date_of_birth' => ['required', 'date'],
            'walkIn.phone' => ['required', 'string', 'max:50'],
            'walkIn.residential_address' => ['required', 'string', 'max:500'],
            'walkIn.medical_consent' => ['accepted'],
        ]);

        $outreach = Outreach::query()->where('status', OutreachStatus::Active)->first();
        $user = auth()->user();
        if (! $outreach || ! $user) {
            $this->addError('walkIn', __('No active outreach or you are not signed in.'));

            return;
        }

        try {
            /** @var VisitCheckInService $checkIn */
            $checkIn = app(VisitCheckInService::class);
            $visit = $checkIn->registerWalkInAndCheckIn($outreach, $this->walkIn, $user);
            $this->slipVisitId = $visit->getKey();
            $this->walkInModalOpen = false;
            $this->resetPage('registryPage');
            $this->walkIn = [
                'full_name' => '',
                'gender' => '',
                'date_of_birth' => '',
                'phone' => '',
                'residential_address' => '',
                'medical_consent' => false,
            ];
        } catch (ValidationException $e) {
            $this->addError('walkIn', (string) Collection::make($e->errors())->flatten()->first());
        }
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $activeOutreach = Outreach::query()->where('status', OutreachStatus::Active)->first();

        $slipVisit = $this->slipVisitId
            ? Visit::query()->with(['beneficiary', 'outreach'])->find($this->slipVisitId)
            : null;

        $qrSvg = null;
        if ($slipVisit instanceof Visit) {
            $qrSvg = QrCode::format('svg')->size(180)->margin(1)->generate($slipVisit->check_in_code);
        }

        return view('livewire.stations.check-in', [
            'activeOutreach' => $activeOutreach,
            'slipVisit' => $slipVisit,
            'qrSvg' => $qrSvg,
            'registryBeneficiaries' => $this->registryBeneficiaryPaginator($activeOutreach),
        ]);
    }

    private function registryBeneficiaryPaginator(?Outreach $outreach): ?LengthAwarePaginator
    {
        if (! $outreach instanceof Outreach) {
            return null;
        }

        $term = trim($this->registryFilter);

        return Beneficiary::query()
            ->whereHas('registeredOutreaches', function (Builder $q) use ($outreach): void {
                $q->where('outreaches.id', $outreach->getKey());
            })
            ->with(['visits' => function ($q) use ($outreach): void {
                $q->where('outreach_id', $outreach->getKey());
            }])
            ->when($term !== '', function (Builder $q) use ($term): void {
                $digits = preg_replace('/\D+/', '', $term) ?? '';
                if ($digits !== '' && strlen($digits) >= 3) {
                    $q->where('phone', 'like', '%'.$digits.'%');

                    return;
                }
                $escaped = addcslashes($term, '%_\\');
                $q->whereRaw('LOWER(full_name) LIKE ?', ['%'.mb_strtolower($escaped, 'UTF-8').'%']);
            })
            ->orderBy('full_name')
            ->paginate(perPage: 15, pageName: 'registryPage');
    }
}
