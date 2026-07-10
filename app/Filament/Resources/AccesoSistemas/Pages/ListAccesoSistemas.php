<?php

namespace App\Filament\Resources\AccesoSistemas\Pages;

use App\Filament\Resources\AccesoSistemas\AccesoSistemaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAccesoSistemas extends ListRecords
{
    protected static string $resource = AccesoSistemaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
