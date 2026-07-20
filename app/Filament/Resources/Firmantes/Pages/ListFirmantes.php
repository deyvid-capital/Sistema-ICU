<?php

namespace App\Filament\Resources\Firmantes\Pages;

use App\Filament\Resources\Firmantes\FirmanteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFirmantes extends ListRecords
{
    protected static string $resource = FirmanteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
