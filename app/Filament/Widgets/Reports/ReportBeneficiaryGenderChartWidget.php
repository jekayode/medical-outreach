<?php

namespace App\Filament\Widgets\Reports;

class ReportBeneficiaryGenderChartWidget extends ReportChartWidget
{
    protected ?string $maxHeight = '300px';

    public function getHeading(): ?string
    {
        return __('Beneficiaries by gender');
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
        $chart = $this->metrics()->beneficiariesByGenderChart($this->outreachId);

        return [
            'labels' => $chart['labels'],
            'datasets' => [
                [
                    'label' => __('Distinct beneficiaries'),
                    'data' => $chart['data'],
                    'backgroundColor' => 'rgb(217, 119, 6)',
                ],
            ],
        ];
    }
}
