<?php

namespace App\Filament\Resources\Users\Pages;

use App\Exports\UserListExport;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('exportExcel')
                ->label(__('Export to Excel'))
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action(function (): BinaryFileResponse {
                    $filename = 'users-'.now()->format('Y-m-d-His').'.xlsx';

                    return Excel::download(new UserListExport, $filename);
                }),
        ];
    }
}
