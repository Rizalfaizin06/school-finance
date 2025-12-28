<?php

namespace App\Filament\Resources\AcademicYears\Pages;

use App\Filament\Resources\AcademicYears\AcademicYearResource;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class TunggakanSPP extends Page
{
    use InteractsWithRecord;

    protected static string $resource = AcademicYearResource::class;

    protected string $view = 'filament.resources.academic-years.pages.tunggakan-s-p-p';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }
}
