<?php

namespace App\Filament\Resources\AsignacionEquipos\Pages;

use App\Filament\Resources\AsignacionEquipos\AsignacionEquipoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAsignacionEquipos extends ListRecords
{
    protected static string $resource = AsignacionEquipoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nueva asignación de equipo'),
        ];
    }
}
