<?php

namespace App\Filament\Resources\Students\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class StudentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('nis'),
                TextEntry::make('nisn')
                    ->placeholder('-'),
                TextEntry::make('name'),
                TextEntry::make('gender')
                    ->badge(),
                TextEntry::make('birth_place')
                    ->placeholder('-'),
                TextEntry::make('birth_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('address')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('class.name')
                    ->label('Class')
                    ->placeholder('-'),
                TextEntry::make('enrollment_date')
                    ->date(),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('parent_name')
                    ->placeholder('-'),
                TextEntry::make('parent_phone')
                    ->placeholder('-'),
                TextEntry::make('parent_email')
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
