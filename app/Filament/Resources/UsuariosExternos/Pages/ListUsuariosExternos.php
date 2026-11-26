<?php

namespace App\Filament\Resources\UsuariosExternos\Pages;

use App\Filament\Resources\UsuariosExternos\UsuarioExternoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUsuariosExternos extends ListRecords
{
    protected static string $resource = UsuarioExternoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo usuario externo'),
        ];
    }
}
