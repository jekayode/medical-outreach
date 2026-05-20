<?php

namespace App\Http\Controllers\Admin;

use App\Filament\Pages\Reports;
use App\Http\Controllers\Controller;
use App\Services\DatabaseBackupService;
use Illuminate\Http\RedirectResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DatabaseBackupDownloadController extends Controller
{
    public function __invoke(DatabaseBackupService $backupService): BinaryFileResponse|StreamedResponse|RedirectResponse
    {
        try {
            return $backupService->downloadResponse();
        } catch (RuntimeException $exception) {
            return redirect()
                ->to(Reports::getUrl())
                ->with('backup_error', $exception->getMessage());
        }
    }
}
