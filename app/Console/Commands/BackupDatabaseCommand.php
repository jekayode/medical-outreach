<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'database:backup',
    description: 'Create a gzip-compressed MySQL logical backup (for hourly cron during outreaches, PRD §12.2).',
)]
final class BackupDatabaseCommand extends Command
{
    public function handle(DatabaseBackupService $backupService): int
    {
        if ($backupService->driver() !== 'mysql') {
            $this->warn('database:backup only supports the mysql driver; skipping.');

            return self::SUCCESS;
        }

        try {
            $path = $backupService->createBackup();
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $size = filesize($path);
        $this->info(sprintf(
            'Backup written to %s (%s).',
            $path,
            $this->formatBytes(is_int($size) ? $size : 0)
        ));

        return self::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $value = (float) $bytes;
        while ($value >= 1024 && $i < count($units) - 1) {
            $value /= 1024;
            $i++;
        }

        return (string) (round($value, $i === 0 ? 0 : 1)).' '.$units[$i];
    }
}
