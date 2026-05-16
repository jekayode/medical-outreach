<?php

namespace App\Livewire\Stations;

use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.app')]
abstract class StationPage extends Component
{
    public ?string $selectedVisitId = null;

    #[On('visit-selected')]
    public function onVisitSelected(string $visitId): void
    {
        $this->selectedVisitId = $visitId;
    }

    abstract protected function stationHeading(): string;
}
