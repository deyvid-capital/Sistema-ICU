<?php

namespace App\Filament\Resources\UsuarioExternos\Pages;

use App\Filament\Resources\UsuarioExternos\UsuarioExternoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUsuarioExternos extends ListRecords
{
    protected static string $resource = UsuarioExternoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
