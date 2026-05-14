<?php

namespace App\Filament\Resources\Outreaches\Pages;

use App\Enums\OutreachStatus;
use App\Filament\Resources\Outreaches\OutreachResource;
use App\Models\Outreach;
use App\Services\BeneficiaryImportService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditOutreach extends EditRecord
{
    protected static string $resource = OutreachResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importBeneficiaries')
                ->label(__('Import beneficiaries'))
                ->modalHeading(__('Import from Google Form export (CSV/XLSX)'))
                ->schema([
                    FileUpload::make('file')
                        ->label(__('Spreadsheet'))
                        ->required()
                        ->acceptedFileTypes([
                            'text/csv',
                            'text/plain',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])
                        ->storeFiles(false),
                ])
                ->action(function (array $data, BeneficiaryImportService $importer): void {
                    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile $upload */
                    $upload = $data['file'];
                    $path = $upload->getRealPath();
                    if ($path === false) {
                        Notification::make()
                            ->title(__('Could not read uploaded file.'))
                            ->danger()
                            ->send();

                        return;
                    }

                    $result = $importer->importFromUpload($this->record, $path, auth()->user());

                    Notification::make()
                        ->title(__('Import finished'))
                        ->body(__('Created: :c, updated: :u, failed: :f', [
                            'c' => $result['created'],
                            'u' => $result['updated'],
                            'f' => $result['failed'],
                        ]))
                        ->success()
                        ->send();
                }),
            Action::make('markActive')
                ->label(__('Mark as active outreach'))
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status !== OutreachStatus::Active)
                ->action(function (): void {
                    DB::transaction(function (): void {
                        Outreach::query()
                            ->where('status', OutreachStatus::Active)
                            ->update(['status' => OutreachStatus::Closed]);

                        $this->record->update(['status' => OutreachStatus::Active]);
                    });

                    Notification::make()
                        ->title(__('This outreach is now active.'))
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),
            DeleteAction::make(),
        ];
    }
}
