<?php

namespace App\Filament\Resources\EntregaEquipos\Pages;

use App\Filament\Resources\EntregaEquipos\EntregaEquipoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEntregaEquipos extends ListRecords
{
    protected static string $resource = EntregaEquipoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
