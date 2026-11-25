<?php

namespace App\Filament\Resources\SoftwareAreas\Pages;

use App\Filament\Resources\SoftwareAreas\SoftwareAreaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSoftwareAreas extends ListRecords
{
    protected static string $resource = SoftwareAreaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nueva relacion'),
        ];
    }
}
