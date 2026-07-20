<?php

namespace App\Filament\Resources\RecepcionEquipos\Pages;

use App\Filament\Resources\RecepcionEquipos\RecepcionEquipoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRecepcionEquipos extends ListRecords
{
    protected static string $resource = RecepcionEquipoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
