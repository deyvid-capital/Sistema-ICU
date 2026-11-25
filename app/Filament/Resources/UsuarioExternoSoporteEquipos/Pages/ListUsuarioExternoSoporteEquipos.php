<?php

namespace App\Filament\Resources\UsuarioExternoSoporteEquipos\Pages;

use App\Filament\Resources\UsuarioExternoSoporteEquipos\UsuarioExternoSoporteEquipoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUsuarioExternoSoporteEquipos extends ListRecords
{
    protected static string $resource = UsuarioExternoSoporteEquipoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
