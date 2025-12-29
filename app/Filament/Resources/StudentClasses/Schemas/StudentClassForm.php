<?php

namespace App\Filament\Resources\StudentClasses\Schemas;

use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class StudentClassForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->components([
                        Select::make('academic_year_id')
                            ->label('Tahun Ajaran')
                            ->relationship('academicYear', 'name')
                            ->required()
                            ->native(false)
                            ->columnSpan(1),

                        Select::make('student_id')
                            ->label('Siswa')
                            ->relationship('student', 'name')
                            ->searchable()
                            ->required()
                            ->native(false)
                            ->columnSpan(1),

                        Select::make('class_id')
                            ->label('Kelas')
                            ->relationship('classRoom', 'name')
                            ->required()
                            ->native(false)
                            ->columnSpan(1),
                    ]),
            ]);
    }
}
