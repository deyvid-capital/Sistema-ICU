<?php

namespace App\Filament\Resources\MantenimientoEquipos\Pages;

use App\Filament\Resources\MantenimientoEquipos\MantenimientoEquipoResource;
use App\Models\MantenimientoEquipo;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMantenimientoEquipo extends ViewRecord
{
    protected static string $resource = MantenimientoEquipoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Editar mantenimiento')
                ->visible(fn (MantenimientoEquipo $record): bool => ! $record->trashed()),
        ];
    }
}
