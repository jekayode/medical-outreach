<?php

namespace App\Filament\Resources\Outreaches\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OutreachesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('location')
                    ->label(__('Location'))
                    ->searchable(),
                TextColumn::make('start_date')
                    ->label(__('Start'))
                    ->date()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label(__('End'))
                    ->date()
                    ->sortable(),
                TextColumn::make('code_prefix')
                    ->label(__('Prefix'))
                    ->badge(),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
