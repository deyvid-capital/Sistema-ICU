<?php

namespace App\Filament\Resources\AccesoVirtuals\Pages;

use App\Filament\Resources\AccesoVirtuals\AccesoVirtualResource;
use App\Models\AccesoVirtual;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAccesoVirtual extends ViewRecord
{
    protected static string $resource = AccesoVirtualResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Editar asignación')
                ->visible(fn (AccesoVirtual $record): bool => ! $record->trashed()),
        ];
    }
}
