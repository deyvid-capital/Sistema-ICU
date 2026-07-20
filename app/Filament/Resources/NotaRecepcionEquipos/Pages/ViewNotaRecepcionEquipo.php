<?php

namespace App\Filament\Resources\NotaRecepcionEquipos\Pages;

use App\Filament\Resources\NotaRecepcionEquipos\NotaRecepcionEquipoResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewNotaRecepcionEquipo extends ViewRecord
{
    protected static string $resource = NotaRecepcionEquipoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
