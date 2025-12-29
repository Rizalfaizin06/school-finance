<?php

namespace App\Filament\Resources\SppRates\Pages;

use App\Filament\Resources\SppRates\SppRateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSppRates extends ListRecords
{
    protected static string $resource = SppRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
