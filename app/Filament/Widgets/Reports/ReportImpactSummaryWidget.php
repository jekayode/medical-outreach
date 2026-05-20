<?php

namespace App\Filament\Widgets\Reports;

use App\Services\Reporting\OutreachReportMetrics;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ReportImpactSummaryWidget extends StatsOverviewWidget
{
    public static function isDiscovered(): bool
    {
        return false;
    }

    public ?string $outreachId = null;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $s = app(OutreachReportMetrics::class)->impactSummary($this->outreachId);

        return [
            Stat::make(__('Total checked in'), (string) $s['total_checked_in'])
                ->description(__('Visit records created at check-in')),
            Stat::make(__('Received General Care'), (string) $s['general_care'])
                ->description(__('Completed general consultations')),
            Stat::make(__('Received Dental Care'), (string) $s['dental_care'])
                ->description(__('Completed dental examinations')),
            Stat::make(__('Received Eye Care'), (string) $s['eye_care'])
                ->description(__('Completed eye examinations')),
            Stat::make(__('Received All Three Types'), (string) $s['all_interventions'])
                ->description(__('Visits where general, dental and eye care were all delivered')),
        ];
    }

    protected function getColumns(): int|array|null
    {
        return ['@2xl' => 5, '@lg' => 3, '@md' => 2, '@sm' => 1];
    }
}
