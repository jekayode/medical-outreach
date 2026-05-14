<?php

namespace App\Filament\Widgets\Reports;

use App\Services\Reporting\OutreachReportMetrics;
use Filament\Widgets\ChartWidget;

abstract class ReportChartWidget extends ChartWidget
{
    public static function isDiscovered(): bool
    {
        return false;
    }

    public ?string $outreachId = null;

    protected function metrics(): OutreachReportMetrics
    {
        return app(OutreachReportMetrics::class);
    }
}
