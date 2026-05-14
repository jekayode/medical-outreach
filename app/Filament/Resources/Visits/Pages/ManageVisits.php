<?php

namespace App\Filament\Resources\Visits\Pages;

use App\Exports\VisitListExport;
use App\Filament\Resources\Visits\VisitResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ManageVisits extends ManageRecords
{
    protected static string $resource = VisitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label(__('Export to Excel'))
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action(function (): BinaryFileResponse {
                    $filename = 'visits-'.now()->format('Y-m-d-His').'.xlsx';

                    return Excel::download(new VisitListExport, $filename);
                }),
        ];
    }
}
