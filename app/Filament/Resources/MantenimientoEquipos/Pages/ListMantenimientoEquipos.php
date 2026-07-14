<?php

namespace App\Filament\Resources\MantenimientoEquipos\Pages;

use App\Filament\Resources\MantenimientoEquipos\MantenimientoEquipoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMantenimientoEquipos extends ListRecords
{
    protected static string $resource = MantenimientoEquipoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo mantenimiento de equipo'),
        ];
    }
}
