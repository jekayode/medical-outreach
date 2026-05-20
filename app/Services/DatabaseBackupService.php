<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Process\Process;

final class DatabaseBackupService
{
    /**
     * @return array<string, mixed>
     */
    public function defaultConnectionConfig(): array
    {
        $default = (string) config('database.default');
        $connection = config("database.connections.{$default}");

        if (! is_array($connection)) {
            throw new RuntimeException(__('Database connection configuration is invalid.'));
        }

        return $connection;
    }

    public function driver(): ?string
    {
        $connection = $this->defaultConnectionConfig();

        return is_string($connection['driver'] ?? null) ? $connection['driver'] : null;
    }

    /**
     * Create a backup file and return its absolute path.
     */
    public function createBackup(): string
    {
        $driver = $this->driver();

        return match ($driver) {
            'mysql' => $this->createMysqlBackup(),
            'sqlite' => $this->createSqliteBackupCopy(),
            default => throw new RuntimeException(
                __('Database download is not supported for the :driver driver.', ['driver' => (string) $driver])
            ),
        };
    }

    public function downloadResponse(): BinaryFileResponse|StreamedResponse
    {
        $driver = $this->driver();

        if ($driver === 'sqlite') {
            return $this->sqliteDownloadResponse();
        }

        $path = $this->createBackup();

        return response()->download(
            $path,
            'medical-outreach-backup-'.now()->format('Y-m-d-His').'.sql.gz',
            ['Content-Type' => 'application/gzip']
        );
    }

    private function createMysqlBackup(): string
    {
        $connection = $this->defaultConnectionConfig();
        $database = (string) ($connection['database'] ?? '');

        if ($database === '') {
            throw new RuntimeException(__('Database name is empty; check your configuration.'));
        }

        $dir = storage_path('backups');
        File::ensureDirectoryExists($dir);

        $path = $dir.DIRECTORY_SEPARATOR.sprintf('medical-outreach-%s.sql.gz', now()->format('Y-m-d-His'));

        $sql = $this->mysqldumpBinary() !== null
            ? $this->dumpMysqlViaBinary($connection)
            : app(MysqlPhpBackupDumper::class)->dump();

        $gz = gzencode($sql, 6);
        if ($gz === false) {
            throw new RuntimeException(__('Could not compress the database backup.'));
        }

        File::put($path, $gz);

        return $path;
    }

    /**
     * @param  array<string, mixed>  $connection
     */
    private function dumpMysqlViaBinary(array $connection): string
    {
        $process = new Process(
            $this->mysqldumpArguments($connection),
            base_path(),
        );
        $process->setTimeout(3600);

        try {
            $process->mustRun();
        } catch (\Throwable $exception) {
            throw new RuntimeException(
                __('Could not create database backup. Check database credentials and that mysqldump can connect.'),
                previous: $exception
            );
        }

        return $process->getOutput();
    }

    /**
     * @param  array<string, mixed>  $connection
     * @return list<string>
     */
    private function mysqldumpArguments(array $connection): array
    {
        $host = (string) ($connection['host'] ?? '127.0.0.1');
        $port = (int) ($connection['port'] ?? 3306);
        $database = (string) ($connection['database'] ?? '');
        $username = (string) ($connection['username'] ?? 'root');
        $password = (string) ($connection['password'] ?? '');
        $socket = (string) ($connection['unix_socket'] ?? '');

        $arguments = [
            $this->mysqldumpBinary(),
            '--single-transaction',
            '--no-tablespaces',
            '--routines',
            '--skip-comments',
        ];

        if ($socket !== '') {
            $arguments[] = '--socket='.$socket;
        } else {
            $arguments[] = '-h';
            $arguments[] = $host;
            $arguments[] = '-P';
            $arguments[] = (string) $port;
        }

        $arguments[] = '-u';
        $arguments[] = $username;

        if ($password !== '') {
            $arguments[] = '--password='.$password;
        }

        $arguments[] = $database;

        return $arguments;
    }

    private function mysqldumpBinary(): ?string
    {
        foreach ($this->mysqldumpCandidates() as $candidate) {
            if (is_executable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function mysqldumpCandidates(): array
    {
        $configured = trim((string) config('backup.mysqldump_path', ''));

        $discovered = [];
        foreach (['mysqldump', 'mariadb-dump'] as $binary) {
            $process = new Process(['command', '-v', $binary]);
            $process->run();

            if ($process->isSuccessful()) {
                $path = trim($process->getOutput());
                if ($path !== '') {
                    $discovered[] = $path;
                }
            }
        }

        return array_values(array_unique(array_filter([
            $configured !== '' ? $configured : null,
            ...$discovered,
            '/usr/bin/mysqldump',
            '/usr/bin/mariadb-dump',
            '/usr/local/bin/mysqldump',
            '/usr/local/bin/mariadb-dump',
            '/usr/local/mysql/bin/mysqldump',
            '/opt/homebrew/bin/mysqldump',
            '/Applications/Herd.app/Contents/Resources/bin/mysqldump',
        ])));
    }

    private function createSqliteBackupCopy(): string
    {
        $connection = $this->defaultConnectionConfig();
        $dbPath = (string) ($connection['database'] ?? '');

        if ($dbPath === '' || ! file_exists($dbPath)) {
            throw new RuntimeException(__('SQLite database file not found.'));
        }

        $dir = storage_path('backups');
        File::ensureDirectoryExists($dir);

        $path = $dir.DIRECTORY_SEPARATOR.sprintf('medical-outreach-%s.sqlite', now()->format('Y-m-d-His'));
        File::copy($dbPath, $path);

        return $path;
    }

    private function sqliteDownloadResponse(): StreamedResponse
    {
        $connection = $this->defaultConnectionConfig();
        $dbPath = (string) ($connection['database'] ?? '');

        if ($dbPath === '' || ! file_exists($dbPath)) {
            throw new RuntimeException(__('SQLite database file not found.'));
        }

        return response()->streamDownload(
            function () use ($dbPath): void {
                readfile($dbPath);
            },
            'medical-outreach-backup-'.now()->format('Y-m-d-His').'.sqlite',
            ['Content-Type' => 'application/octet-stream']
        );
    }
}
