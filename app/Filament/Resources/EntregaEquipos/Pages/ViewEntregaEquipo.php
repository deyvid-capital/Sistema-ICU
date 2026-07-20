<?php

namespace App\Filament\Resources\EntregaEquipos\Pages;

use App\Filament\Resources\EntregaEquipos\EntregaEquipoResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewEntregaEquipo extends ViewRecord
{
    protected static string $resource = EntregaEquipoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
