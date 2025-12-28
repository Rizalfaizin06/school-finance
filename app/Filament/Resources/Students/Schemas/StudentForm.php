<?php

namespace App\Filament\Resources\Students\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class StudentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nis')
                    ->required(),
                TextInput::make('nisn')
                    ->default(null),
                TextInput::make('name')
                    ->required(),
                Select::make('gender')
                    ->options(['L' => 'L', 'P' => 'P'])
                    ->required(),
                TextInput::make('birth_place')
                    ->default(null),
                DatePicker::make('birth_date'),
                Textarea::make('address')
                    ->default(null)
                    ->columnSpanFull(),
                Select::make('class_id')
                    ->relationship('class', 'name')
                    ->default(null),
                DatePicker::make('enrollment_date')
                    ->required(),
                Select::make('status')
                    ->options([
            'active' => 'Active',
            'inactive' => 'Inactive',
            'graduated' => 'Graduated',
            'transferred' => 'Transferred',
        ])
                    ->default('active')
                    ->required(),
                TextInput::make('parent_name')
                    ->default(null),
                TextInput::make('parent_phone')
                    ->tel()
                    ->default(null),
                TextInput::make('parent_email')
                    ->email()
                    ->default(null),
            ]);
    }
}
