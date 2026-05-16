@php
    use App\Enums\ConsultationNextAction;
@endphp

<div class="space-y-6 py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">{{ __('General doctor station') }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ __('Review vitals and lab results, document the consultation, and route the patient to additional lab tests, pharmacy, or completion.') }}</p>
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
            <aside class="lg:col-span-1 space-y-4" wire:poll.10s>
                @php
                    $sidebarInterventions = $queueTab === 'done' ? $doneInterventions : $queueInterventions;
                @endphp
                <div class="grid grid-cols-3 gap-1 rounded-lg border border-gray-200 bg-white p-1 text-xs font-medium shadow-sm">
                    <button
                        type="button"
                        wire:click="$set('queueTab', 'pending')"
                        @class([
                            'rounded-md min-h-11 px-2 py-2 text-sm transition sm:px-3',
                            'bg-brand-primary text-white shadow' => $queueTab === 'pending',
                            'text-gray-600 hover:bg-gray-50' => $queueTab !== 'pending',
                        ])
                    >
                        {{ __('Pending') }}
                    </button>
                    <button
                        type="button"
                        wire:click="$set('queueTab', 'review')"
                        @class([
                            'rounded-md min-h-11 px-2 py-2 text-sm transition sm:px-3',
                            'bg-brand-primary text-white shadow' => $queueTab === 'review',
                            'text-gray-600 hover:bg-gray-50' => $queueTab !== 'review',
                        ])
                    >
                        {{ __('From lab') }}
                    </button>
                    <button
                        type="button"
                        wire:click="$set('queueTab', 'done')"
                        @class([
                            'rounded-md min-h-11 px-2 py-2 text-sm transition sm:px-3',
                            'bg-brand-primary text-white shadow' => $queueTab === 'done',
                            'text-gray-600 hover:bg-gray-50' => $queueTab !== 'done',
                        ])
                    >
                        {{ __('Done') }}
                    </button>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                        @if ($queueTab === 'pending')
                            {{ __('Awaiting doctor') }}
                        @elseif ($queueTab === 'review')
                            {{ __('Returned for review') }}
                        @else
                            {{ __('Routed from doctor') }}
                        @endif
                    </h2>
                    @if ($sidebarInterventions->isEmpty())
                        <p class="mt-2 text-sm text-gray-500">{{ __('No patients in this list.') }}</p>
                    @else
                        <ul class="mt-2 space-y-2" role="list">
                            @foreach ($sidebarInterventions as $qi)
                                <li wire:key="dq-{{ $queueTab }}-{{ $qi->getKey() }}">
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
                                        @if ($queueTab === 'done')
                                            <div class="mt-1 text-xs font-medium text-gray-600">
                                                {{ \Illuminate\Support\Str::headline($qi->status->value) }}
                                            </div>
                                        @endif
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
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
                                {{ __('This visit does not have a general consultation waiting for the doctor (wrong stage or service line not selected at vitals).') }}
                            </div>
                        @else
                            @if (! $isDoneListIntervention && $selectedIntervention->status === \App\Enums\InterventionStatus::ConsultationReview && $selectedIntervention->consultation?->labOrders?->isNotEmpty())
                                <div class="rounded-lg border border-gray-100 bg-gray-50 p-4 space-y-3">
                                    <h3 class="text-sm font-semibold text-gray-800">{{ __('Lab results') }}</h3>
                                    @foreach ($selectedIntervention->consultation->labOrders as $order)
                                        <ul class="space-y-3 text-sm">
                                            @foreach ($order->items as $item)
                                                <li class="border-b border-gray-200 pb-2 last:border-0 last:pb-0" wire:key="lab-{{ $item->getKey() }}">
                                                    <span class="font-medium text-gray-900">{{ $item->test_name }}</span>
                                                    @if ($item->notes)
                                                        <span class="text-gray-500"> — {{ $item->notes }}</span>
                                                    @endif
                                                    <div class="mt-1 text-gray-700">
                                                        <span class="text-gray-500">{{ __('Result:') }}</span>
                                                        {{ $item->result ?? '—' }}
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ul>
                                        @if ($order->notes)
                                            <p class="text-sm text-gray-700">
                                                <span class="font-medium text-gray-500">{{ __('Lab comment:') }}</span>
                                                {{ $order->notes }}
                                            </p>
                                        @endif
                                    @endforeach
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
                                        @if ($v->lab_notes)
                                            <div class="sm:col-span-2"><span class="text-gray-500">{{ __('Lab comment') }}</span> {{ $v->lab_notes }}</div>
                                        @endif
                                    </dl>
                                @endif
                            </div>

                            @if ($isDoneListIntervention)
                                <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800">
                                    <p class="font-medium">{{ __('Routed from doctor (read-only)') }}</p>
                                    <p class="mt-1 text-xs text-slate-600">
                                        {{ __('Consultation line:') }}
                                        <span class="font-semibold text-slate-800">{{ \Illuminate\Support\Str::headline($selectedIntervention->status->value) }}</span>
                                    </p>
                                </div>

                                @if ($selectedIntervention->consultation)
                                    @php($c = $selectedIntervention->consultation)
                                    <div class="space-y-4 rounded-lg border border-gray-200 bg-white p-4">
                                        <h3 class="text-sm font-semibold text-gray-800">{{ __('Consultation record') }}</h3>
                                        <dl class="space-y-3 text-sm text-gray-700">
                                            <div>
                                                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('Chief complaint') }}</dt>
                                                <dd class="mt-1 whitespace-pre-wrap">{{ $c->chief_complaint }}</dd>
                                            </div>
                                            @if ($c->observations)
                                                <div>
                                                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('Observations') }}</dt>
                                                    <dd class="mt-1 whitespace-pre-wrap">{{ $c->observations }}</dd>
                                                </div>
                                            @endif
                                            @if ($c->diagnosis)
                                                <div>
                                                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('Diagnosis') }}</dt>
                                                    <dd class="mt-1 whitespace-pre-wrap">{{ $c->diagnosis }}</dd>
                                                </div>
                                            @endif
                                            @if ($c->notes)
                                                <div>
                                                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('Consultation notes') }}</dt>
                                                    <dd class="mt-1 whitespace-pre-wrap">{{ $c->notes }}</dd>
                                                </div>
                                            @endif
                                            @if ($c->next_action)
                                                <div>
                                                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('Last route choice') }}</dt>
                                                    <dd class="mt-1 font-medium text-gray-900">{{ \Illuminate\Support\Str::headline($c->next_action->value) }}</dd>
                                                </div>
                                            @endif
                                        </dl>
                                    </div>
                                @endif

                                @if ($selectedIntervention->consultation?->labOrders?->isNotEmpty())
                                    <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                                        <h3 class="text-sm font-semibold text-gray-800 mb-2">{{ __('Lab orders & results') }}</h3>
                                        <ul class="space-y-3 text-sm">
                                            @foreach ($selectedIntervention->consultation->labOrders as $order)
                                                @foreach ($order->items as $item)
                                                    <li class="border-b border-gray-200 pb-2 last:border-0 last:pb-0" wire:key="lab-done-{{ $item->getKey() }}">
                                                        <span class="font-medium text-gray-900">{{ $item->test_name }}</span>
                                                        @if ($item->notes)
                                                            <span class="text-gray-500"> — {{ $item->notes }}</span>
                                                        @endif
                                                        <div class="mt-1 text-gray-700">
                                                            <span class="text-gray-500">{{ __('Result:') }}</span>
                                                            {{ $item->result ?? '—' }}
                                                        </div>
                                                    </li>
                                                @endforeach
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                @if ($selectedIntervention->prescriptions->isNotEmpty())
                                    <div class="rounded-lg border border-gray-200 overflow-hidden">
                                        <h3 class="text-sm font-semibold text-gray-800 bg-gray-50 px-4 py-3 border-b border-gray-200">{{ __('Prescriptions written') }}</h3>
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                                <thead class="bg-gray-50">
                                                    <tr>
                                                        <th class="px-4 py-2 text-left font-medium text-gray-600">{{ __('Drug') }}</th>
                                                        <th class="px-4 py-2 text-left font-medium text-gray-600">{{ __('Dosage') }}</th>
                                                        <th class="px-4 py-2 text-left font-medium text-gray-600">{{ __('Frequency') }}</th>
                                                        <th class="px-4 py-2 text-left font-medium text-gray-600">{{ __('Duration') }}</th>
                                                        <th class="px-4 py-2 text-right font-medium text-gray-600">{{ __('Qty') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-100 bg-white">
                                                    @foreach ($selectedIntervention->prescriptions as $rx)
                                                        @foreach ($rx->items as $item)
                                                            <tr wire:key="rx-done-{{ $item->getKey() }}">
                                                                <td class="px-4 py-2 text-gray-900">{{ $item->drug_name }}</td>
                                                                <td class="px-4 py-2 text-gray-700">{{ $item->dosage }}</td>
                                                                <td class="px-4 py-2 text-gray-700">{{ $item->frequency }}</td>
                                                                <td class="px-4 py-2 text-gray-700">{{ $item->duration }}</td>
                                                                <td class="px-4 py-2 text-right text-gray-700">{{ $item->quantity }}</td>
                                                            </tr>
                                                        @endforeach
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endif
                            @elseif (! $canSave)
                                <div class="rounded-md bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900">
                                    {{ __('This consultation is no longer editable from the doctor queue.') }}
                                </div>
                            @else
                                <form
                                    wire:submit="saveConsultation"
                                    wire:confirm="{{ __('Save this consultation and send the patient to the selected next step?') }}"
                                    class="space-y-6"
                                >
                                    @error('form')
                                        <div class="text-sm text-red-600">{{ $message }}</div>
                                    @enderror

                                    <div>
                                        <label for="chief_complaint" class="block text-sm font-medium text-gray-700">{{ __('Chief complaint') }} <span class="text-red-600">*</span></label>
                                        <textarea
                                            id="chief_complaint"
                                            wire:model="consultationForm.chief_complaint"
                                            rows="3"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary sm:text-sm"
                                        ></textarea>
                                        @error('consultationForm.chief_complaint')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="observations" class="block text-sm font-medium text-gray-700">{{ __('Observations') }}</label>
                                        <textarea
                                            id="observations"
                                            wire:model="consultationForm.observations"
                                            rows="3"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary sm:text-sm"
                                        ></textarea>
                                        @error('consultationForm.observations')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="diagnosis" class="block text-sm font-medium text-gray-700">{{ __('Diagnosis') }}</label>
                                        <textarea
                                            id="diagnosis"
                                            wire:model="consultationForm.diagnosis"
                                            rows="2"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary sm:text-sm"
                                        ></textarea>
                                        @error('consultationForm.diagnosis')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="cnotes" class="block text-sm font-medium text-gray-700">{{ __('Consultation notes') }}</label>
                                        <textarea
                                            id="cnotes"
                                            wire:model="consultationForm.notes"
                                            rows="2"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary sm:text-sm"
                                        ></textarea>
                                        @error('consultationForm.notes')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <fieldset>
                                        <legend class="text-sm font-medium text-gray-700">{{ __('Next step') }} <span class="text-red-600">*</span></legend>
                                        <div class="mt-2 space-y-2">
                                            @foreach ($availableNextActions as $action)
                                                <label class="flex items-center gap-2 text-sm text-gray-800">
                                                    <input
                                                        type="radio"
                                                        wire:model.live="nextAction"
                                                        value="{{ $action->value }}"
                                                        class="rounded-full border-gray-300 text-brand-primary focus:ring-brand-primary"
                                                    />
                                                    {{ \Illuminate\Support\Str::headline($action->value) }}
                                                </label>
                                            @endforeach
                                        </div>
                                        @error('nextAction')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </fieldset>

                                    @if ($nextAction === ConsultationNextAction::Lab->value)
                                        <div class="rounded-lg border border-gray-200 p-4 space-y-3">
                                            <div class="flex items-center justify-between">
                                                <h3 class="text-sm font-semibold text-gray-800">{{ __('Lab tests to order') }}</h3>
                                                <button type="button" wire:click="addLabRow" class="text-sm font-medium text-brand-primary hover:text-brand-hover">
                                                    {{ __('Add row') }}
                                                </button>
                                            </div>
                                            @foreach ($labItems as $idx => $row)
                                                <div class="grid grid-cols-1 sm:grid-cols-6 gap-2 items-end border-b border-gray-100 pb-3" wire:key="lab-row-{{ $idx }}">
                                                    <div class="sm:col-span-3">
                                                        <label class="block text-xs font-medium text-gray-600">{{ __('Test name') }}</label>
                                                        <input
                                                            type="text"
                                                            wire:model="labItems.{{ $idx }}.test_name"
                                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary sm:text-sm"
                                                        />
                                                    </div>
                                                    <div class="sm:col-span-2">
                                                        <label class="block text-xs font-medium text-gray-600">{{ __('Notes') }}</label>
                                                        <input
                                                            type="text"
                                                            wire:model="labItems.{{ $idx }}.notes"
                                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary sm:text-sm"
                                                        />
                                                    </div>
                                                    <div class="sm:col-span-1 flex sm:justify-end">
                                                        <button type="button" wire:click="removeLabRow({{ $idx }})" class="text-sm text-red-600 hover:text-red-500">
                                                            {{ __('Remove') }}
                                                        </button>
                                                    </div>
                                                </div>
                                            @endforeach
                                            @error('labItems')
                                                <p class="text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    @endif

                                    @if ($nextAction === ConsultationNextAction::Pharmacy->value)
                                        <div class="rounded-lg border border-gray-200 p-4 space-y-3">
                                            <div class="flex items-center justify-between">
                                                <h3 class="text-sm font-semibold text-gray-800">{{ __('Prescription lines') }}</h3>
                                                <button type="button" wire:click="addPrescriptionRow" class="text-sm font-medium text-brand-primary hover:text-brand-hover">
                                                    {{ __('Add row') }}
                                                </button>
                                            </div>
                                            <p class="text-xs text-gray-500">{{ __('Suggestions show common partner stock; you can type any drug name.') }}</p>
                                            <datalist id="partner-medication-suggestions">
                                                @foreach ($partnerMedicationSuggestions as $suggestion)
                                                    <option value="{{ $suggestion }}"></option>
                                                @endforeach
                                            </datalist>
                                            @foreach ($prescriptionItems as $idx => $row)
                                                <div class="grid grid-cols-1 lg:grid-cols-12 gap-2 items-end border-b border-gray-100 pb-3" wire:key="rx-row-{{ $idx }}">
                                                    <div class="lg:col-span-3">
                                                        <label class="block text-xs font-medium text-gray-600">{{ __('Drug') }}</label>
                                                        <input
                                                            type="text"
                                                            list="partner-medication-suggestions"
                                                            autocomplete="off"
                                                            wire:model="prescriptionItems.{{ $idx }}.drug_name"
                                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm"
                                                        />
                                                    </div>
                                                    <div class="lg:col-span-2">
                                                        <label class="block text-xs font-medium text-gray-600">{{ __('Dosage') }}</label>
                                                        <input type="text" wire:model="prescriptionItems.{{ $idx }}.dosage" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" />
                                                    </div>
                                                    <div class="lg:col-span-2">
                                                        <label class="block text-xs font-medium text-gray-600">{{ __('Frequency') }}</label>
                                                        <input type="text" wire:model="prescriptionItems.{{ $idx }}.frequency" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" />
                                                    </div>
                                                    <div class="lg:col-span-2">
                                                        <label class="block text-xs font-medium text-gray-600">{{ __('Duration') }}</label>
                                                        <input type="text" wire:model="prescriptionItems.{{ $idx }}.duration" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" />
                                                    </div>
                                                    <div class="lg:col-span-2">
                                                        <label class="block text-xs font-medium text-gray-600">{{ __('Qty') }}</label>
                                                        <input type="number" min="1" wire:model="prescriptionItems.{{ $idx }}.quantity" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" />
                                                    </div>
                                                    <div class="lg:col-span-1 flex lg:justify-end">
                                                        <button type="button" wire:click="removePrescriptionRow({{ $idx }})" class="text-sm text-red-600 hover:text-red-500">{{ __('Remove') }}</button>
                                                    </div>
                                                </div>
                                            @endforeach
                                            @error('prescriptionItems')
                                                <p class="text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    @endif

                                    <div
                                        class="sticky bottom-0 z-10 -mx-4 mt-8 flex justify-end border-t border-gray-200 bg-white px-4 py-4 shadow-[0_-8px_24px_rgba(15,23,42,0.06)] sm:-mx-6 sm:px-6"
                                    >
                                        <x-primary-button type="submit" class="px-6">
                                            {{ __('Save & route') }}
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
