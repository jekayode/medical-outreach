<?php

namespace App\Services\Reporting;

use App\Enums\DispensedStatus;
use App\Enums\Gender;
use App\Enums\HivStatus;
use App\Enums\InterventionStatus;
use App\Enums\InterventionType;
use App\Models\Beneficiary;
use App\Models\Consultation;
use App\Models\Intervention;
use App\Models\LabOrderItem;
use App\Models\PrescriptionItem;
use App\Models\Visit;
use App\Models\Vitals;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class OutreachReportMetrics
{
    /**
     * Headline totals for donor-style reports.
     *
     * `interventions_delivered` counts intervention rows in `completed` or `awaiting_counselling`
     * (clinical station work finished; counselling is a separate pastoral step).
     *
     * @return array{
     *     beneficiaries_served: int,
     *     interventions_delivered: int,
     *     drugs_dispensed: int,
     *     lab_tests_completed: int
     * }
     */
    public function headlines(?string $outreachId): array
    {
        $outreachId = $this->normalizeOutreachId($outreachId);

        return [
            'beneficiaries_served' => $this->distinctBeneficiariesWithVisitsCount($outreachId),
            'interventions_delivered' => $this->interventionsDeliveredCount($outreachId),
            'drugs_dispensed' => $this->dispensedPrescriptionItemsCount($outreachId),
            'lab_tests_completed' => $this->completedLabOrderItemsCount($outreachId),
        ];
    }

    /**
     * @return array{labels: list<string>, data: list<int>}
     */
    public function interventionsByTypeChart(?string $outreachId): array
    {
        $outreachId = $this->normalizeOutreachId($outreachId);

        $rows = Intervention::query()
            ->selectRaw('type as intervention_type, COUNT(*) as c')
            ->whereIn('status', $this->interventionStatusesCountedAsDelivered())
            ->whereHas('visit', fn (Builder $q) => $this->scopeVisitToOutreach($q, $outreachId))
            ->groupBy('type')
            ->pluck('c', 'intervention_type');

        $labels = [];
        $data = [];

        foreach (InterventionType::cases() as $case) {
            $labels[] = str_replace('_', ' ', ucwords($case->value, '_'));
            $data[] = (int) ($rows[$case->value] ?? 0);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * @return array{labels: list<string>, data: list<int>}
     */
    public function beneficiariesByGenderChart(?string $outreachId): array
    {
        $outreachId = $this->normalizeOutreachId($outreachId);

        $query = Beneficiary::query()
            ->join('visits', 'visits.beneficiary_id', '=', 'beneficiaries.id')
            ->when($outreachId !== null, fn (Builder $q) => $q->where('visits.outreach_id', $outreachId))
            ->selectRaw('beneficiaries.gender as g, COUNT(DISTINCT beneficiaries.id) as c')
            ->groupBy('beneficiaries.gender');

        $counts = $query->pluck('c', 'g');

        $labels = [];
        $data = [];

        foreach (Gender::cases() as $case) {
            $labels[] = str_replace('_', ' ', ucwords($case->value, '_'));
            $data[] = (int) ($counts[$case->value] ?? 0);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * @return array{labels: list<string>, data: list<int>}
     */
    public function beneficiariesByAgeBandChart(?string $outreachId): array
    {
        $outreachId = $this->normalizeOutreachId($outreachId);

        $bands = [
            '0–12' => 0,
            '13–17' => 0,
            '18–30' => 0,
            '31–50' => 0,
            '51–65' => 0,
            '65+' => 0,
        ];

        $seenBeneficiaryIds = [];

        Visit::query()
            ->when($outreachId !== null, fn (Builder $q) => $q->where('outreach_id', $outreachId))
            ->join('beneficiaries', 'beneficiaries.id', '=', 'visits.beneficiary_id')
            ->select(['beneficiaries.id', 'beneficiaries.date_of_birth'])
            ->lazy(500)
            ->each(function ($row) use (&$bands, &$seenBeneficiaryIds): void {
                if (isset($seenBeneficiaryIds[$row->id])) {
                    return;
                }

                $seenBeneficiaryIds[$row->id] = true;

                if ($row->date_of_birth === null) {
                    return;
                }

                $age = Carbon::parse($row->date_of_birth)->age;
                $key = match (true) {
                    $age <= 12 => '0–12',
                    $age <= 17 => '13–17',
                    $age <= 30 => '18–30',
                    $age <= 50 => '31–50',
                    $age <= 65 => '51–65',
                    default => '65+',
                };
                $bands[$key]++;
            });

        return [
            'labels' => array_keys($bands),
            'data' => array_values($bands),
        ];
    }

    /**
     * @return array{labels: list<string>, data: list<int>}
     */
    public function topDiagnosesChart(?string $outreachId, int $limit = 10): array
    {
        $outreachId = $this->normalizeOutreachId($outreachId);

        $rows = Consultation::query()
            ->whereNotNull('diagnosis')
            ->whereRaw('LENGTH(TRIM(diagnosis)) > 0')
            ->whereHas('intervention.visit', fn (Builder $q) => $this->scopeVisitToOutreach($q, $outreachId))
            ->selectRaw('TRIM(diagnosis) as label, COUNT(*) as c')
            ->groupBy(DB::raw('TRIM(diagnosis)'))
            ->orderByDesc('c')
            ->limit($limit)
            ->pluck('c', 'label');

        if ($rows->isEmpty()) {
            return ['labels' => [__('No data')], 'data' => [0]];
        }

        return [
            'labels' => $rows->keys()->map(fn (string $k): string => \Illuminate\Support\Str::limit($k, 48))->values()->all(),
            'data' => $rows->values()->map(fn ($v): int => (int) $v)->all(),
        ];
    }

    /**
     * @return array{labels: list<string>, data: list<int>}
     */
    public function topDrugsDispensedChart(?string $outreachId, int $limit = 10): array
    {
        $outreachId = $this->normalizeOutreachId($outreachId);

        $rows = PrescriptionItem::query()
            ->where('dispensed_status', DispensedStatus::Dispensed)
            ->whereHas('prescription.intervention.visit', fn (Builder $q) => $this->scopeVisitToOutreach($q, $outreachId))
            ->selectRaw('TRIM(drug_name) as label, COUNT(*) as c')
            ->groupBy(DB::raw('TRIM(drug_name)'))
            ->orderByDesc('c')
            ->limit($limit)
            ->pluck('c', 'label');

        if ($rows->isEmpty()) {
            return ['labels' => [__('No data')], 'data' => [0]];
        }

        return [
            'labels' => $rows->keys()->map(fn (string $k): string => \Illuminate\Support\Str::limit($k, 48))->values()->all(),
            'data' => $rows->values()->map(fn ($v): int => (int) $v)->all(),
        ];
    }

    /**
     * @return array{labels: list<string>, data: list<int>}
     */
    public function hivStatusChart(?string $outreachId): array
    {
        $outreachId = $this->normalizeOutreachId($outreachId);

        $rows = Vitals::query()
            ->join('visits', 'visits.id', '=', 'vitals.visit_id')
            ->whereIn('vitals.hiv_status', [HivStatus::Negative->value, HivStatus::Positive->value])
            ->when($outreachId !== null, fn (Builder $q) => $q->where('visits.outreach_id', $outreachId))
            ->selectRaw('vitals.hiv_status as s, COUNT(*) as c')
            ->groupBy('vitals.hiv_status')
            ->pluck('c', 's');

        $labels = [];
        $data = [];

        foreach ([HivStatus::Negative, HivStatus::Positive] as $case) {
            $labels[] = str_replace('_', ' ', ucwords($case->value, '_'));
            $data[] = (int) ($rows[$case->value] ?? 0);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * @return array{labels: list<string>, data: list<int>}
     */
    public function bloodPressureRiskBandsChart(?string $outreachId): array
    {
        $outreachId = $this->normalizeOutreachId($outreachId);

        $bands = [
            __('Normal') => 0,
            __('Elevated') => 0,
            __('Stage 1') => 0,
            __('Stage 2') => 0,
            __('Crisis') => 0,
        ];

        Vitals::query()
            ->join('visits', 'visits.id', '=', 'vitals.visit_id')
            ->when($outreachId !== null, fn (Builder $q) => $q->where('visits.outreach_id', $outreachId))
            ->whereNotNull('vitals.blood_pressure_systolic')
            ->whereNotNull('vitals.blood_pressure_diastolic')
            ->select(['vitals.blood_pressure_systolic', 'vitals.blood_pressure_diastolic'])
            ->lazy(500)
            ->each(function ($vital) use (&$bands): void {
                $sys = (int) $vital->blood_pressure_systolic;
                $dia = (int) $vital->blood_pressure_diastolic;
                $key = $this->classifyBloodPressureBand($sys, $dia);
                $bands[$key]++;
            });

        return [
            'labels' => array_keys($bands),
            'data' => array_values($bands),
        ];
    }

    /**
     * @return array{labels: list<string>, data: list<int>}
     */
    public function bmiBandsChart(?string $outreachId): array
    {
        $outreachId = $this->normalizeOutreachId($outreachId);

        $bands = [
            __('Underweight') => 0,
            __('Normal') => 0,
            __('Overweight') => 0,
            __('Obese') => 0,
        ];

        Vitals::query()
            ->join('visits', 'visits.id', '=', 'vitals.visit_id')
            ->when($outreachId !== null, fn (Builder $q) => $q->where('visits.outreach_id', $outreachId))
            ->whereNotNull('vitals.bmi')
            ->select(['vitals.bmi'])
            ->lazy(500)
            ->each(function ($vital) use (&$bands): void {
                $bmi = (float) $vital->bmi;
                $key = match (true) {
                    $bmi < 18.5 => __('Underweight'),
                    $bmi < 25 => __('Normal'),
                    $bmi < 30 => __('Overweight'),
                    default => __('Obese'),
                };
                $bands[$key]++;
            });

        return [
            'labels' => array_keys($bands),
            'data' => array_values($bands),
        ];
    }

    /**
     * @return array{labels: list<string>, data: list<int>}
     */
    public function hourlyCheckInsChart(?string $outreachId): array
    {
        $outreachId = $this->normalizeOutreachId($outreachId);

        $hours = array_fill(0, 24, 0);

        Visit::query()
            ->whereNotNull('checked_in_at')
            ->when($outreachId !== null, fn (Builder $q) => $q->where('outreach_id', $outreachId))
            ->select(['checked_in_at'])
            ->lazy(500)
            ->each(function ($visit) use (&$hours): void {
                $hour = (int) $visit->checked_in_at->timezone(config('app.timezone'))->format('G');
                $hours[$hour]++;
            });

        $labels = [];
        for ($h = 0; $h < 24; $h++) {
            $labels[] = sprintf('%02d:00', $h);
        }

        return ['labels' => $labels, 'data' => array_values($hours)];
    }

    private function normalizeOutreachId(?string $outreachId): ?string
    {
        if ($outreachId === null || $outreachId === '') {
            return null;
        }

        return $outreachId;
    }

    private function scopeVisitToOutreach(Builder $query, ?string $outreachId): void
    {
        if ($outreachId !== null) {
            $query->where('outreach_id', $outreachId);
        }
    }

    private function distinctBeneficiariesWithVisitsCount(?string $outreachId): int
    {
        return (int) Visit::query()
            ->when($outreachId !== null, fn (Builder $q) => $q->where('outreach_id', $outreachId))
            ->distinct()
            ->count('beneficiary_id');
    }

    /**
     * Intervention lines whose clinical station work is done: fully closed, or waiting only on counselling.
     *
     * @return list<InterventionStatus>
     */
    private function interventionStatusesCountedAsDelivered(): array
    {
        return [
            InterventionStatus::Completed,
            InterventionStatus::AwaitingCounselling,
        ];
    }

    private function interventionsDeliveredCount(?string $outreachId): int
    {
        return (int) Intervention::query()
            ->whereIn('status', $this->interventionStatusesCountedAsDelivered())
            ->whereHas('visit', fn (Builder $q) => $this->scopeVisitToOutreach($q, $outreachId))
            ->count();
    }

    private function dispensedPrescriptionItemsCount(?string $outreachId): int
    {
        return (int) PrescriptionItem::query()
            ->where('dispensed_status', DispensedStatus::Dispensed)
            ->whereHas('prescription.intervention.visit', fn (Builder $q) => $this->scopeVisitToOutreach($q, $outreachId))
            ->count();
    }

    private function completedLabOrderItemsCount(?string $outreachId): int
    {
        return (int) LabOrderItem::query()
            ->whereNotNull('result')
            ->whereRaw('LENGTH(TRIM(result)) > 0')
            ->whereHas('labOrder.consultation.intervention.visit', fn (Builder $q) => $this->scopeVisitToOutreach($q, $outreachId))
            ->count();
    }

    private function classifyBloodPressureBand(int $systolic, int $diastolic): string
    {
        if ($systolic >= 180 || $diastolic >= 120) {
            return __('Crisis');
        }

        if ($systolic >= 140 || $diastolic >= 90) {
            return __('Stage 2');
        }

        if ($systolic >= 130 || $diastolic >= 80) {
            return __('Stage 1');
        }

        if ($systolic >= 120 && $diastolic < 80) {
            return __('Elevated');
        }

        return __('Normal');
    }
}
