<?php

namespace App\Filament\Resources\PdfEquipos\Pages;

use App\Filament\Resources\PdfEquipos\PdfEquipoResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPdfEquipo extends ViewRecord
{
    protected static string $resource = PdfEquipoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
