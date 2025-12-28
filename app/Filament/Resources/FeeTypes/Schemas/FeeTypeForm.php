<?php

namespace App\Filament\Resources\FeeTypes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class FeeTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                Select::make('category')
                    ->options(['spp' => 'Spp', 'non_spp' => 'Non spp', 'bos' => 'Bos'])
                    ->required(),
                TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                Select::make('frequency')
                    ->options(['monthly' => 'Monthly', 'once' => 'Once', 'yearly' => 'Yearly'])
                    ->default('monthly')
                    ->required(),
                Textarea::make('description')
                    ->default(null)
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
