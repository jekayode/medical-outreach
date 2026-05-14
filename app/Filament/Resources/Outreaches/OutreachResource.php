<?php

namespace App\Filament\Resources\Outreaches;

use App\Filament\Resources\Outreaches\Pages\CreateOutreach;
use App\Filament\Resources\Outreaches\Pages\EditOutreach;
use App\Filament\Resources\Outreaches\Pages\ListOutreaches;
use App\Filament\Resources\Outreaches\Schemas\OutreachForm;
use App\Filament\Resources\Outreaches\Tables\OutreachesTable;
use App\Models\Outreach;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class OutreachResource extends Resource
{
    protected static ?string $model = Outreach::class;

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    public static function form(Schema $schema): Schema
    {
        return OutreachForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OutreachesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOutreaches::route('/'),
            'create' => CreateOutreach::route('/create'),
            'edit' => EditOutreach::route('/{record}/edit'),
        ];
    }
}
