<?php

namespace App\Livewire\Stations;

use App\Enums\OutreachStatus;
use App\Models\Outreach;
use App\Services\StationBeneficiarySearch;
use Livewire\Component;

class BeneficiaryLookup extends Component
{
    public string $search = '';

    /** @var list<array<string, mixed>> */
    public array $results = [];

    public bool $scannerOpen = false;

    public ?string $highlightedVisitId = null;

    public function updatedSearch(): void
    {
        if (strlen(trim($this->search)) < 2) {
            $this->results = [];

            return;
        }

        $this->performSearch();
    }

    public function performSearch(): void
    {
        /** @var StationBeneficiarySearch $stationSearch */
        $stationSearch = app(StationBeneficiarySearch::class);

        $outreach = Outreach::query()->where('status', OutreachStatus::Active)->first();
        if (! $outreach || trim($this->search) === '') {
            $this->results = [];

            return;
        }

        $this->results = $stationSearch->search($outreach, $this->search)->values()->all();
    }

    public function selectVisit(string $visitId): void
    {
        $this->highlightedVisitId = $visitId;
        $this->dispatch('visit-selected', visitId: $visitId);
    }

    public function closeScanner(): void
    {
        $this->scannerOpen = false;
    }

    public function openScanner(): void
    {
        $this->scannerOpen = true;
    }

    public function updatedScannerOpen(bool $value): void
    {
        if (! $value) {
            $this->js('window.MedicalOutreachQr && window.MedicalOutreachQr.stop()');

            return;
        }

        $elementId = 'qr-reader-'.$this->getId();

        $this->js(<<<JS
            setTimeout(async () => {
                if (! window.MedicalOutreachQr) {
                    return;
                }
                try {
                    await window.MedicalOutreachQr.start('{$elementId}', function (text) {
                        \$wire.set('search', text);
                        \$wire.call('performSearch');
                        \$wire.set('scannerOpen', false);
                    });
                } catch (e) {
                    console.error(e);
                    \$wire.set('scannerOpen', false);
                }
            }, 250);
            JS
        );
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.stations.beneficiary-lookup');
    }
}
