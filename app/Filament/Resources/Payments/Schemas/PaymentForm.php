<?php

namespace App\Filament\Resources\Payments\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('receipt_number')
                    ->required(),
                Select::make('student_id')
                    ->relationship('student', 'name')
                    ->required(),
                Select::make('fee_type_id')
                    ->relationship('feeType', 'name')
                    ->required(),
                Select::make('account_id')
                    ->relationship('account', 'name')
                    ->required(),
                Select::make('academic_year_id')
                    ->relationship('academicYear', 'name')
                    ->required(),
                DatePicker::make('payment_date')
                    ->required(),
                TextInput::make('month')
                    ->numeric()
                    ->default(null),
                TextInput::make('year')
                    ->numeric()
                    ->default(null),
                TextInput::make('amount')
                    ->required()
                    ->numeric(),
                Select::make('payment_method')
                    ->options(['cash' => 'Cash', 'transfer' => 'Transfer', 'check' => 'Check'])
                    ->default('cash')
                    ->required(),
                Textarea::make('notes')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('created_by')
                    ->numeric()
                    ->default(null),
            ]);
    }
}
