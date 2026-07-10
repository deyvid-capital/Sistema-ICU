<?php

namespace App\Filament\Resources\AccUdCompartidas\Pages;

use App\Filament\Resources\AccUdCompartidas\AccUdCompartidaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAccUdCompartidas extends ListRecords
{
    protected static string $resource = AccUdCompartidaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
