<?php

namespace App\Filament\Resources\PdfCelulars\Pages;

use App\Filament\Resources\PdfCelulars\PdfCelularResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPdfCelular extends EditRecord
{
    protected static string $resource = PdfCelularResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
