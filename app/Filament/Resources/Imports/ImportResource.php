<?php

namespace App\Filament\Resources\Imports;

use App\Filament\Resources\Imports\Pages\ManageImports;
use App\Models\Import;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ImportResource extends Resource
{
    protected static ?string $model = Import::class;

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('filename')
                    ->label(__('File'))
                    ->searchable(),
                TextColumn::make('outreach.name')
                    ->label(__('Outreach'))
                    ->sortable(),
                TextColumn::make('importedByUser.name')
                    ->label(__('Imported by')),
                TextColumn::make('total_rows')
                    ->label(__('Rows'))
                    ->numeric(),
                TextColumn::make('successful_rows')
                    ->label(__('OK'))
                    ->numeric(),
                TextColumn::make('failed_rows')
                    ->label(__('Failed'))
                    ->numeric(),
                TextColumn::make('created_at')
                    ->label(__('Imported at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
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
            'index' => ManageImports::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['outreach', 'importedByUser']);
    }
}
