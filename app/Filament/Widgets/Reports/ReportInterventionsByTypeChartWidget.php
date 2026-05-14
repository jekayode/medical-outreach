<?php

namespace App\Filament\Widgets\Reports;

class ReportInterventionsByTypeChartWidget extends ReportChartWidget
{
    protected ?string $maxHeight = '320px';

    public function getHeading(): ?string
    {
        return __('Interventions by type');
    }

    protected function getType(): string
    {
        return 'pie';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $chart = $this->metrics()->interventionsByTypeChart($this->outreachId);

        return [
            'labels' => $chart['labels'],
            'datasets' => [
                [
                    'label' => __('Delivered'),
                    'data' => $chart['data'],
                    'backgroundColor' => [
                        'rgb(59, 130, 246)',
                        'rgb(16, 185, 129)',
                        'rgb(245, 158, 11)',
                    ],
                ],
            ],
        ];
    }
}
