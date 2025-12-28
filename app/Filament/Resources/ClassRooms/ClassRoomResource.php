<?php

namespace App\Filament\Resources\ClassRooms;

use App\Filament\Resources\ClassRooms\Pages\ManageClassRooms;
use App\Models\ClassRoom;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ClassRoomResource extends Resource
{
    protected static ?string $model = ClassRoom::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'ClassRoom';

    public static function form(Schema $schema): Schema
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

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('ClassRoom')
            ->columns([
                TextColumn::make('academicYear.name')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('grade_level')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('homeroom_teacher')
                    ->searchable(),
                TextColumn::make('capacity')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageClassRooms::route('/'),
        ];
    }
}
