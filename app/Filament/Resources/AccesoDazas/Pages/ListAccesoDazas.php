<?php

namespace App\Filament\Resources\AccesoDazas\Pages;

use App\Filament\Resources\AccesoDazas\AccesoDazaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAccesoDazas extends ListRecords
{
    protected static string $resource = AccesoDazaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
