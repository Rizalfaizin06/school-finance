<?php

namespace App\Filament\Resources\Students\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StudentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nis')
                    ->searchable(),
                TextColumn::make('nisn')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('gender')
                    ->badge(),
                TextColumn::make('birth_place')
                    ->searchable(),
                TextColumn::make('birth_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('class.name')
                    ->searchable(),
                TextColumn::make('enrollment_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('parent_name')
                    ->searchable(),
                TextColumn::make('parent_phone')
                    ->searchable(),
                TextColumn::make('parent_email')
                    ->searchable(),
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
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
