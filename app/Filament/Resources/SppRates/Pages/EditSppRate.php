<?php

namespace App\Filament\Resources\SppRates\Pages;

use App\Filament\Resources\SppRates\SppRateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSppRate extends EditRecord
{
    protected static string $resource = SppRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
