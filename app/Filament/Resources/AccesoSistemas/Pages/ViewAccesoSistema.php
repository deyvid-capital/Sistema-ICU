<?php

namespace App\Filament\Resources\AccesoSistemas\Pages;

use App\Filament\Resources\AccesoSistemas\AccesoSistemaResource;
use App\Models\AccesoSistema;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAccesoSistema extends ViewRecord
{
    protected static string $resource = AccesoSistemaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Editar asignación')
                ->visible(fn (AccesoSistema $record): bool => ! $record->trashed()),
        ];
    }
}
