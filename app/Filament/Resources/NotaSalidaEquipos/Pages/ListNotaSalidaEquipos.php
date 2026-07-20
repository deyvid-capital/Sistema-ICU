<?php

namespace App\Filament\Resources\NotaSalidaEquipos\Pages;

use App\Filament\Resources\NotaSalidaEquipos\NotaSalidaEquipoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNotaSalidaEquipos extends ListRecords
{
    protected static string $resource = NotaSalidaEquipoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
