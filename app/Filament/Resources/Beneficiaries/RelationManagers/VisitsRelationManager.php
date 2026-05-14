<?php

namespace App\Filament\Resources\Beneficiaries\RelationManagers;

use App\Filament\Resources\Visits\VisitResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VisitsRelationManager extends RelationManager
{
    protected static string $relationship = 'visits';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('check_in_code')
            ->columns([
                TextColumn::make('check_in_code')
                    ->label(__('Code'))
                    ->searchable()
                    ->url(fn ($record): string => VisitResource::getUrl('view', ['record' => $record])),
                TextColumn::make('outreach.name')
                    ->label(__('Outreach')),
                TextColumn::make('current_stage')
                    ->label(__('Stage'))
                    ->badge(),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge(),
                TextColumn::make('checked_in_at')
                    ->label(__('Checked in'))
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                //
            ]);
    }
}
