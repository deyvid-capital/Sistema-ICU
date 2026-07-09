<?php

namespace App\Filament\Resources\LicenciasUsuarios\Pages;

use App\Filament\Resources\LicenciasUsuarios\LicenciaUsuarioResource;
use App\Models\LicenciaUsuario;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLicenciaUsuario extends ViewRecord
{
    protected static string $resource = LicenciaUsuarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Editar asignación')
                ->visible(fn (LicenciaUsuario $record): bool => ! $record->trashed()),
        ];
    }
}
