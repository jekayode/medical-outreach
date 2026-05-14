<?php

namespace App\Filament\Widgets\Reports;

class ReportBloodPressureBandsChartWidget extends ReportChartWidget
{
    protected ?string $maxHeight = '300px';

    public function getHeading(): ?string
    {
        return __('Blood pressure risk bands');
    }

    public function getDescription(): ?string
    {
        return __('Based on paired systolic/diastolic vitals (adult-style bands).');
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
        $chart = $this->metrics()->bloodPressureRiskBandsChart($this->outreachId);

        return [
            'labels' => $chart['labels'],
            'datasets' => [
                [
                    'label' => __('Vitals records'),
                    'data' => $chart['data'],
                    'backgroundColor' => 'rgb(180, 83, 9)',
                ],
            ],
        ];
    }
}
