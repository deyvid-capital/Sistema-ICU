<?php

namespace App\Filament\Resources\SitioSharepoints\Pages;

use App\Filament\Resources\SitioSharepoints\SitioSharepointResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSitioSharepoints extends ListRecords
{
    protected static string $resource = SitioSharepointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
