<?php

namespace App\Filament\Resources\ClassRooms\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ClassRoomForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('academic_year_id')
                    ->relationship('academicYear', 'name')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('grade_level')
                    ->required()
                    ->numeric(),
                TextInput::make('homeroom_teacher')
                    ->default(null),
                TextInput::make('capacity')
                    ->required()
                    ->numeric()
                    ->default(30),
            ]);
    }
}
