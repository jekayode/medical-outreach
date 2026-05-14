<?php

namespace Tests\Feature\Filament;

use App\Exports\BeneficiaryListExport;
use App\Exports\ImportListExport;
use App\Exports\OutreachListExport;
use App\Exports\UserListExport;
use App\Exports\VisitListExport;
use App\Filament\Resources\Beneficiaries\BeneficiaryResource;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Tests\TestCase;

class FilamentListExportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_beneficiaries_list_shows_export_to_excel(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(BeneficiaryResource::getUrl('index'))
            ->assertOk()
            ->assertSee(__('Export to Excel'));
    }

    public function test_beneficiary_list_excel_export_generates_workbook(): void
    {
        $binary = Excel::raw(new BeneficiaryListExport, ExcelWriter::XLSX);

        $this->assertGreaterThan(1500, strlen($binary));
    }

    public function test_outreach_list_excel_export_generates_workbook(): void
    {
        $binary = Excel::raw(new OutreachListExport, ExcelWriter::XLSX);

        $this->assertGreaterThan(1500, strlen($binary));
    }

    public function test_user_list_excel_export_generates_workbook(): void
    {
        $binary = Excel::raw(new UserListExport, ExcelWriter::XLSX);

        $this->assertGreaterThan(1500, strlen($binary));
    }

    public function test_visit_list_excel_export_generates_workbook(): void
    {
        $binary = Excel::raw(new VisitListExport, ExcelWriter::XLSX);

        $this->assertGreaterThan(1500, strlen($binary));
    }

    public function test_import_list_excel_export_generates_workbook(): void
    {
        $binary = Excel::raw(new ImportListExport, ExcelWriter::XLSX);

        $this->assertGreaterThan(1500, strlen($binary));
    }
}
