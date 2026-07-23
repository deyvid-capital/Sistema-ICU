<?php

namespace App\Filament\Resources\PdfEquipos\Pages;

use App\Filament\Resources\PdfEquipos\PdfEquipoResource;
use Filament\Resources\Pages\ListRecords;

class ListPdfEquipos extends ListRecords
{
    protected static string $resource = PdfEquipoResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
