<?php

namespace App\Filament\Widgets\Reports;

class ReportHourlyCheckInsChartWidget extends ReportChartWidget
{
    protected ?string $maxHeight = '300px';

    public function getHeading(): ?string
    {
        return __('Hourly check-in rate');
    }

    protected function getType(): string
    {
        return 'line';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $chart = $this->metrics()->hourlyCheckInsChart($this->outreachId);

        return [
            'labels' => $chart['labels'],
            'datasets' => [
                [
                    'label' => __('Check-ins'),
                    'data' => $chart['data'],
                    'borderColor' => 'rgb(234, 88, 12)',
                    'backgroundColor' => 'rgba(234, 88, 12, 0.15)',
                    'fill' => true,
                    'tension' => 0.25,
                ],
            ],
        ];
    }
}
