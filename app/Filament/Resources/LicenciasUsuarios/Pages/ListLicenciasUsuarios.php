<?php

namespace App\Filament\Resources\LicenciasUsuarios\Pages;

use App\Filament\Resources\LicenciasUsuarios\LicenciaUsuarioResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLicenciasUsuarios extends ListRecords
{
    protected static string $resource = LicenciaUsuarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nueva asignación'),
        ];
    }
}
