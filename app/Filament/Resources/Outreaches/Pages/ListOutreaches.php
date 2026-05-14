<?php

namespace App\Filament\Resources\Outreaches\Pages;

use App\Exports\OutreachListExport;
use App\Filament\Resources\Outreaches\OutreachResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ListOutreaches extends ListRecords
{
    protected static string $resource = OutreachResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('exportExcel')
                ->label(__('Export to Excel'))
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action(function (): BinaryFileResponse {
                    $filename = 'outreaches-'.now()->format('Y-m-d-His').'.xlsx';

                    return Excel::download(new OutreachListExport, $filename);
                }),
        ];
    }
}
