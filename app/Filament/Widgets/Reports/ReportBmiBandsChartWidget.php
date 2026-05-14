<?php

namespace App\Filament\Widgets\Reports;

class ReportBmiBandsChartWidget extends ReportChartWidget
{
    protected ?string $maxHeight = '300px';

    public function getHeading(): ?string
    {
        return __('BMI bands');
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
        $chart = $this->metrics()->bmiBandsChart($this->outreachId);

        return [
            'labels' => $chart['labels'],
            'datasets' => [
                [
                    'label' => __('Vitals records'),
                    'data' => $chart['data'],
                    'backgroundColor' => 'rgb(79, 70, 229)',
                ],
            ],
        ];
    }
}
