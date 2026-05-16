<div class="space-y-6 py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">{{ __('Pharmacy station') }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ __('Confirm stock availability and dispensed status for each prescribed line, then save. This completes only the intervention you are dispensing; eye care and dental care are separate intervention lines on the same visit.') }}</p>
        </div>

        @if ($successMessage)
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-900" role="status">
                {{ $successMessage }}
            </div>
        @endif

        @if (! $activeOutreach)
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 mb-6">
                {{ __('There is no active outreach. Ask an admin to mark an outreach as active.') }}
            </div>
        @else
            <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-2 text-sm text-gray-700 mb-6">
                <span class="font-medium">{{ __('Active outreach:') }}</span>
                {{ $activeOutreach->name }}
            </div>
        @endif

        <livewire:stations.beneficiary-lookup />

        <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-4">
            <aside class="lg:col-span-1 space-y-3" wire:poll.10s>
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">{{ __('Awaiting pharmacy') }}</h2>
                @if ($queueInterventions->isEmpty())
                    <p class="text-sm text-gray-500">{{ __('No patients in the pharmacy queue.') }}</p>
                @else
                    <ul class="space-y-2" role="list">
                        @foreach ($queueInterventions as $qi)
                            <li wire:key="rx-q-{{ $qi->getKey() }}">
                                <button
                                    type="button"
                                    wire:click="selectQueueIntervention('{{ $qi->getKey() }}')"
                                    @class([
                                        'w-full min-h-11 text-left rounded-lg border px-3 py-3 text-sm transition shadow-sm',
                                        'border-brand-primary bg-brand-surface ring-1 ring-brand-primary' => $selectedInterventionId === $qi->getKey(),
                                        'border-gray-200 bg-white hover:border-gray-300' => $selectedInterventionId !== $qi->getKey(),
                                    ])
                                >
                                    <div class="font-medium text-gray-900">{{ $qi->visit->beneficiary->full_name }}</div>
                                    <div class="mt-1 font-mono text-xs text-brand-primary">{{ $qi->visit->check_in_code }}</div>
                                    <div class="mt-1 text-xs text-gray-600">{{ \Illuminate\Support\Str::headline($qi->type->value) }}</div>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </aside>

            <section class="lg:col-span-3 space-y-6">
                @if (! $selectedVisit)
                    <div class="rounded-lg border border-dashed border-gray-300 bg-white p-8 text-center text-gray-500 text-sm">
                        {{ __('Select a patient from the queue or search above.') }}
                    </div>
                @else
                    <div class="rounded-lg border border-gray-200 bg-white p-4 sm:p-6 shadow-sm space-y-6">
                        <div class="flex flex-wrap justify-between gap-2 border-b border-gray-100 pb-4">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900">{{ $selectedVisit->beneficiary->full_name }}</h2>
                                <p class="text-sm text-gray-600 mt-1">
                                    <span class="font-mono text-brand-primary">{{ $selectedVisit->check_in_code }}</span>
                                    <span class="mx-2">·</span>
                                    {{ $selectedVisit->beneficiary->gender->value }}
                                    @if ($selectedVisit->beneficiary->date_of_birth)
                                        <span class="mx-2">·</span>
                                        {{ __(':age yrs', ['age' => $selectedVisit->beneficiary->date_of_birth->age]) }}
                                    @endif
                                </p>
                            </div>
                            <span class="self-start inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-800">
                                {{ \Illuminate\Support\Str::headline($selectedVisit->current_stage->value) }}
                            </span>
                        </div>

                        @if (! $selectedIntervention)
                            <div class="rounded-md bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900">
                                {{ __('This visit has no service line awaiting pharmacy (or it was already processed).') }}
                            </div>
                        @else
                            <div>
                                <h3 class="text-sm font-semibold text-gray-800 mb-2">{{ __('Vitals') }}</h3>
                                @if (! $selectedVisit->vitals)
                                    <p class="text-sm text-gray-500">{{ __('No vitals recorded for this visit.') }}</p>
                                @else
                                    @php($v = $selectedVisit->vitals)
                                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-sm text-gray-700">
                                        @if ($v->blood_pressure_systolic || $v->blood_pressure_diastolic)
                                            <div><span class="text-gray-500">{{ __('BP') }}</span> {{ $v->blood_pressure_systolic }}/{{ $v->blood_pressure_diastolic }}</div>
                                        @endif
                                        @if ($v->pulse)
                                            <div><span class="text-gray-500">{{ __('Pulse') }}</span> {{ $v->pulse }}</div>
                                        @endif
                                        @if ($v->temperature)
                                            <div><span class="text-gray-500">{{ __('Temp °C') }}</span> {{ $v->temperature }}</div>
                                        @endif
                                        @if ($v->weight_kg)
                                            <div><span class="text-gray-500">{{ __('Weight kg') }}</span> {{ $v->weight_kg }}</div>
                                        @endif
                                        @if ($v->height_cm)
                                            <div><span class="text-gray-500">{{ __('Height cm') }}</span> {{ $v->height_cm }}</div>
                                        @endif
                                        @if ($v->bmi)
                                            <div><span class="text-gray-500">{{ __('BMI') }}</span> {{ $v->bmi }}</div>
                                        @endif
                                        @if ($v->blood_glucose)
                                            <div><span class="text-gray-500">{{ __('Glucose') }}</span> {{ $v->blood_glucose }}</div>
                                        @endif
                                        @if ($v->hiv_status)
                                            <div><span class="text-gray-500">{{ __('HIV') }}</span> {{ \Illuminate\Support\Str::headline($v->hiv_status->value) }}</div>
                                        @endif
                                        @if ($v->notes)
                                            <div class="sm:col-span-2"><span class="text-gray-500">{{ __('Vitals notes') }}</span> {{ $v->notes }}</div>
                                        @endif
                                    </dl>
                                @endif
                            </div>

                            @if (! $canRecord)
                                <div class="rounded-md bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900">
                                    {{ __('There are no prescription lines to process for this selection.') }}
                                </div>
                            @else
                                <form class="space-y-4">
                                    @error('form')
                                        <div class="text-sm text-red-600">{{ $message }}</div>
                                    @enderror

                                    <h3 class="text-sm font-semibold text-gray-800">{{ __('Prescription lines') }}</h3>
                                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Drug') }}</th>
                                                    <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Dosage') }}</th>
                                                    <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Frequency') }}</th>
                                                    <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Duration') }}</th>
                                                    <th class="px-3 py-2 text-right font-medium text-gray-600">{{ __('Qty') }}</th>
                                                    <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Availability') }}</th>
                                                    <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Dispensed') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100 bg-white">
                                                @foreach ($prescriptionItems as $row)
                                                    <tr wire:key="rx-item-{{ $row->getKey() }}">
                                                        <td class="px-3 py-2 font-medium text-gray-900">{{ $row->drug_name }}</td>
                                                        <td class="px-3 py-2 text-gray-700">{{ $row->dosage }}</td>
                                                        <td class="px-3 py-2 text-gray-700">{{ $row->frequency }}</td>
                                                        <td class="px-3 py-2 text-gray-700">{{ $row->duration }}</td>
                                                        <td class="px-3 py-2 text-right text-gray-700">{{ $row->quantity }}</td>
                                                        <td class="px-3 py-2">
                                                            <select
                                                                wire:model="itemDispense.{{ $row->getKey() }}.availability"
                                                                class="block w-full min-w-[8rem] rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary sm:text-sm"
                                                            >
                                                                @foreach ($availabilityCases as $av)
                                                                    <option value="{{ $av->value }}">{{ \Illuminate\Support\Str::headline($av->value) }}</option>
                                                                @endforeach
                                                            </select>
                                                            @error('itemDispense.' . $row->getKey() . '.availability')
                                                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                                            @enderror
                                                        </td>
                                                        <td class="px-3 py-2">
                                                            <select
                                                                wire:model="itemDispense.{{ $row->getKey() }}.dispensed_status"
                                                                class="block w-full min-w-[8rem] rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary sm:text-sm"
                                                            >
                                                                @foreach ($dispensedCases as $ds)
                                                                    <option value="{{ $ds->value }}">{{ \Illuminate\Support\Str::headline(str_replace('_', ' ', $ds->value)) }}</option>
                                                                @endforeach
                                                            </select>
                                                            @error('itemDispense.' . $row->getKey() . '.dispensed_status')
                                                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                                            @enderror
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    <div
                                        class="sticky bottom-0 z-10 -mx-4 mt-6 flex flex-wrap justify-end gap-3 border-t border-gray-200 bg-white px-4 py-4 shadow-[0_-8px_24px_rgba(15,23,42,0.06)] sm:-mx-6 sm:px-6"
                                    >
                                        <button
                                            type="button"
                                            wire:click="saveDispense(false)"
                                            wire:confirm="{{ __('Save dispense and complete this visit now?') }}"
                                            wire:loading.attr="disabled"
                                            class="inline-flex items-center justify-center min-h-11 px-4 py-2 rounded-md font-semibold text-sm text-white uppercase tracking-wide bg-gray-800 border border-gray-700 shadow-sm hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-25"
                                        >
                                            {{ __('Done — no counselling') }}
                                        </button>
                                        <x-primary-button
                                            type="button"
                                            wire:click="saveDispense(true)"
                                            wire:confirm="{{ __('Save dispense and refer this patient to counselling?') }}"
                                            wire:loading.attr="disabled"
                                            class="px-6"
                                        >
                                            {{ __('Refer for counselling') }}
                                        </x-primary-button>
                                    </div>
                                </form>
                            @endif
                        @endif
                    </div>
                @endif
            </section>
        </div>
    </div>
</div>
