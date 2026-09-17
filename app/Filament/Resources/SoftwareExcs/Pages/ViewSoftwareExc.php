<?php

namespace App\Filament\Resources\SoftwareExcs\Pages;

use App\Filament\Actions\AprobarExcepcionSoftwareAction;
use App\Filament\Actions\RechazarExcepcionSoftwareAction;
use App\Filament\Resources\SoftwareExcs\SoftwareExcResource;
use App\Models\SoftwareExc;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSoftwareExc extends ViewRecord
{
    protected static string $resource = SoftwareExcResource::class;

    protected function getHeaderActions(): array
    {
        return [
            AprobarExcepcionSoftwareAction::make(),
            RechazarExcepcionSoftwareAction::make(),
            EditAction::make()
                ->label('Editar excepcion')
                ->visible(fn (SoftwareExc $record): bool => ! $record->trashed()),
        ];
    }
}
