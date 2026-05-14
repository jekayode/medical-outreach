<?php

namespace App\Exports;

use App\Services\Reporting\OutreachReportMetrics;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

final class OutreachReportExport implements WithMultipleSheets
{
    public function __construct(
        private readonly ?string $outreachId,
        private readonly ?string $outreachName,
    ) {}

    /**
     * @return array<int, FromArray&WithTitle>
     */
    public function sheets(): array
    {
        $metrics = app(OutreachReportMetrics::class);
        $id = $this->outreachId;

        return [
            new OutreachReportSummarySheet($this->outreachName, $metrics->headlines($id)),
            new OutreachReportChartSheet(__('Interventions by type'), $metrics->interventionsByTypeChart($id)),
            new OutreachReportChartSheet(__('Beneficiaries by gender'), $metrics->beneficiariesByGenderChart($id)),
            new OutreachReportChartSheet(__('Beneficiaries by age band'), $metrics->beneficiariesByAgeBandChart($id)),
            new OutreachReportChartSheet(__('Top diagnoses'), $metrics->topDiagnosesChart($id)),
            new OutreachReportChartSheet(__('Top drugs dispensed'), $metrics->topDrugsDispensedChart($id)),
            new OutreachReportChartSheet(__('HIV status tested'), $metrics->hivStatusChart($id)),
            new OutreachReportChartSheet(__('Blood pressure bands'), $metrics->bloodPressureRiskBandsChart($id)),
            new OutreachReportChartSheet(__('BMI bands'), $metrics->bmiBandsChart($id)),
            new OutreachReportChartSheet(__('Hourly check-ins'), $metrics->hourlyCheckInsChart($id)),
        ];
    }
}

final class OutreachReportSummarySheet implements FromArray, WithTitle
{
    /**
     * @param  array{
     *     beneficiaries_served: int,
     *     interventions_delivered: int,
     *     drugs_dispensed: int,
     *     lab_tests_completed: int
     * }  $headlines
     */
    public function __construct(
        private readonly ?string $outreachName,
        private readonly array $headlines,
    ) {}

    /**
     * @return list<list<string|int>>
     */
    public function array(): array
    {
        $scope = $this->outreachName ?? __('All outreaches');

        return [
            [__('Scope'), $scope],
            [],
            [__('Metric'), __('Value')],
            [__('Beneficiaries served'), $this->headlines['beneficiaries_served']],
            [__('Interventions delivered'), $this->headlines['interventions_delivered']],
            [__('Drugs dispensed'), $this->headlines['drugs_dispensed']],
            [__('Lab tests completed'), $this->headlines['lab_tests_completed']],
        ];
    }

    public function title(): string
    {
        return $this->sanitizeSheetTitle(__('Summary'));
    }

    private function sanitizeSheetTitle(string $title): string
    {
        $clean = (string) Str::of($title)->replace(['*', ':', '/', '\\', '?', '[', ']'], '');

        return Str::limit($clean, 31, '');
    }
}

final class OutreachReportChartSheet implements FromArray, WithTitle
{
    /**
     * @param  array{labels: list<string>, data: list<int>}  $chart
     */
    public function __construct(
        private readonly string $chartTitle,
        private readonly array $chart,
    ) {}

    /**
     * @return list<list<string|int>>
     */
    public function array(): array
    {
        $rows = [
            [__('Label'), __('Count')],
        ];

        foreach ($this->chart['labels'] as $index => $label) {
            $rows[] = [
                (string) $label,
                (int) ($this->chart['data'][$index] ?? 0),
            ];
        }

        return $rows;
    }

    public function title(): string
    {
        return $this->sanitizeSheetTitle($this->chartTitle);
    }

    private function sanitizeSheetTitle(string $title): string
    {
        $clean = (string) Str::of($title)->replace(['*', ':', '/', '\\', '?', '[', ']'], '');

        return Str::limit($clean, 31, '');
    }
}
