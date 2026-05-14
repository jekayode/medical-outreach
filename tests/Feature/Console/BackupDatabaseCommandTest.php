<?php

namespace Tests\Feature\Console;

use Tests\TestCase;

class BackupDatabaseCommandTest extends TestCase
{
    public function test_database_backup_skips_for_sqlite_testing_connection(): void
    {
        $this->artisan('database:backup')
            ->assertSuccessful()
            ->expectsOutputToContain('skipping');
    }
}
