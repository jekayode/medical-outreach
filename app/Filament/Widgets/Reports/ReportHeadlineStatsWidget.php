<?php

namespace App\Filament\Widgets\Reports;

use App\Services\Reporting\OutreachReportMetrics;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ReportHeadlineStatsWidget extends StatsOverviewWidget
{
    public static function isDiscovered(): bool
    {
        return false;
    }

    public ?string $outreachId = null;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $h = app(OutreachReportMetrics::class)->headlines($this->outreachId);

        return [
            Stat::make(__('Beneficiaries served'), (string) $h['beneficiaries_served'])
                ->description(__('Unique people with a visit')),
            Stat::make(__('Interventions delivered'), (string) $h['interventions_delivered'])
                ->description(__('Care lines finished at a station or awaiting counselling only')),
            Stat::make(__('Drugs dispensed'), (string) $h['drugs_dispensed'])
                ->description(__('Prescription lines marked dispensed')),
            Stat::make(__('Lab tests completed'), (string) $h['lab_tests_completed'])
                ->description(__('Order lines with a recorded result')),
        ];
    }

    protected function getColumns(): int | array | null
    {
        return ['@lg' => 4, '@md' => 2, '@sm' => 1];
    }
}
