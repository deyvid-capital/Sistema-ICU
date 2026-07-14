<?php

namespace App\Filament\Resources\Equipos\Pages;

use App\Filament\Resources\Equipos\EquipoResource;
use App\Models\Equipo;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewEquipo extends ViewRecord
{
    protected static string $resource = EquipoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Editar equipo')
                ->visible(fn (Equipo $record): bool => ! $record->trashed()),
        ];
    }
}
