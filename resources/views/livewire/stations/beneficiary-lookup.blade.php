@assets
@vite(['resources/js/beneficiary-qr.js'])
@endassets

<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-4 sm:p-6 border-b border-gray-100">
        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">{{ __('Beneficiary lookup') }}</h3>
        <p class="text-xs text-gray-500 mb-4">{{ __('Search by check-in code, QR slip, phone, or name for the active outreach.') }}</p>

        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <label for="station-beneficiary-search" class="sr-only">{{ __('Search') }}</label>
                <input
                    id="station-beneficiary-search"
                    type="search"
                    wire:model.live.debounce.400ms="search"
                    wire:keydown.enter="$wire.performSearch()"
                    autocomplete="off"
                    placeholder="{{ __('Code, phone, or name…') }}"
                    class="block w-full min-h-11 text-base rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary sm:text-sm"
                />
            </div>
            <div class="flex gap-2 shrink-0">
                <x-secondary-button type="button" wire:click="performSearch" class="justify-center">
                    {{ __('Search') }}
                </x-secondary-button>
                <x-secondary-button type="button" wire:click="openScanner" class="justify-center">
                    {{ __('Scan QR') }}
                </x-secondary-button>
            </div>
        </div>
    </div>

    @if ($scannerOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" wire:key="scanner-{{ $this->getId() }}">
            <div class="bg-white rounded-lg shadow-xl max-w-lg w-full p-4 space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-900">{{ __('Scan check-in QR') }}</span>
                    <button type="button" wire:click="closeScanner" class="text-gray-500 hover:text-gray-800 text-sm">
                        {{ __('Close') }}
                    </button>
                </div>
                <div wire:ignore id="qr-reader-{{ $this->getId() }}" class="w-full min-h-[240px] bg-black rounded-md overflow-hidden"></div>
                <p class="text-xs text-gray-500">{{ __('Allow camera access when prompted. The scanner stops after a successful read.') }}</p>
            </div>
        </div>
    @endif

    @if ($results !== [])
        <ul class="divide-y divide-gray-100 max-h-80 overflow-y-auto" role="list">
            @foreach ($results as $row)
                <li wire:key="visit-{{ $row['visit_id'] }}">
                    <button
                        type="button"
                        wire:click="selectVisit('{{ $row['visit_id'] }}')"
                        class="w-full min-h-11 text-left px-4 py-3 hover:bg-gray-50 focus:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-brand-primary transition
                            {{ $highlightedVisitId === $row['visit_id'] ? 'bg-brand-surface border-l-4 border-brand-primary' : '' }}"
                    >
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <span class="font-medium text-gray-900">{{ $row['full_name'] }}</span>
                            <span class="font-mono text-sm text-brand-primary">{{ $row['check_in_code'] }}</span>
                        </div>
                        <div class="mt-1 text-xs text-gray-600">
                            {{ $row['gender_label'] }}
                            @if ($row['age'] !== null)
                                · {{ __(':age yrs', ['age' => $row['age']]) }}
                            @endif
                        </div>
                        @if ($row['interventions'] !== [])
                            <div class="mt-2 flex flex-wrap gap-1">
                                @foreach ($row['interventions'] as $iv)
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-800">
                                        {{ $iv['type_label'] }}: {{ $iv['status_label'] }}
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <p class="mt-2 text-xs text-gray-400">{{ __('No interventions recorded yet.') }}</p>
                        @endif
                    </button>
                </li>
            @endforeach
        </ul>
    @elseif (strlen(trim($search)) >= 2)
        <div class="px-4 py-6 text-sm text-gray-500">
            {{ __('No matching visits for the active outreach.') }}
        </div>
    @endif
</div>
