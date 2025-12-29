<?php

namespace App\Filament\Resources\FeeRates;

use App\Filament\Resources\FeeRates\Pages\CreateFeeRate;
use App\Filament\Resources\FeeRates\Pages\EditFeeRate;
use App\Filament\Resources\FeeRates\Pages\ListFeeRates;
use App\Filament\Resources\FeeRates\Pages\ViewFeeRate;
use App\Filament\Resources\FeeRates\Schemas\FeeRateForm;
use App\Filament\Resources\FeeRates\Schemas\FeeRateInfolist;
use App\Filament\Resources\FeeRates\Tables\FeeRatesTable;
use App\Models\FeeRate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class FeeRateResource extends Resource
{
    protected static ?string $model = FeeRate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Setting Pemasukan';

    protected static UnitEnum|string|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return FeeRateForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return FeeRateInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FeeRatesTable::configure($table);
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
            'index' => ListFeeRates::route('/'),
            'create' => CreateFeeRate::route('/create'),
            'view' => ViewFeeRate::route('/{record}'),
            'edit' => EditFeeRate::route('/{record}/edit'),
        ];
    }
}
