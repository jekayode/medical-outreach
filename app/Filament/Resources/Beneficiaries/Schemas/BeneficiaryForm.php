<?php

namespace App\Filament\Resources\Beneficiaries\Schemas;

use App\Enums\BeneficiarySource;
use App\Enums\CommunicationPreference;
use App\Enums\Gender;
use App\Enums\MedicationStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BeneficiaryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('full_name')
                    ->label(__('Full name'))
                    ->required()
                    ->maxLength(255),
                Select::make('gender')
                    ->label(__('Gender'))
                    ->options(collect(Gender::cases())->mapWithKeys(fn (Gender $g): array => [$g->value => ucfirst($g->value)]))
                    ->required()
                    ->native(false),
                DatePicker::make('date_of_birth')
                    ->label(__('Date of birth'))
                    ->required()
                    ->native(false),
                TextInput::make('phone')
                    ->label(__('Phone'))
                    ->tel()
                    ->required()
                    ->maxLength(50),
                TextInput::make('email')
                    ->label(__('Email'))
                    ->email()
                    ->maxLength(255),
                Textarea::make('residential_address')
                    ->label(__('Residential address'))
                    ->required()
                    ->rows(2)
                    ->columnSpanFull(),
                Textarea::make('existing_medical_conditions')
                    ->label(__('Existing medical conditions'))
                    ->rows(2)
                    ->columnSpanFull(),
                Select::make('medication_status')
                    ->label(__('Medication status'))
                    ->options(collect(MedicationStatus::cases())->mapWithKeys(fn (MedicationStatus $m): array => [$m->value => ucfirst(str_replace('_', ' ', $m->name))]))
                    ->native(false),
                Textarea::make('medication_list')
                    ->label(__('Medication list'))
                    ->rows(2)
                    ->columnSpanFull(),
                Textarea::make('allergies')
                    ->label(__('Allergies'))
                    ->rows(2)
                    ->columnSpanFull(),
                TextInput::make('emergency_contact_name')
                    ->label(__('Emergency contact name'))
                    ->maxLength(255),
                TextInput::make('emergency_contact_relationship')
                    ->label(__('Emergency contact relationship'))
                    ->maxLength(255),
                TextInput::make('emergency_contact_number')
                    ->label(__('Emergency contact number'))
                    ->tel()
                    ->maxLength(50),
                Toggle::make('medical_consent')
                    ->label(__('Medical consent')),
                Select::make('communication_preference')
                    ->label(__('Communication preference'))
                    ->options(collect(CommunicationPreference::cases())->mapWithKeys(fn (CommunicationPreference $c): array => [$c->value => ucfirst(str_replace('_', ' ', $c->name))]))
                    ->native(false),
                Select::make('source')
                    ->label(__('Source'))
                    ->options(collect(BeneficiarySource::cases())->mapWithKeys(fn (BeneficiarySource $s): array => [$s->value => ucfirst(str_replace('_', ' ', $s->name))]))
                    ->required()
                    ->native(false),
            ]);
    }
}
