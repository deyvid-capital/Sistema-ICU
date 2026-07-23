<?php

namespace App\Filament\Resources\Celulares\Pages;

use App\Filament\Resources\Celulares\CelularResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCelulars extends ListRecords
{
    protected static string $resource = CelularResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuevo celular'),
        ];
    }
}
