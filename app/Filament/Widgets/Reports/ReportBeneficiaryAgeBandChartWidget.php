<?php

namespace App\Filament\Widgets\Reports;

class ReportBeneficiaryAgeBandChartWidget extends ReportChartWidget
{
    protected ?string $maxHeight = '300px';

    public function getHeading(): ?string
    {
        return __('Beneficiaries by age band');
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
        $chart = $this->metrics()->beneficiariesByAgeBandChart($this->outreachId);

        return [
            'labels' => $chart['labels'],
            'datasets' => [
                [
                    'label' => __('Distinct beneficiaries'),
                    'data' => $chart['data'],
                    'backgroundColor' => 'rgb(124, 58, 237)',
                ],
            ],
        ];
    }
}
