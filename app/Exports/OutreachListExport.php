<?php

namespace App\Exports;

use App\Models\Outreach;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

final class OutreachListExport implements FromQuery, WithHeadings, WithMapping
{
    /**
     * @return Builder<Outreach>
     */
    public function query(): Builder
    {
        return Outreach::query()->orderBy('start_date')->orderBy('name');
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            __('ID'),
            __('Name'),
            __('Location'),
            __('Start date'),
            __('End date'),
            __('Code prefix'),
            __('Status'),
            __('Next check-in sequence'),
            __('Notes'),
            __('Created at'),
            __('Updated at'),
        ];
    }

    /**
     * @param  Outreach  $outreach
     * @return list<string|int|float|null>
     */
    public function map($outreach): array
    {
        return [
            $outreach->getKey(),
            $outreach->name,
            $outreach->location,
            $this->formatDate($outreach->start_date),
            $this->formatDate($outreach->end_date),
            $outreach->code_prefix,
            $outreach->status?->value ?? '',
            $outreach->next_check_in_sequence,
            $outreach->notes ?? '',
            $this->formatDateTime($outreach->created_at),
            $this->formatDateTime($outreach->updated_at),
        ];
    }

    private function formatDate(mixed $value): string
    {
        if ($value instanceof CarbonInterface) {
            return $value->toDateString();
        }

        return '';
    }

    private function formatDateTime(mixed $value): string
    {
        if ($value instanceof CarbonInterface) {
            return $value->toDateTimeString();
        }

        return '';
    }
}
