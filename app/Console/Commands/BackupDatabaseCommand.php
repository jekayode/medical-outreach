<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'database:backup',
    description: 'Create a gzip-compressed MySQL logical backup (for hourly cron during outreaches, PRD §12.2).',
)]
final class BackupDatabaseCommand extends Command
{
    public function handle(): int
    {
        $default = (string) config('database.default');
        $connection = config("database.connections.{$default}");

        if (! is_array($connection) || ($connection['driver'] ?? null) !== 'mysql') {
            $this->warn('database:backup only supports the mysql driver; skipping.');

            return self::SUCCESS;
        }

        $host = (string) ($connection['host'] ?? '127.0.0.1');
        $port = (int) ($connection['port'] ?? 3306);
        $database = (string) ($connection['database'] ?? '');
        $username = (string) ($connection['username'] ?? 'root');
        $password = (string) ($connection['password'] ?? '');

        if ($database === '') {
            $this->error('Database name is empty; check your configuration.');

            return self::FAILURE;
        }

        $dir = storage_path('backups');
        File::ensureDirectoryExists($dir);

        $filename = sprintf('medical-outreach-%s.sql.gz', now()->format('Y-m-d-His'));
        $path = $dir.DIRECTORY_SEPARATOR.$filename;

        $previousMysqlPwd = getenv('MYSQL_PWD');
        putenv('MYSQL_PWD='.$password);

        $process = new Process(
            [
                'mysqldump',
                '--single-transaction',
                '--no-tablespaces',
                '--routines',
                '--skip-comments',
                '-h',
                $host,
                '-P',
                (string) $port,
                '-u',
                $username,
                $database,
            ],
            base_path(),
            null,
        );
        $process->setTimeout(3600);

        try {
            $process->mustRun();
        } catch (\Throwable $e) {
            $this->error('mysqldump failed: '.$e->getMessage());

            return self::FAILURE;
        } finally {
            if ($previousMysqlPwd === false) {
                putenv('MYSQL_PWD');
            } else {
                putenv('MYSQL_PWD='.$previousMysqlPwd);
            }
        }

        $gz = gzencode($process->getOutput(), 6);
        if ($gz === false) {
            $this->error('Could not gzip mysqldump output.');

            return self::FAILURE;
        }

        File::put($path, $gz);

        $this->info(sprintf('Backup written to %s (%s).', $path, $this->formatBytes(strlen($gz))));

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
