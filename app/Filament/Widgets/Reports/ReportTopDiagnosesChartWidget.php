<?php

namespace App\Filament\Widgets\Reports;

use Filament\Support\RawJs;

class ReportTopDiagnosesChartWidget extends ReportChartWidget
{
    protected ?string $maxHeight = '360px';

    public function getHeading(): ?string
    {
        return __('Top diagnoses');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array | RawJs | null
    {
        return RawJs::make(<<<'JS'
            {
                indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true } }
            }
        JS);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $chart = $this->metrics()->topDiagnosesChart($this->outreachId);

        return [
            'labels' => $chart['labels'],
            'datasets' => [
                [
                    'label' => __('Consultations'),
                    'data' => $chart['data'],
                    'backgroundColor' => 'rgb(37, 99, 235)',
                ],
            ],
        ];
    }
}
