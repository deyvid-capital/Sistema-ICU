<?php

namespace App\Filament\Resources\SoftwareUsuarios\Pages;

use App\Filament\Resources\SoftwareUsuarios\SoftwareUsuarioResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSoftwareUsuarios extends ListRecords
{
    protected static string $resource = SoftwareUsuarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nueva asignacion'),
        ];
    }
}
