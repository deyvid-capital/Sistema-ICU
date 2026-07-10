<?php

namespace App\Filament\Resources\AccesoSharepoints\Pages;

use App\Filament\Resources\AccesoSharepoints\AccesoSharepointResource;
use App\Models\AccesoSharepoint;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAccesoSharepoint extends ViewRecord
{
    protected static string $resource = AccesoSharepointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Editar asignación')
                ->visible(fn (AccesoSharepoint $record): bool => ! $record->trashed()),
        ];
    }
}
