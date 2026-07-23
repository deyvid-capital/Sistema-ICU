<?php

namespace App\Filament\Resources\PdfCelulars\Pages;

use App\Filament\Resources\PdfCelulars\PdfCelularResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPdfCelular extends ViewRecord
{
    protected static string $resource = PdfCelularResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
