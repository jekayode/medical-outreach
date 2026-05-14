<div class="space-y-6 py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">{{ __('Eye care station') }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ __('Record visual acuity, findings, glasses, drops (with Rx if needed), and referrals.') }}</p>
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
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">{{ __('Awaiting eye clinic') }}</h2>
                @if ($queueInterventions->isEmpty())
                    <p class="text-sm text-gray-500">{{ __('No patients in this queue.') }}</p>
                @else
                    <ul class="space-y-2" role="list">
                        @foreach ($queueInterventions as $qi)
                            <li wire:key="eye-q-{{ $qi->getKey() }}">
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
                                {{ __('This visit has no eye-care line queued (add Eye Care at vitals).') }}
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
                                    {{ __('This eye-care line is no longer editable from the queue.') }}
                                </div>
                            @else
                                <form
                                    wire:submit="saveExam"
                                    wire:confirm="{{ __('Save the eye exam and route this patient forward?') }}"
                                    class="space-y-6"
                                >
                                    @error('form')
                                        <div class="text-sm text-red-600">{{ $message }}</div>
                                    @enderror

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label for="va-l" class="block text-sm font-medium text-gray-700">{{ __('Visual acuity (left)') }}</label>
                                            <input id="va-l" type="text" wire:model="examForm.visual_acuity_left" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary sm:text-sm" placeholder="e.g. 20/40" />
                                            @error('examForm.visual_acuity_left')
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="va-r" class="block text-sm font-medium text-gray-700">{{ __('Visual acuity (right)') }}</label>
                                            <input id="va-r" type="text" wire:model="examForm.visual_acuity_right" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary sm:text-sm" placeholder="e.g. 20/40" />
                                            @error('examForm.visual_acuity_right')
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>

                                    <div>
                                        <label for="findings" class="block text-sm font-medium text-gray-700">{{ __('Findings') }}</label>
                                        <textarea id="findings" wire:model="examForm.findings" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary sm:text-sm"></textarea>
                                        @error('examForm.findings')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <fieldset class="space-y-3 rounded-lg border border-gray-200 p-4">
                                        <legend class="text-sm font-semibold text-gray-800">{{ __('Glasses') }}</legend>
                                        <label class="flex items-center gap-2 text-sm text-gray-800">
                                            <input type="checkbox" wire:model.live="examForm.glasses_prescribed" class="rounded border-gray-300 text-brand-primary focus:ring-brand-primary" />
                                            {{ __('Glasses prescribed') }}
                                        </label>
                                        @if ($examForm['glasses_prescribed'])
                                            <div>
                                                <label for="glass-det" class="block text-xs font-medium text-gray-600">{{ __('Prescription details') }} <span class="text-red-600">*</span></label>
                                                <textarea id="glass-det" wire:model="examForm.glasses_prescription_details" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary sm:text-sm"></textarea>
                                                @error('examForm.glasses_prescription_details')
                                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        @endif
                                    </fieldset>

                                    <fieldset class="space-y-3 rounded-lg border border-gray-200 p-4">
                                        <legend class="text-sm font-semibold text-gray-800">{{ __('Drops / pharmacy') }}</legend>
                                        <label class="flex items-center gap-2 text-sm text-gray-800">
                                            <input type="checkbox" wire:model.live="examForm.drops_prescribed" class="rounded border-gray-300 text-brand-primary focus:ring-brand-primary" />
                                            {{ __('Drops prescribed (sends patient to pharmacy)') }}
                                        </label>
                                        @if ($examForm['drops_prescribed'])
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                                                <div class="sm:col-span-2">
                                                    <label class="block text-xs font-medium text-gray-600">{{ __('Drug') }} <span class="text-red-600">*</span></label>
                                                    <input type="text" wire:model="dropsRx.drug_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" />
                                                    @error('dropsRx.drug_name')
                                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                                    @enderror
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-600">{{ __('Dosage') }} <span class="text-red-600">*</span></label>
                                                    <input type="text" wire:model="dropsRx.dosage" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" />
                                                    @error('dropsRx.dosage')
                                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                                    @enderror
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-600">{{ __('Frequency') }} <span class="text-red-600">*</span></label>
                                                    <input type="text" wire:model="dropsRx.frequency" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" />
                                                    @error('dropsRx.frequency')
                                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                                    @enderror
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-600">{{ __('Duration') }} <span class="text-red-600">*</span></label>
                                                    <input type="text" wire:model="dropsRx.duration" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" />
                                                    @error('dropsRx.duration')
                                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                                    @enderror
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-600">{{ __('Quantity') }} <span class="text-red-600">*</span></label>
                                                    <input type="number" min="1" wire:model="dropsRx.quantity" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" />
                                                    @error('dropsRx.quantity')
                                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                                    @enderror
                                                </div>
                                            </div>
                                        @endif
                                    </fieldset>

                                    <fieldset class="space-y-3 rounded-lg border border-gray-200 p-4">
                                        <legend class="text-sm font-semibold text-gray-800">{{ __('Referral') }}</legend>
                                        <label class="flex items-center gap-2 text-sm text-gray-800">
                                            <input type="checkbox" wire:model.live="examForm.referral_needed" class="rounded border-gray-300 text-brand-primary focus:ring-brand-primary" />
                                            {{ __('Referral needed') }}
                                        </label>
                                        @if ($examForm['referral_needed'])
                                            <div>
                                                <label for="ref-notes" class="block text-xs font-medium text-gray-600">{{ __('Referral notes') }} <span class="text-red-600">*</span></label>
                                                <textarea id="ref-notes" wire:model="examForm.referral_notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary sm:text-sm"></textarea>
                                                @error('examForm.referral_notes')
                                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        @endif
                                    </fieldset>

                                    <div>
                                        <label for="ex-notes" class="block text-sm font-medium text-gray-700">{{ __('Exam notes') }}</label>
                                        <textarea id="ex-notes" wire:model="examForm.notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary sm:text-sm"></textarea>
                                        @error('examForm.notes')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div
                                        class="sticky bottom-0 z-10 -mx-4 mt-6 flex justify-end border-t border-gray-200 bg-white px-4 py-4 shadow-[0_-8px_24px_rgba(15,23,42,0.06)] sm:-mx-6 sm:px-6"
                                    >
                                        <x-primary-button type="submit" class="px-6">
                                            {{ __('Save eye exam') }}
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
