<div class="space-y-6 py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">{{ __('Lab station') }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ __('Record results for ordered tests. Saving returns the patient to the doctor for review.') }}</p>
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
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">{{ __('Awaiting lab') }}</h2>
                @if ($queueInterventions->isEmpty())
                    <p class="text-sm text-gray-500">{{ __('No patients in the lab queue.') }}</p>
                @else
                    <ul class="space-y-2" role="list">
                        @foreach ($queueInterventions as $qi)
                            <li wire:key="lab-q-{{ $qi->getKey() }}">
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
                                {{ __('This visit has no general-consultation line awaiting lab results (or tests are already completed).') }}
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

                            @if ($selectedIntervention->consultation)
                                @php($c = $selectedIntervention->consultation)
                                <div class="rounded-lg border border-gray-100 bg-gray-50 p-4 space-y-2">
                                    <h3 class="text-sm font-semibold text-gray-800">{{ __('Consultation summary') }}</h3>
                                    <p class="text-sm text-gray-800"><span class="font-medium text-gray-600">{{ __('Chief complaint:') }}</span> {{ $c->chief_complaint }}</p>
                                    @if ($c->observations)
                                        <p class="text-sm text-gray-800"><span class="font-medium text-gray-600">{{ __('Observations:') }}</span> {{ $c->observations }}</p>
                                    @endif
                                    @if ($c->diagnosis)
                                        <p class="text-sm text-gray-800"><span class="font-medium text-gray-600">{{ __('Diagnosis:') }}</span> {{ $c->diagnosis }}</p>
                                    @endif
                                </div>
                            @endif

                            @if (! $canRecord)
                                <div class="rounded-md bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900">
                                    {{ __('There are no pending tests to enter for this selection.') }}
                                </div>
                            @else
                                <form
                                    wire:submit="saveResults"
                                    wire:confirm="{{ __('Save lab results and return this patient to the doctor for review?') }}"
                                    class="space-y-4"
                                >
                                    @error('form')
                                        <div class="text-sm text-red-600">{{ $message }}</div>
                                    @enderror

                                    <h3 class="text-sm font-semibold text-gray-800">{{ __('Test results') }}</h3>
                                    <ul class="space-y-4" role="list">
                                        @foreach ($pendingLabItems as $item)
                                            <li class="rounded-lg border border-gray-200 p-4" wire:key="item-{{ $item->getKey() }}">
                                                <div class="font-medium text-gray-900">{{ $item->test_name }}</div>
                                                @if ($item->notes)
                                                    <p class="mt-1 text-xs text-gray-500">{{ __('Order notes:') }} {{ $item->notes }}</p>
                                                @endif
                                                <label for="res-{{ $item->getKey() }}" class="mt-2 block text-xs font-medium text-gray-600">{{ __('Result') }} <span class="text-red-600">*</span></label>
                                                <textarea
                                                    id="res-{{ $item->getKey() }}"
                                                    wire:model="itemResults.{{ $item->getKey() }}"
                                                    rows="2"
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary sm:text-sm"
                                                ></textarea>
                                                @error('itemResults.' . $item->getKey())
                                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                                @enderror
                                            </li>
                                        @endforeach
                                    </ul>

                                    <div
                                        class="sticky bottom-0 z-10 -mx-4 mt-6 flex justify-end border-t border-gray-200 bg-white px-4 py-4 shadow-[0_-8px_24px_rgba(15,23,42,0.06)] sm:-mx-6 sm:px-6"
                                    >
                                        <x-primary-button type="submit" class="px-6">
                                            {{ __('Save results & return to doctor') }}
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
