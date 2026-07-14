<?php

namespace App\Filament\Resources\AsignacionEquipos\Pages;

use App\Filament\Resources\AsignacionEquipos\AsignacionEquipoResource;
use App\Models\AsignacionEquipo;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAsignacionEquipo extends ViewRecord
{
    protected static string $resource = AsignacionEquipoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Editar asignación')
                ->visible(fn (AsignacionEquipo $record): bool => ! $record->trashed()),
        ];
    }
}
