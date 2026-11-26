<?php

namespace App\Filament\Resources\PersonalExternoSoporteTecnicos\Pages;

use App\Filament\Resources\PersonalExternoSoporteTecnicos\PersonalExternoSoporteTecnicoResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPersonalExternoSoporteTecnico extends ViewRecord
{
    protected static string $resource = PersonalExternoSoporteTecnicoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
