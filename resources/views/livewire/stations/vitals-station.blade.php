<div class="space-y-6 py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">{{ __('Vitals station') }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ __('Record vitals and choose which service lines this visit will use today.') }}</p>
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
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">{{ __('Awaiting vitals') }}</h2>
                @if ($queueVisits->isEmpty())
                    <p class="text-sm text-gray-500">{{ __('No visits in queue.') }}</p>
                @else
                    <ul class="space-y-2" role="list">
                        @foreach ($queueVisits as $qv)
                            <li wire:key="q-{{ $qv->getKey() }}">
                                <button
                                    type="button"
                                    wire:click="selectQueueVisit('{{ $qv->getKey() }}')"
                                    @class([
                                        'w-full min-h-11 text-left rounded-lg border px-3 py-3 text-sm transition shadow-sm',
                                        'border-brand-primary bg-brand-surface ring-1 ring-brand-primary' => $selectedVisitId === $qv->getKey(),
                                        'border-gray-200 bg-white hover:border-gray-300' => $selectedVisitId !== $qv->getKey(),
                                    ])
                                >
                                    <div class="font-medium text-gray-900">{{ $qv->beneficiary->full_name }}</div>
                                    <div class="mt-1 font-mono text-xs text-brand-primary">{{ $qv->check_in_code }}</div>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </aside>

            <section class="lg:col-span-3 space-y-6">
                @if (! $selectedVisit)
                    <div class="rounded-lg border border-dashed border-gray-300 bg-white p-8 text-center text-gray-500 text-sm">
                        {{ __('Select a visit from the queue or search above to record vitals.') }}
                    </div>
                @else
                    <div class="rounded-lg border border-gray-200 bg-white p-4 sm:p-6 shadow-sm">
                        <div class="flex flex-wrap justify-between gap-2 border-b border-gray-100 pb-4 mb-4">
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

                        @if (! $canRecordVitals)
                            <div class="rounded-md bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900">
                                @if ($selectedVisit->vitals)
                                    {{ __('Vitals are already recorded for this visit.') }}
                                @else
                                    {{ __('This visit is not ready for vitals (wrong stage).') }}
                                @endif
                            </div>
                        @else
                            <x-input-error :messages="$errors->get('form')" class="mb-4" />
                            <x-input-error :messages="$errors->get('visit')" class="mb-4" />

                            <form
                                wire:submit="saveVitals"
                                wire:confirm="{{ __('Save vitals, create the selected service lines, and move this visit forward?') }}"
                                class="space-y-6"
                            >
                                <fieldset class="space-y-4">
                                    <legend class="text-sm font-semibold text-gray-800">{{ __('Vitals') }}</legend>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <x-input-label for="bp-sys" :value="__('BP systolic')" />
                                            <x-text-input id="bp-sys" class="block mt-1 w-full" type="number" inputmode="numeric" wire:model="form.blood_pressure_systolic" min="0" />
                                            <x-input-error :messages="$errors->get('form.blood_pressure_systolic')" class="mt-2" />
                                        </div>
                                        <div>
                                            <x-input-label for="bp-dia" :value="__('BP diastolic')" />
                                            <x-text-input id="bp-dia" class="block mt-1 w-full" type="number" inputmode="numeric" wire:model="form.blood_pressure_diastolic" min="0" />
                                            <x-input-error :messages="$errors->get('form.blood_pressure_diastolic')" class="mt-2" />
                                        </div>
                                        <div>
                                            <x-input-label for="pulse" :value="__('Pulse (bpm)')" />
                                            <x-text-input id="pulse" class="block mt-1 w-full" type="number" inputmode="numeric" wire:model="form.pulse" required min="0" />
                                            <x-input-error :messages="$errors->get('form.pulse')" class="mt-2" />
                                        </div>
                                        <div>
                                            <x-input-label for="temp" :value="__('Temperature (°C)')" />
                                            <x-text-input id="temp" class="block mt-1 w-full" type="number" inputmode="decimal" step="0.1" wire:model="form.temperature" required />
                                            <x-input-error :messages="$errors->get('form.temperature')" class="mt-2" />
                                        </div>
                                        <div>
                                            <x-input-label for="weight" :value="__('Weight (kg)')" />
                                            <x-text-input id="weight" class="block mt-1 w-full" type="number" inputmode="decimal" step="0.01" wire:model="form.weight_kg" required />
                                            <x-input-error :messages="$errors->get('form.weight_kg')" class="mt-2" />
                                        </div>
                                        <div>
                                            <x-input-label for="height" :value="__('Height (cm)')" />
                                            <x-text-input id="height" class="block mt-1 w-full" type="number" inputmode="decimal" step="0.01" wire:model="form.height_cm" required />
                                            <x-input-error :messages="$errors->get('form.height_cm')" class="mt-2" />
                                        </div>
                                        <div class="sm:col-span-2">
                                            @if ($this->estimatedBmi !== null)
                                                <p class="text-sm text-gray-700">
                                                    <span class="font-medium">{{ __('BMI (estimated):') }}</span>
                                                    {{ $this->estimatedBmi }}
                                                    <span class="text-gray-500">({{ __('saved value uses server rules') }})</span>
                                                </p>
                                            @endif
                                        </div>
                                        <div>
                                            <x-input-label for="hiv" :value="__('HIV status')" />
                                            <select id="hiv" wire:model="form.hiv_status" class="mt-1 block w-full min-h-11 text-base rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary sm:text-sm">
                                                <option value="">{{ __('Not specified') }}</option>
                                                @foreach (\App\Enums\HivStatus::cases() as $h)
                                                    <option value="{{ $h->value }}">{{ \Illuminate\Support\Str::headline($h->value) }}</option>
                                                @endforeach
                                            </select>
                                            <x-input-error :messages="$errors->get('form.hiv_status')" class="mt-2" />
                                        </div>
                                        <div class="sm:col-span-2">
                                            <x-input-label for="notes" :value="__('Notes')" />
                                            <textarea id="notes" wire:model="form.notes" rows="3" class="mt-1 block w-full text-base rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary sm:text-sm"></textarea>
                                            <x-input-error :messages="$errors->get('form.notes')" class="mt-2" />
                                        </div>
                                    </div>
                                </fieldset>

                                <fieldset class="space-y-3">
                                    <legend class="text-sm font-semibold text-gray-800">{{ __('Service lines for this visit') }}</legend>
                                    <p class="text-xs text-gray-500">{{ __('Choose one or more. Each line becomes a separate intervention record.') }}</p>
                                    <div class="space-y-2">
                                        @foreach (\App\Enums\InterventionType::cases() as $type)
                                            <label class="flex min-h-11 items-center gap-3 rounded-lg border border-gray-200 px-3 py-3 cursor-pointer hover:bg-gray-50">
                                                <input type="checkbox" class="rounded border-gray-300 text-brand-primary shadow-sm focus:ring-brand-primary" value="{{ $type->value }}" wire:model="interventionSelections" />
                                                <span class="text-sm font-medium text-gray-900">{{ \Illuminate\Support\Str::headline(str_replace('_', ' ', $type->value)) }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    <x-input-error :messages="$errors->get('interventionSelections')" class="mt-2" />
                                </fieldset>

                                <div class="flex justify-end">
                                    <x-primary-button type="submit" class="px-6">
                                        {{ __('Save vitals & queue services') }}
                                    </x-primary-button>
                                </div>
                            </form>
                        @endif
                    </div>
                @endif
            </section>
        </div>
    </div>
</div>
