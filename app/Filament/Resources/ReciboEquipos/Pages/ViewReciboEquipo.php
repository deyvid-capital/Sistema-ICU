<?php

namespace App\Filament\Resources\ReciboEquipos\Pages;

use App\Filament\Resources\ReciboEquipos\ReciboEquipoResource;
use App\Models\ReciboEquipo;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewReciboEquipo extends ViewRecord
{
    protected static string $resource = ReciboEquipoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Editar recibo')
                ->visible(fn (ReciboEquipo $record): bool => ! $record->trashed()),
        ];
    }
}
