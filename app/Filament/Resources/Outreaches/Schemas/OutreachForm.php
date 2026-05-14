<?php

namespace App\Filament\Resources\Outreaches\Schemas;

use App\Enums\OutreachStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OutreachForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('location')
                    ->label(__('Location'))
                    ->required()
                    ->maxLength(255),
                DatePicker::make('start_date')
                    ->label(__('Start date'))
                    ->required()
                    ->native(false),
                DatePicker::make('end_date')
                    ->label(__('End date'))
                    ->required()
                    ->native(false),
                TextInput::make('code_prefix')
                    ->label(__('Code prefix'))
                    ->required()
                    ->maxLength(10)
                    ->extraInputAttributes(['style' => 'text-transform:uppercase'])
                    ->dehydrateStateUsing(fn (?string $state): ?string => $state ? strtoupper(trim($state)) : $state),
                Select::make('status')
                    ->label(__('Status'))
                    ->options(collect(OutreachStatus::cases())->mapWithKeys(fn (OutreachStatus $s): array => [$s->value => str_replace('_', ' ', ucfirst($s->name))]))
                    ->required()
                    ->native(false),
                Textarea::make('notes')
                    ->label(__('Notes'))
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }
}
