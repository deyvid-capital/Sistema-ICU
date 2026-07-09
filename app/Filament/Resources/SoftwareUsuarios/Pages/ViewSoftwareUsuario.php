<?php

namespace App\Filament\Resources\SoftwareUsuarios\Pages;

use App\Filament\Resources\SoftwareUsuarios\SoftwareUsuarioResource;
use App\Models\SoftwareUsuario;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSoftwareUsuario extends ViewRecord
{
    protected static string $resource = SoftwareUsuarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Editar asignacion')
                ->visible(fn (SoftwareUsuario $record): bool => ! $record->trashed()),
        ];
    }
}
