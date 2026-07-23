<?php

namespace App\Filament\Resources\PdfCelulars\Pages;

use App\Filament\Resources\PdfCelulars\PdfCelularResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPdfCelulars extends ListRecords
{
    protected static string $resource = PdfCelularResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
