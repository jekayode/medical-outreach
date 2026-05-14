<?php

namespace App\Filament\Resources\Visits;

use App\Filament\Resources\Visits\Pages\ManageVisits;
use App\Filament\Resources\Visits\Pages\ViewVisit;
use App\Models\Visit;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VisitResource extends Resource
{
    protected static ?string $model = Visit::class;

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'check_in_code';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('check_in_code')->label(__('Check-in code')),
                TextEntry::make('beneficiary.full_name')->label(__('Beneficiary')),
                TextEntry::make('outreach.name')->label(__('Outreach')),
                TextEntry::make('current_stage')->label(__('Stage'))->badge(),
                TextEntry::make('status')->label(__('Visit status'))->badge(),
                TextEntry::make('checked_in_at')->label(__('Checked in'))->dateTime(),
                TextEntry::make('completed_at')->label(__('Completed at'))->dateTime(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Visit $record): string => static::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('check_in_code')
                    ->label(__('Code'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('beneficiary.full_name')
                    ->label(__('Beneficiary'))
                    ->searchable(),
                TextColumn::make('outreach.name')
                    ->label(__('Outreach'))
                    ->sortable(),
                TextColumn::make('current_stage')
                    ->label(__('Stage'))
                    ->badge(),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge(),
                TextColumn::make('checked_in_at')
                    ->label(__('Checked in'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageVisits::route('/'),
            'view' => ViewVisit::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['beneficiary', 'outreach', 'checkedInByUser']);
    }
}
