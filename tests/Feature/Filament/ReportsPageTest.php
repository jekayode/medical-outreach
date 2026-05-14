<?php

namespace Tests\Feature\Filament;

use App\Exports\OutreachReportExport;
use App\Filament\Pages\Reports;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Tests\TestCase;

class ReportsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_reports_page(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(Reports::getUrl())
            ->assertOk();
    }

    public function test_outreach_report_excel_export_is_non_empty(): void
    {
        $binary = Excel::raw(new OutreachReportExport(null, null), ExcelWriter::XLSX);

        $this->assertGreaterThan(2000, strlen($binary));
    }
}
