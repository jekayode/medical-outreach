<div class="space-y-6 py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">{{ __('Counselling station') }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ __('Record counselling types and notes. Saving completes the visit and any interventions still awaiting counselling.') }}</p>
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
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">{{ __('Awaiting counselling') }}</h2>
                @if ($queueVisits->isEmpty())
                    <p class="text-sm text-gray-500">{{ __('No patients in this queue.') }}</p>
                @else
                    <ul class="space-y-2" role="list">
                        @foreach ($queueVisits as $qv)
                            <li wire:key="counselling-q-{{ $qv->getKey() }}">
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

                        @if ($selectedVisit->counsellingSession)
                            <div class="rounded-md bg-gray-50 border border-gray-200 px-4 py-3 text-sm text-gray-800">
                                {{ __('Counselling has already been recorded for this visit.') }}
                            </div>
                        @elseif (! $canRecord)
                            <div class="rounded-md bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900">
                                {{ __('Counselling cannot be recorded until every intervention is finished or awaiting counselling, and at least one line is awaiting counselling.') }}
                            </div>
                        @endif

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

                        <div>
                            <h3 class="text-sm font-semibold text-gray-800 mb-2">{{ __('Interventions') }}</h3>
                            @if ($selectedVisit->interventions->isEmpty())
                                <p class="text-sm text-gray-500">{{ __('No interventions on this visit.') }}</p>
                            @else
                                <ul class="divide-y divide-gray-100 rounded-lg border border-gray-200">
                                    @foreach ($selectedVisit->interventions as $inv)
                                        <li class="px-3 py-2 text-sm flex justify-between gap-2" wire:key="inv-{{ $inv->getKey() }}">
                                            <span class="text-gray-900">{{ \Illuminate\Support\Str::headline($inv->type->value) }}</span>
                                            <span class="text-gray-600 shrink-0">{{ \Illuminate\Support\Str::headline($inv->status->value) }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>

                        @if ($canRecord)
                            <form
                                wire:submit="saveSession"
                                wire:confirm="{{ __('Save the counselling session and update this visit?') }}"
                                class="space-y-6"
                            >
                                @error('form')
                                    <div class="text-sm text-red-600">{{ $message }}</div>
                                @enderror
                                @error('visit')
                                    <div class="text-sm text-red-600">{{ $message }}</div>
                                @enderror

                                <fieldset class="space-y-3 rounded-lg border border-gray-200 p-4">
                                    <legend class="text-sm font-semibold text-gray-800">{{ __('Counselling types') }} <span class="text-red-600">*</span></legend>
                                    <p class="text-xs text-gray-600">{{ __('Select one or more.') }}</p>
                                    <div class="space-y-2">
                                        @foreach ($counsellingTypeCases as $typeCase)
                                            <label class="flex items-center gap-2 text-sm text-gray-800">
                                                <input
                                                    type="checkbox"
                                                    wire:model="sessionFormTypes"
                                                    value="{{ $typeCase->value }}"
                                                    class="rounded border-gray-300 text-brand-primary focus:ring-brand-primary"
                                                />
                                                {{ \Illuminate\Support\Str::headline($typeCase->value) }}
                                            </label>
                                        @endforeach
                                    </div>
                                    @error('sessionFormTypes')
                                        <p class="text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </fieldset>

                                <div>
                                    <label for="counselling-notes" class="block text-sm font-medium text-gray-700">{{ __('Session notes') }}</label>
                                    <textarea
                                        id="counselling-notes"
                                        wire:model="sessionFormNotes"
                                        rows="3"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary sm:text-sm"
                                    ></textarea>
                                    @error('sessionFormNotes')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div
                                    class="sticky bottom-0 z-10 -mx-4 mt-6 flex justify-end border-t border-gray-200 bg-white px-4 py-4 shadow-[0_-8px_24px_rgba(15,23,42,0.06)] sm:-mx-6 sm:px-6"
                                >
                                    <x-primary-button type="submit" class="px-6">
                                        {{ __('Save counselling & complete visit') }}
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
