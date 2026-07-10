<?php

namespace App\Filament\Resources\AccesoInternets\Pages;

use App\Filament\Resources\AccesoInternets\AccesoInternetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAccesoInternets extends ListRecords
{
    protected static string $resource = AccesoInternetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
