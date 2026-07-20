<?php

namespace App\Filament\Resources\RecepcionEquipos\Pages;

use App\Filament\Resources\RecepcionEquipos\RecepcionEquipoResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRecepcionEquipo extends ViewRecord
{
    protected static string $resource = RecepcionEquipoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
