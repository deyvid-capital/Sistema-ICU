<?php

namespace App\Filament\Resources\SoftwareExcs\Pages;

use App\Filament\Resources\SoftwareExcs\SoftwareExcResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSoftwareExcs extends ListRecords
{
    protected static string $resource = SoftwareExcResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nueva excepcion'),
        ];
    }
}
