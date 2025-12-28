<?php

namespace App\Filament\Resources\Expenses\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('expense_number')
                    ->required(),
                TextInput::make('expense_category_id')
                    ->required()
                    ->numeric(),
                Select::make('account_id')
                    ->relationship('account', 'name')
                    ->required(),
                Select::make('academic_year_id')
                    ->relationship('academicYear', 'name')
                    ->required(),
                DatePicker::make('expense_date')
                    ->required(),
                TextInput::make('amount')
                    ->required()
                    ->numeric(),
                TextInput::make('vendor')
                    ->default(null),
                Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('receipt_file')
                    ->default(null),
                TextInput::make('approved_by')
                    ->numeric()
                    ->default(null),
                TextInput::make('created_by')
                    ->numeric()
                    ->default(null),
            ]);
    }
}
