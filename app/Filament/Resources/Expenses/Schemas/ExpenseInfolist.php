<?php

namespace App\Filament\Resources\Expenses\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ExpenseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('expense_number'),
                TextEntry::make('expense_category_id')
                    ->numeric(),
                TextEntry::make('account.name')
                    ->label('Account'),
                TextEntry::make('academicYear.name')
                    ->label('Academic year'),
                TextEntry::make('expense_date')
                    ->date(),
                TextEntry::make('amount')
                    ->numeric(),
                TextEntry::make('vendor')
                    ->placeholder('-'),
                TextEntry::make('description')
                    ->columnSpanFull(),
                TextEntry::make('receipt_file')
                    ->placeholder('-'),
                TextEntry::make('approved_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('created_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
