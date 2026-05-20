<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Reports\ReportImpactSummaryWidget;
use App\Models\Outreach;
use App\Services\Reporting\OutreachReportMetrics;
use Barryvdh\DomPDF\Facade\Pdf;
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
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImpactReport extends Page
{
    protected static string $routePath = 'impact-report';

    protected static ?int $navigationSort = 11;

    public ?string $outreachId = null;

    public static function getNavigationLabel(): string
    {
        return __('Impact Report');
    }

    public function getTitle(): string|Htmlable
    {
        return __('Impact Report');
    }

    public static function getNavigationIcon(): string|\BackedEnum|Htmlable|null
    {
        return Heroicon::OutlinedDocumentChartBar;
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadPdf')
                ->label(__('Download PDF'))
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('primary')
                ->action(function (): StreamedResponse {
                    $id = $this->normalizedOutreachId();
                    $outreach = $id ? Outreach::query()->whereKey($id)->first() : null;
                    $stats = app(OutreachReportMetrics::class)->impactSummary($id);

                    $pdf = Pdf::loadView('pdf.impact-report', [
                        'outreach' => $outreach,
                        'stats' => $stats,
                        'generatedAt' => now()->format('d M Y, H:i'),
                    ])->setPaper('a4', 'portrait');

                    $slug = Str::slug($outreach?->name ?? 'all-outreaches');
                    $filename = 'impact-report-'.$slug.'-'.now()->format('Y-m-d').'.pdf';

                    return response()->streamDownload(
                        fn () => print ($pdf->output()),
                        $filename,
                        ['Content-Type' => 'application/pdf']
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
            ReportImpactSummaryWidget::class,
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Outreach filter'))
                    ->description(__('Narrow the impact figures to a single outreach or view totals across all outreaches.'))
                    ->schema([
                        Select::make('outreachId')
                            ->label(__('Outreach'))
                            ->options(fn (): array => ['' => __('All outreaches')] + Outreach::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->native(false)
                            ->live(),
                    ]),
                Grid::make(1)
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
