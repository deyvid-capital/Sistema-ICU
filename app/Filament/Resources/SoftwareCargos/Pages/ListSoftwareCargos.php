<?php

namespace App\Filament\Resources\SoftwareCargos\Pages;

use App\Filament\Resources\SoftwareCargos\SoftwareCargoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSoftwareCargos extends ListRecords
{
    protected static string $resource = SoftwareCargoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nueva relacion'),
        ];
    }
}
