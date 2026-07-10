<?php

namespace App\Filament\Resources\AccesoInternets\Pages;

use App\Filament\Resources\AccesoInternets\AccesoInternetResource;
use App\Models\AccesoInternet;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAccesoInternet extends ViewRecord
{
    protected static string $resource = AccesoInternetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Editar asignación')
                ->visible(fn (AccesoInternet $record): bool => ! $record->trashed()),
        ];
    }
}
