<?php

namespace App\Filament\Widgets\Reports;

class ReportHivStatusChartWidget extends ReportChartWidget
{
    protected ?string $maxHeight = '280px';

    public function getHeading(): ?string
    {
        return __('HIV status (tested)');
    }

    public function getDescription(): ?string
    {
        return __('Excludes “not tested” and “declined”.');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $chart = $this->metrics()->hivStatusChart($this->outreachId);

        return [
            'labels' => $chart['labels'],
            'datasets' => [
                [
                    'label' => __('Vitals records'),
                    'data' => $chart['data'],
                    'backgroundColor' => [
                        'rgb(34, 197, 94)',
                        'rgb(239, 68, 68)',
                    ],
                ],
            ],
        ];
    }
}
