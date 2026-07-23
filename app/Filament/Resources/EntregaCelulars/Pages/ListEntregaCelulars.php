<?php

namespace App\Filament\Resources\EntregaCelulars\Pages;

use App\Filament\Resources\EntregaCelulars\EntregaCelularResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEntregaCelulars extends ListRecords
{
    protected static string $resource = EntregaCelularResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
