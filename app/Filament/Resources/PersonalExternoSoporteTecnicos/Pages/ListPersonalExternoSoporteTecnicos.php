<?php

namespace App\Filament\Resources\PersonalExternoSoporteTecnicos\Pages;

use App\Filament\Resources\PersonalExternoSoporteTecnicos\PersonalExternoSoporteTecnicoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPersonalExternoSoporteTecnicos extends ListRecords
{
    protected static string $resource = PersonalExternoSoporteTecnicoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
