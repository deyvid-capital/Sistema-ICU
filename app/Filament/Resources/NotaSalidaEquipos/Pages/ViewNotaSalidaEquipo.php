<?php

namespace App\Filament\Resources\NotaSalidaEquipos\Pages;

use App\Filament\Resources\NotaSalidaEquipos\NotaSalidaEquipoResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewNotaSalidaEquipo extends ViewRecord
{
    protected static string $resource = NotaSalidaEquipoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
