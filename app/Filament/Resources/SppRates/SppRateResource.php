<?php

namespace App\Filament\Resources\SppRates;

use App\Filament\Resources\SppRates\Pages\CreateSppRate;
use App\Filament\Resources\SppRates\Pages\EditSppRate;
use App\Filament\Resources\SppRates\Pages\ListSppRates;
use App\Filament\Resources\SppRates\Schemas\SppRateForm;
use App\Filament\Resources\SppRates\Tables\SppRatesTable;
use App\Models\SppRate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SppRateResource extends Resource
{
    protected static ?string $model = SppRate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'SppRate';

    public static function form(Schema $schema): Schema
    {
        return SppRateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SppRatesTable::configure($table);
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
            'index' => ListSppRates::route('/'),
            'create' => CreateSppRate::route('/create'),
            'edit' => EditSppRate::route('/{record}/edit'),
        ];
    }
}
