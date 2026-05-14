<?php

namespace App\Filament\Pages;

use App\Exports\OutreachReportExport;
use App\Filament\Widgets\Reports\ReportBeneficiaryAgeBandChartWidget;
use App\Filament\Widgets\Reports\ReportBeneficiaryGenderChartWidget;
use App\Filament\Widgets\Reports\ReportBloodPressureBandsChartWidget;
use App\Filament\Widgets\Reports\ReportBmiBandsChartWidget;
use App\Filament\Widgets\Reports\ReportHeadlineStatsWidget;
use App\Filament\Widgets\Reports\ReportHourlyCheckInsChartWidget;
use App\Filament\Widgets\Reports\ReportHivStatusChartWidget;
use App\Filament\Widgets\Reports\ReportInterventionsByTypeChartWidget;
use App\Filament\Widgets\Reports\ReportTopDiagnosesChartWidget;
use App\Filament\Widgets\Reports\ReportTopDrugsChartWidget;
use App\Models\Outreach;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class Reports extends Page
{
    protected static string $routePath = 'reports';

    protected static ?int $navigationSort = 10;

    public ?string $outreachId = null;

    public static function getNavigationLabel(): string
    {
        return __('Reports');
    }

    public function getTitle(): string | Htmlable
    {
        return __('Reports');
    }

    public static function getNavigationIcon(): string | \BackedEnum | Htmlable | null
    {
        return Heroicon::OutlinedChartBar;
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label(__('Export to Excel'))
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action(function (): BinaryFileResponse {
                    $id = $this->normalizedOutreachId();
                    $outreachName = $id
                        ? Outreach::query()->whereKey($id)->value('name')
                        : null;
                    $slug = Str::slug($outreachName ?? 'all-outreaches');
                    $filename = 'donor-report-'.$slug.'-'.now()->format('Y-m-d-His').'.xlsx';

                    return Excel::download(
                        new OutreachReportExport($id, $outreachName),
                        $filename
                    );
                }),
        ];
    }

    /**
     * @return array<class-string<Widget> | WidgetConfiguration>
     */
    protected function getReportWidgets(): array
    {
        return [
            ReportHeadlineStatsWidget::class,
            ReportInterventionsByTypeChartWidget::class,
            ReportBeneficiaryGenderChartWidget::class,
            ReportBeneficiaryAgeBandChartWidget::class,
            ReportTopDiagnosesChartWidget::class,
            ReportTopDrugsChartWidget::class,
            ReportHivStatusChartWidget::class,
            ReportBloodPressureBandsChartWidget::class,
            ReportBmiBandsChartWidget::class,
            ReportHourlyCheckInsChartWidget::class,
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Report scope'))
                    ->description(__('Aggregated metrics only — suitable for donor-facing summaries. Use the outreach filter to narrow results.'))
                    ->schema([
                        Select::make('outreachId')
                            ->label(__('Outreach'))
                            ->options(fn (): array => ['' => __('All outreaches')] + Outreach::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->native(false)
                            ->live(),
                    ]),
                Grid::make(2)
                    ->schema(fn (): array => $this->getWidgetsSchemaComponents($this->getReportWidgets())),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getWidgetData(): array
    {
        return [
            'outreachId' => $this->normalizedOutreachId(),
        ];
    }

    private function normalizedOutreachId(): ?string
    {
        if ($this->outreachId === null || $this->outreachId === '') {
            return null;
        }

        return $this->outreachId;
    }
}
