<?php

namespace App\Filament\Resources\Payments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PaymentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('receipt_number'),
                TextEntry::make('student.name')
                    ->label('Student'),
                TextEntry::make('feeType.name')
                    ->label('Fee type'),
                TextEntry::make('account.name')
                    ->label('Account'),
                TextEntry::make('academicYear.name')
                    ->label('Academic year'),
                TextEntry::make('payment_date')
                    ->date(),
                TextEntry::make('month')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('year')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('amount')
                    ->numeric(),
                TextEntry::make('payment_method')
                    ->badge(),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
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
