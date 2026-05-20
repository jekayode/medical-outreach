<?php

namespace App\Filament\Pages;

use App\Exports\OutreachReportExport;
use App\Filament\Widgets\Reports\ReportBeneficiaryAgeBandChartWidget;
use App\Filament\Widgets\Reports\ReportBeneficiaryGenderChartWidget;
use App\Filament\Widgets\Reports\ReportBloodPressureBandsChartWidget;
use App\Filament\Widgets\Reports\ReportBmiBandsChartWidget;
use App\Filament\Widgets\Reports\ReportHeadlineStatsWidget;
use App\Filament\Widgets\Reports\ReportHivStatusChartWidget;
use App\Filament\Widgets\Reports\ReportHourlyCheckInsChartWidget;
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
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Reports extends Page
{
    protected static string $routePath = 'reports';

    protected static ?int $navigationSort = 10;

    public ?string $outreachId = null;

    public static function getNavigationLabel(): string
    {
        return __('Reports');
    }

    public function getTitle(): string|Htmlable
    {
        return __('Reports');
    }

    public static function getNavigationIcon(): string|\BackedEnum|Htmlable|null
    {
        return Heroicon::OutlinedChartBar;
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadDatabase')
                ->label(__('Download Database'))
                ->icon(Heroicon::OutlinedCircleStack)
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading(__('Download database backup'))
                ->modalDescription(__('This will create a full database backup and download it to your device. Use this to keep a local copy of all outreach data.'))
                ->modalSubmitActionLabel(__('Download'))
                ->action(function (): BinaryFileResponse|StreamedResponse {
                    return $this->buildDatabaseDownload();
                }),
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

    private function buildDatabaseDownload(): BinaryFileResponse|StreamedResponse
    {
        $default = (string) config('database.default');
        $connection = config("database.connections.{$default}");
        $driver = is_array($connection) ? ($connection['driver'] ?? null) : null;

        if ($driver === 'mysql') {
            \Artisan::call('database:backup');

            $dir = storage_path('backups');
            $files = File::glob($dir.'/medical-outreach-*.sql.gz');

            if (empty($files)) {
                abort(500, 'Backup file not found after running database:backup.');
            }

            usort($files, fn (string $a, string $b): int => filemtime($b) - filemtime($a));
            $latest = $files[0];

            return response()->download(
                $latest,
                'medical-outreach-backup-'.now()->format('Y-m-d-His').'.sql.gz',
                ['Content-Type' => 'application/gzip']
            );
        }

        if ($driver === 'sqlite') {
            $dbPath = is_array($connection) ? ($connection['database'] ?? null) : null;
            if (! $dbPath || ! file_exists($dbPath)) {
                abort(500, 'SQLite database file not found.');
            }

            $filename = 'medical-outreach-backup-'.now()->format('Y-m-d-His').'.sqlite';

            return response()->streamDownload(
                function () use ($dbPath): void {
                    readfile($dbPath);
                },
                $filename,
                ['Content-Type' => 'application/octet-stream']
            );
        }

        abort(500, "Database download is not supported for the '{$driver}' driver.");
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
