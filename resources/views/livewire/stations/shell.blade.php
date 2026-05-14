<div class="space-y-6 py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <livewire:stations.beneficiary-lookup />

        @if (! empty($selectedVisitId))
            <div class="rounded-lg border border-brand-surface-border bg-brand-surface px-4 py-3 text-sm text-brand-ink shadow-sm">
                {{ __('Visit selected') }} — <span class="font-mono">{{ \Illuminate\Support\Str::limit($selectedVisitId, 13, '…') }}</span>
            </div>
        @endif

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <h2 class="text-lg font-semibold text-gray-800 mb-2">{{ $heading }}</h2>
                <p>{{ __('Station workflow UI will continue in the next build steps.') }}</p>
            </div>
        </div>
    </div>
</div>
