<?php

namespace App\Filament\Resources\AccUdCompartidas\Pages;

use App\Filament\Resources\AccUdCompartidas\AccUdCompartidaResource;
use App\Models\AccUdCompartida;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAccUdCompartida extends ViewRecord
{
    protected static string $resource = AccUdCompartidaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Editar asignación')
                ->visible(fn (AccUdCompartida $record): bool => ! $record->trashed()),
        ];
    }
}
