<?php

namespace App\Filament\Resources\Beneficiaries\Pages;

use App\Exports\BeneficiaryListExport;
use App\Filament\Resources\Beneficiaries\BeneficiaryResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ListBeneficiaries extends ListRecords
{
    protected static string $resource = BeneficiaryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('exportExcel')
                ->label(__('Export to Excel'))
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action(function (): BinaryFileResponse {
                    $filename = 'beneficiaries-'.now()->format('Y-m-d-His').'.xlsx';

                    return Excel::download(new BeneficiaryListExport, $filename);
                }),
        ];
    }
}
