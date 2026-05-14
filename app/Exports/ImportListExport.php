<?php

namespace App\Exports;

use App\Models\Import;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

final class ImportListExport implements FromQuery, WithHeadings, WithMapping
{
    private const int ERRORS_CELL_MAX = 32000;

    /**
     * @return Builder<Import>
     */
    public function query(): Builder
    {
        return Import::query()
            ->with(['outreach', 'importedByUser'])
            ->orderByDesc('created_at');
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            __('ID'),
            __('Filename'),
            __('Outreach'),
            __('Imported by'),
            __('Total rows'),
            __('Successful rows'),
            __('Failed rows'),
            __('Errors (JSON)'),
            __('Imported at'),
        ];
    }

    /**
     * @param  Import  $import
     * @return list<string|int|float|null>
     */
    public function map($import): array
    {
        $errorsJson = json_encode($import->errors ?? [], JSON_UNESCAPED_UNICODE);
        if ($errorsJson === false) {
            $errorsJson = '';
        }

        return [
            $import->getKey(),
            $import->filename,
            $import->outreach?->name ?? '',
            $import->importedByUser?->name ?? '',
            $import->total_rows,
            $import->successful_rows,
            $import->failed_rows,
            Str::limit($errorsJson, self::ERRORS_CELL_MAX, '…'),
            $this->formatDateTime($import->created_at),
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
