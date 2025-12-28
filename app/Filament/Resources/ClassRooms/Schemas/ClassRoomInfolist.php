<?php

namespace App\Filament\Resources\ClassRooms\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ClassRoomInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('academicYear.name')
                    ->label('Academic year'),
                TextEntry::make('name'),
                TextEntry::make('grade_level')
                    ->numeric(),
                TextEntry::make('homeroom_teacher')
                    ->placeholder('-'),
                TextEntry::make('capacity')
                    ->numeric(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
