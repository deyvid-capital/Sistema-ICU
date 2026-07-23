<?php

namespace App\Filament\Resources\RecepcionCelulars\Pages;

use App\Filament\Resources\RecepcionCelulars\RecepcionCelularResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRecepcionCelulars extends ListRecords
{
    protected static string $resource = RecepcionCelularResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
