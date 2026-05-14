<?php

namespace App\Exports;

use App\Models\Visit;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

final class VisitListExport implements FromQuery, WithHeadings, WithMapping
{
    /**
     * @return Builder<Visit>
     */
    public function query(): Builder
    {
        return Visit::query()
            ->with(['beneficiary', 'outreach', 'checkedInByUser'])
            ->orderByDesc('checked_in_at');
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            __('Visit ID'),
            __('Check-in code'),
            __('Beneficiary name'),
            __('Beneficiary phone'),
            __('Outreach'),
            __('Current stage'),
            __('Visit status'),
            __('Checked in at'),
            __('Checked in by'),
            __('Completed at'),
            __('Created at'),
            __('Updated at'),
        ];
    }

    /**
     * @param  Visit  $visit
     * @return list<string|int|float|null>
     */
    public function map($visit): array
    {
        return [
            $visit->getKey(),
            $visit->check_in_code,
            $visit->beneficiary?->full_name ?? '',
            $visit->beneficiary?->phone ?? '',
            $visit->outreach?->name ?? '',
            $visit->current_stage?->value ?? '',
            $visit->status?->value ?? '',
            $this->formatDateTime($visit->checked_in_at),
            $visit->checkedInByUser?->name ?? '',
            $this->formatDateTime($visit->completed_at),
            $this->formatDateTime($visit->created_at),
            $this->formatDateTime($visit->updated_at),
        ];
    }

    private function formatDateTime(mixed $value): string
    {
        if ($value instanceof CarbonInterface) {
            return $value->toDateTimeString();
        }

        return '';
    }
}
