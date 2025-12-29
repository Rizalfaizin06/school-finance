<?php

namespace App\Filament\Resources\FeeRates\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;

class FeeRateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->components([
                        Select::make('fee_type_id')
                            ->label('Jenis Pemasukan')
                            ->relationship('feeType', 'name')
                            ->required()
                            ->native(false)
                            ->live(),

                        Select::make('academic_year_id')
                            ->label('Tahun Ajaran')
                            ->helperText('Isi jika tarif khusus untuk tahun ajaran tertentu (contoh: SPP)')
                            ->relationship('academicYear', 'name')
                            ->native(false)
                            ->searchable()
                            ->preload(),

                        TextInput::make('name')
                            ->label('Nama Item')
                            ->helperText('Contoh: Studi Tour, Seragam OSIS, dll')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('amount')
                            ->label('Harga')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->placeholder('0'),

                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(3)
                            ->columnSpanFull(),

                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->required(),
                    ]),
            ]);
    }
}
