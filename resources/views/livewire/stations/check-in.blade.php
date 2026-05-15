<div class="space-y-6 py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">{{ __('Check-in station') }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ __('Search pre-registered guests or checked-in visits, register walk-ins, and print check-in slips.') }}</p>
        </div>

        @if (! $activeOutreach)
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                {{ __('There is no active outreach. Ask an admin to mark an outreach as active in the admin panel.') }}
            </div>
        @else
            <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-2 text-sm text-gray-700">
                <span class="font-medium">{{ __('Active outreach:') }}</span>
                {{ $activeOutreach->name }}
                <span class="text-gray-500">({{ $activeOutreach->start_date->format('M j, Y') }})</span>
            </div>
        @endif

        <x-input-error :messages="$errors->get('check_in')" class="mt-0" />

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-4 sm:p-6 border-b border-gray-100 space-y-4">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">{{ __('Lookup') }}</h2>
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="flex-1">
                        <label for="check-in-search" class="sr-only">{{ __('Search') }}</label>
                        <input
                            id="check-in-search"
                            type="search"
                            wire:model.live.debounce.400ms="search"
                            wire:keydown.enter="$wire.performSearch()"
                            autocomplete="off"
                            placeholder="{{ __('Code, phone, or name…') }}"
                            class="block w-full min-h-11 text-base rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary sm:text-sm"
                            @disabled(! $activeOutreach)
                        />
                    </div>
                    <div class="flex gap-2 shrink-0">
                        @if ($activeOutreach)
                            <button
                                type="button"
                                wire:click="performSearch"
                                class="inline-flex items-center justify-center min-h-11 px-4 py-2 rounded-md font-semibold text-sm text-white uppercase tracking-wide bg-gray-800 border border-gray-700 shadow-sm hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                {{ __('Search') }}
                            </button>
                            <x-primary-button type="button" wire:click="openWalkIn" class="justify-center text-white">
                                {{ __('Walk-in registration') }}
                            </x-primary-button>
                        @endif
                    </div>
                </div>
            </div>

            @if ($results !== [])
                <ul class="divide-y divide-gray-100 max-h-96 overflow-y-auto" role="list">
                    @foreach ($results as $row)
                        <li wire:key="row-{{ $row['kind'] }}-{{ $row['beneficiary_id'] }}-{{ $row['visit_id'] ?? 'p' }}" class="px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div>
                                <div class="flex flex-wrap items-baseline gap-2">
                                    <span class="font-medium text-gray-900">{{ $row['full_name'] }}</span>
                                    @if ($row['kind'] === 'visit')
                                        @if (! empty($row['check_in_code']))
                                            <span class="font-mono text-sm text-brand-primary">{{ $row['check_in_code'] }}</span>
                                        @endif
                                        <span class="inline-flex rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-900">{{ __('Checked in') }}</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-900">{{ __('Pre-registered — not checked in') }}</span>
                                    @endif
                                </div>
                                <div class="mt-1 text-xs text-gray-600">
                                    {{ $row['gender_label'] }}
                                    @if (($row['age'] ?? null) !== null)
                                        · {{ __(':age yrs', ['age' => $row['age']]) }}
                                    @endif
                                    @if (! empty($row['phone']))
                                        · {{ $row['phone'] }}
                                    @endif
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2 shrink-0">
                                @if ($row['kind'] === 'pending')
                                    <button
                                        type="button"
                                        wire:click="checkInPending('{{ $row["beneficiary_id"] }}')"
                                        wire:confirm="{{ __('Create a visit for this person and open the check-in slip?') }}"
                                        wire:loading.attr="disabled"
                                        class="inline-flex items-center justify-center min-h-11 px-4 py-2 rounded-md font-semibold text-sm text-white uppercase tracking-wide bg-brand-primary hover:bg-brand-hover focus:bg-brand-hover active:bg-brand-active focus:outline-none focus:ring-2 focus:ring-brand-primary focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-25"
                                    >
                                        {{ __('Check in & issue slip') }}
                                    </button>
                                @else
                                    <button
                                        type="button"
                                        wire:click="reprintSlip('{{ $row["visit_id"] }}')"
                                        class="inline-flex items-center justify-center min-h-11 px-4 py-2 rounded-md font-semibold text-sm text-white uppercase tracking-wide bg-gray-800 border border-gray-700 shadow-sm hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        {{ __('Reprint slip') }}
                                    </button>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @elseif (strlen(trim($search)) >= 2 && $activeOutreach)
                <div class="px-4 py-6 text-sm text-gray-500">
                    {{ __('No matches.') }}
                </div>
            @endif
        </div>

        @if ($activeOutreach && $registryBeneficiaries)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 sm:p-6 border-b border-gray-100 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0 flex-1">
                        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">{{ __('Registered guests') }}</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ __('Pre-registered or walk-in records for this outreach. Filter by name or phone, then check in from the table.') }}</p>
                    </div>
                    <div class="w-full shrink-0 sm:w-72 sm:max-w-xs">
                        <label for="registry-filter" class="sr-only">{{ __('Filter list') }}</label>
                        <input
                            id="registry-filter"
                            type="search"
                            wire:model.live.debounce.400ms="registryFilter"
                            placeholder="{{ __('Filter by name or phone…') }}"
                            class="block w-full min-h-11 text-base rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary sm:text-sm"
                        />
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">{{ __('Name') }}</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">{{ __('Phone') }}</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide hidden md:table-cell">{{ __('Details') }}</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">{{ __('Status') }}</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wide">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($registryBeneficiaries as $beneficiary)
                                @php
                                    $visit = $beneficiary->visits->first();
                                @endphp
                                <tr wire:key="registry-row-{{ $beneficiary->getKey() }}">
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $beneficiary->full_name }}</td>
                                    <td class="px-4 py-3 text-gray-700 whitespace-nowrap">{{ $beneficiary->phone }}</td>
                                    <td class="px-4 py-3 text-gray-600 hidden md:table-cell">
                                        {{ \Illuminate\Support\Str::headline($beneficiary->gender->value) }}
                                        @if ($beneficiary->date_of_birth)
                                            <span class="text-gray-400">·</span> {{ __(':age yrs', ['age' => $beneficiary->date_of_birth->age]) }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($visit)
                                            <span class="font-mono text-brand-primary text-xs sm:text-sm">{{ $visit->check_in_code }}</span>
                                            <span class="ml-2 inline-flex rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-900">{{ __('Checked in') }}</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-900">{{ __('Not checked in') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        @if ($visit)
                                            <button
                                                type="button"
                                                wire:click="reprintSlip('{{ $visit->getKey() }}')"
                                                class="inline-flex items-center justify-center min-h-11 px-3 py-2 rounded-md text-sm font-semibold text-white bg-gray-800 border border-gray-700 shadow-sm hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                                            >
                                                {{ __('Reprint slip') }}
                                            </button>
                                        @else
                                            <button
                                                type="button"
                                                wire:click="checkInPending('{{ $beneficiary->getKey() }}')"
                                                wire:confirm="{{ __('Create a visit for this person and open the check-in slip?') }}"
                                                wire:loading.attr="disabled"
                                                class="inline-flex items-center justify-center min-h-11 px-3 py-2 rounded-md text-sm font-semibold text-white bg-brand-primary hover:bg-brand-hover focus:outline-none focus:ring-2 focus:ring-brand-primary focus:ring-offset-2 disabled:opacity-25"
                                            >
                                                {{ __('Check in & issue slip') }}
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-500">
                                        {{ __('No one is registered for this outreach yet. Import a spreadsheet from the admin panel, or use walk-in registration.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($registryBeneficiaries->hasPages())
                    <div class="px-4 py-3 border-t border-gray-100 bg-gray-50">
                        {{ $registryBeneficiaries->onEachSide(1)->links() }}
                    </div>
                @endif
            </div>
        @endif

        @if ($slipVisit && $qrSvg)
            <div
                id="check-in-slip-print"
                class="fixed inset-0 z-[60] flex items-center justify-center p-4 sm:p-6 print:static print:inset-auto print:p-0 print:block"
                role="dialog"
                aria-modal="true"
                aria-labelledby="check-in-slip-title"
            >
                <div class="absolute inset-0 bg-black/50 no-print" wire:click="clearSlip"></div>
                <div class="relative z-[1] w-full max-w-lg rounded-lg bg-white shadow-xl print:shadow-none print:max-w-none">
                    <div class="p-6 print:p-4">
                        <div class="flex justify-between items-start gap-4">
                            <div>
                                <h2 id="check-in-slip-title" class="text-lg font-semibold text-gray-900">{{ __('Check-in slip') }}</h2>
                                <p class="mt-2 text-2xl font-bold font-mono text-brand-ink">{{ $slipVisit->check_in_code }}</p>
                                <p class="mt-3 text-gray-900 font-medium">{{ $slipVisit->beneficiary->full_name }}</p>
                                <p class="mt-2 text-sm text-gray-600">{{ $slipVisit->outreach->name }}</p>
                                <p class="text-sm text-gray-500">{{ $slipVisit->outreach->start_date->format('l, F j, Y') }}</p>
                            </div>
                            <div class="shrink-0 bg-white p-2 border border-gray-200 rounded print:border-gray-400">
                                {!! $qrSvg !!}
                            </div>
                        </div>
                        <div class="mt-6 flex flex-wrap gap-3 no-print">
                            <button
                                type="button"
                                onclick="window.print()"
                                class="inline-flex items-center justify-center min-h-11 px-4 py-2 rounded-md font-semibold text-sm text-white uppercase tracking-wide bg-brand-primary hover:bg-brand-hover focus:outline-none focus:ring-2 focus:ring-brand-primary focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                {{ __('Print slip') }}
                            </button>
                            <button
                                type="button"
                                wire:click="clearSlip"
                                class="inline-flex items-center justify-center min-h-11 px-4 py-2 rounded-md font-semibold text-sm text-white uppercase tracking-wide bg-gray-800 border border-gray-700 shadow-sm hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                {{ __('Dismiss') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @if ($walkInModalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 no-print" wire:key="walk-in-modal">
            <div class="bg-white rounded-lg shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto p-6 space-y-4">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-medium text-gray-900">{{ __('Walk-in registration') }}</h3>
                    <button type="button" wire:click="closeWalkIn" class="min-h-11 px-2 text-sm text-gray-500 hover:text-gray-800">{{ __('Close') }}</button>
                </div>

                <x-input-error :messages="$errors->get('walkIn')" class="mt-0" />

                <form
                    wire:submit="saveWalkIn"
                    wire:confirm="{{ __('Register this walk-in, create their visit, and open the check-in slip?') }}"
                    class="space-y-4"
                >
                    <div>
                        <x-input-label for="wi-name" :value="__('Full name')" />
                        <x-text-input id="wi-name" class="block mt-1 w-full" type="text" wire:model="walkIn.full_name" required />
                        <x-input-error :messages="$errors->get('walkIn.full_name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="wi-gender" :value="__('Gender')" />
                        <select id="wi-gender" wire:model="walkIn.gender" class="mt-1 block w-full min-h-11 text-base rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary sm:text-sm" required>
                            <option value="">{{ __('Select…') }}</option>
                            @foreach (\App\Enums\Gender::cases() as $g)
                                <option value="{{ $g->value }}">{{ \Illuminate\Support\Str::headline($g->value) }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('walkIn.gender')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="wi-dob" :value="__('Date of birth')" />
                        <x-text-input id="wi-dob" class="block mt-1 w-full" type="date" wire:model="walkIn.date_of_birth" required />
                        <x-input-error :messages="$errors->get('walkIn.date_of_birth')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="wi-phone" :value="__('Phone')" />
                        <x-text-input id="wi-phone" class="block mt-1 w-full" type="text" wire:model="walkIn.phone" required />
                        <x-input-error :messages="$errors->get('walkIn.phone')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="wi-address" :value="__('Residential address')" />
                        <textarea id="wi-address" wire:model="walkIn.residential_address" rows="3" class="mt-1 block w-full text-base rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary sm:text-sm" required></textarea>
                        <x-input-error :messages="$errors->get('walkIn.residential_address')" class="mt-2" />
                    </div>
                    <div class="flex items-start gap-2 min-h-11">
                        <input id="wi-consent" type="checkbox" wire:model="walkIn.medical_consent" class="rounded border-gray-300 text-brand-primary shadow-sm focus:ring-brand-primary mt-1" />
                        <label for="wi-consent" class="text-sm text-gray-700">{{ __('The person has given medical consent to be seen at this outreach.') }}</label>
                    </div>
                    <x-input-error :messages="$errors->get('walkIn.medical_consent')" class="mt-0" />

                    <div class="flex justify-end gap-2 pt-2">
                        <button
                            type="button"
                            wire:click="closeWalkIn"
                            class="inline-flex items-center justify-center min-h-11 px-4 py-2 rounded-md font-semibold text-sm text-white uppercase tracking-wide bg-gray-800 border border-gray-700 shadow-sm hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            {{ __('Cancel') }}
                        </button>
                        <x-primary-button type="submit" class="text-white">{{ __('Register & check in') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            #check-in-slip-print,
            #check-in-slip-print * {
                visibility: visible !important;
            }
            #check-in-slip-print {
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                min-height: 100% !important;
                display: block !important;
                background: #fff !important;
                padding: 0 !important;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</div>
