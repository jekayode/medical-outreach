<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\DatabaseBackupService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseBackupDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_sqlite_database_backup(): void
    {
        $this->seed(RoleSeeder::class);

        $dbPath = database_path('testing-backup.sqlite');
        if (file_exists($dbPath)) {
            unlink($dbPath);
        }
        touch($dbPath);

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => $dbPath,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.database-backup.download'))
            ->assertOk()
            ->assertHeader('content-disposition');

        unlink($dbPath);
    }

    public function test_non_admin_cannot_download_database_backup(): void
    {
        $this->seed(RoleSeeder::class);

        $nurse = User::factory()->create();
        $nurse->assignRole('nurse');

        $this->actingAs($nurse)
            ->get(route('admin.database-backup.download'))
            ->assertForbidden();
    }

    public function test_guest_cannot_download_database_backup(): void
    {
        $this->get(route('admin.database-backup.download'))
            ->assertRedirect();
    }

    public function test_backup_failure_redirects_back_to_reports_with_error(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => database_path('missing-backup.sqlite'),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.database-backup.download'))
            ->assertRedirect('/admin/reports')
            ->assertSessionHas('backup_error');
    }

    public function test_mysql_backup_falls_back_to_php_when_mysqldump_is_unavailable(): void
    {
        if (config('database.default') !== 'mysql') {
            $this->markTestSkipped('Requires a mysql connection.');
        }

        config(['backup.mysqldump_path' => '/nonexistent/mysqldump']);

        $path = app(DatabaseBackupService::class)->createBackup();

        $this->assertFileExists($path);
        $this->assertGreaterThan(100, filesize($path));
    }
}
