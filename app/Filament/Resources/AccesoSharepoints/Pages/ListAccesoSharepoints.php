<?php

namespace App\Filament\Resources\AccesoSharepoints\Pages;

use App\Filament\Resources\AccesoSharepoints\AccesoSharepointResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAccesoSharepoints extends ListRecords
{
    protected static string $resource = AccesoSharepointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
