<?php

namespace App\Filament\Resources\Imports\Pages;

use App\Exports\ImportListExport;
use App\Filament\Resources\Imports\ImportResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ManageImports extends ManageRecords
{
    protected static string $resource = ImportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label(__('Export to Excel'))
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action(function (): BinaryFileResponse {
                    $filename = 'imports-'.now()->format('Y-m-d-His').'.xlsx';

                    return Excel::download(new ImportListExport, $filename);
                }),
        ];
    }
}
