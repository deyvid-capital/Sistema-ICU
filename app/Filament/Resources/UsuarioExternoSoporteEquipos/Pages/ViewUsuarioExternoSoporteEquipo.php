<?php

namespace App\Filament\Resources\UsuarioExternoSoporteEquipos\Pages;

use App\Filament\Resources\UsuarioExternoSoporteEquipos\UsuarioExternoSoporteEquipoResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewUsuarioExternoSoporteEquipo extends ViewRecord
{
    protected static string $resource = UsuarioExternoSoporteEquipoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
