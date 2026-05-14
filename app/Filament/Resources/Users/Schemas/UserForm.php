<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Filament\Resources\Users\Pages\CreateUser;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class UserForm
{
    /**
     * @return list<string>
     */
    public static function roleOptions(): array
    {
        return [
            'admin' => __('Admin'),
            'check_in' => __('Check-in'),
            'nurse' => __('Nurse'),
            'doctor' => __('Doctor'),
            'lab' => __('Lab'),
            'pharmacist' => __('Pharmacist'),
            'eye_care' => __('Eye care'),
            'dental_care' => __('Dental care'),
            'counsellor' => __('Counsellor'),
        ];
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label(__('Email address'))
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Select::make('role')
                    ->label(__('Role'))
                    ->options(self::roleOptions())
                    ->required()
                    ->native(false),
                TextInput::make('password')
                    ->label(__('Password'))
                    ->password()
                    ->revealable()
                    ->confirmed()
                    ->required(fn (mixed $livewire): bool => $livewire instanceof CreateUser)
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->minLength(8)
                    ->maxLength(255),
                TextInput::make('password_confirmation')
                    ->label(__('Confirm password'))
                    ->password()
                    ->revealable()
                    ->required(fn (Get $get, mixed $livewire): bool => $livewire instanceof CreateUser || filled($get('password')))
                    ->dehydrated(false),
                Toggle::make('is_active')
                    ->label(__('Active'))
                    ->default(true),
            ]);
    }
}
