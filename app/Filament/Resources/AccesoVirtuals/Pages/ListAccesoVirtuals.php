<?php

namespace App\Filament\Resources\AccesoVirtuals\Pages;

use App\Filament\Resources\AccesoVirtuals\AccesoVirtualResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAccesoVirtuals extends ListRecords
{
    protected static string $resource = AccesoVirtualResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
