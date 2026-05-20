<?php

namespace Tests\Feature\Filament;

use App\Enums\BeneficiarySource;
use App\Enums\Gender;
use App\Enums\InterventionStatus;
use App\Enums\InterventionType;
use App\Enums\OutreachStatus;
use App\Enums\VisitStage;
use App\Enums\VisitStatus;
use App\Filament\Pages\ImpactReport;
use App\Models\Beneficiary;
use App\Models\Intervention;
use App\Models\Outreach;
use App\Models\User;
use App\Models\Visit;
use App\Services\Reporting\OutreachReportMetrics;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImpactReportTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    /**
     * @return array{outreach: Outreach, nurse: User}
     */
    private function seedOutreachWithVisits(): array
    {
        $nurse = User::factory()->create();

        $outreach = Outreach::query()->create([
            'name' => 'Impact Test Outreach',
            'location' => 'Community Hall',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'code_prefix' => 'ITX',
            'status' => OutreachStatus::Active,
            'next_check_in_sequence' => 0,
        ]);

        // Visit A: general + dental + eye (all three)
        $beneficiaryA = Beneficiary::query()->create([
            'full_name' => 'All Three Patient',
            'gender' => Gender::Female,
            'date_of_birth' => '1985-01-01',
            'phone' => '08011111111',
            'email' => null,
            'residential_address' => '1 Lane',
            'source' => BeneficiarySource::WalkIn,
            'imported_at' => null,
            'created_by_user_id' => null,
            'medical_consent' => true,
        ]);
        $visitA = Visit::query()->create([
            'beneficiary_id' => $beneficiaryA->getKey(),
            'outreach_id' => $outreach->getKey(),
            'check_in_code' => 'ITX-0001',
            'checked_in_at' => now(),
            'checked_in_by_user_id' => $nurse->getKey(),
            'current_stage' => VisitStage::VitalsDone,
            'status' => VisitStatus::Open,
        ]);
        Intervention::query()->create(['visit_id' => $visitA->getKey(), 'type' => InterventionType::GeneralConsultation, 'status' => InterventionStatus::Completed]);
        Intervention::query()->create(['visit_id' => $visitA->getKey(), 'type' => InterventionType::DentalCare, 'status' => InterventionStatus::Completed]);
        Intervention::query()->create(['visit_id' => $visitA->getKey(), 'type' => InterventionType::EyeCare, 'status' => InterventionStatus::Completed]);

        // Visit B: general only
        $beneficiaryB = Beneficiary::query()->create([
            'full_name' => 'General Only Patient',
            'gender' => Gender::Male,
            'date_of_birth' => '1990-06-15',
            'phone' => '08022222222',
            'email' => null,
            'residential_address' => '2 Lane',
            'source' => BeneficiarySource::WalkIn,
            'imported_at' => null,
            'created_by_user_id' => null,
            'medical_consent' => true,
        ]);
        $visitB = Visit::query()->create([
            'beneficiary_id' => $beneficiaryB->getKey(),
            'outreach_id' => $outreach->getKey(),
            'check_in_code' => 'ITX-0002',
            'checked_in_at' => now(),
            'checked_in_by_user_id' => $nurse->getKey(),
            'current_stage' => VisitStage::VitalsDone,
            'status' => VisitStatus::Open,
        ]);
        Intervention::query()->create(['visit_id' => $visitB->getKey(), 'type' => InterventionType::GeneralConsultation, 'status' => InterventionStatus::Completed]);

        // Visit C: dental only (pending — should NOT count)
        $beneficiaryC = Beneficiary::query()->create([
            'full_name' => 'Dental Pending Patient',
            'gender' => Gender::Male,
            'date_of_birth' => '2000-03-20',
            'phone' => '08033333333',
            'email' => null,
            'residential_address' => '3 Lane',
            'source' => BeneficiarySource::WalkIn,
            'imported_at' => null,
            'created_by_user_id' => null,
            'medical_consent' => true,
        ]);
        $visitC = Visit::query()->create([
            'beneficiary_id' => $beneficiaryC->getKey(),
            'outreach_id' => $outreach->getKey(),
            'check_in_code' => 'ITX-0003',
            'checked_in_at' => now(),
            'checked_in_by_user_id' => $nurse->getKey(),
            'current_stage' => VisitStage::CheckedIn,
            'status' => VisitStatus::Open,
        ]);
        Intervention::query()->create(['visit_id' => $visitC->getKey(), 'type' => InterventionType::DentalCare, 'status' => InterventionStatus::Pending]);

        return ['outreach' => $outreach, 'nurse' => $nurse];
    }

    public function test_impact_summary_counts_all_checked_in_visits(): void
    {
        ['outreach' => $outreach] = $this->seedOutreachWithVisits();

        $summary = app(OutreachReportMetrics::class)->impactSummary($outreach->getKey());

        $this->assertSame(3, $summary['total_checked_in']);
    }

    public function test_impact_summary_counts_delivered_interventions_by_type(): void
    {
        ['outreach' => $outreach] = $this->seedOutreachWithVisits();

        $summary = app(OutreachReportMetrics::class)->impactSummary($outreach->getKey());

        $this->assertSame(2, $summary['general_care'], 'Two completed general consultations expected');
        $this->assertSame(1, $summary['dental_care'], 'One completed dental care expected');
        $this->assertSame(1, $summary['eye_care'], 'One completed eye care expected');
    }

    public function test_impact_summary_counts_pending_interventions_as_zero(): void
    {
        ['outreach' => $outreach] = $this->seedOutreachWithVisits();

        $summary = app(OutreachReportMetrics::class)->impactSummary($outreach->getKey());

        // Visit C had dental pending — should not be included in dental_care count
        $this->assertSame(1, $summary['dental_care']);
    }

    public function test_impact_summary_counts_all_three_interventions(): void
    {
        ['outreach' => $outreach] = $this->seedOutreachWithVisits();

        $summary = app(OutreachReportMetrics::class)->impactSummary($outreach->getKey());

        $this->assertSame(1, $summary['all_interventions'], 'Only visit A had all three types completed');
    }

    public function test_impact_summary_returns_zero_for_empty_outreach(): void
    {
        $outreach = Outreach::query()->create([
            'name' => 'Empty Outreach',
            'location' => 'Hall',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'code_prefix' => 'EMP',
            'status' => OutreachStatus::Active,
            'next_check_in_sequence' => 0,
        ]);

        $summary = app(OutreachReportMetrics::class)->impactSummary($outreach->getKey());

        $this->assertSame(0, $summary['total_checked_in']);
        $this->assertSame(0, $summary['general_care']);
        $this->assertSame(0, $summary['dental_care']);
        $this->assertSame(0, $summary['eye_care']);
        $this->assertSame(0, $summary['all_interventions']);
    }

    public function test_admin_can_view_impact_report_page(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->get(ImpactReport::getUrl())
            ->assertOk();
    }
}
