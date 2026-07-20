<?php

namespace App\Filament\Resources\UsuarioExternos\Pages;

use App\Filament\Resources\UsuarioExternos\UsuarioExternoResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewUsuarioExterno extends ViewRecord
{
    protected static string $resource = UsuarioExternoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
