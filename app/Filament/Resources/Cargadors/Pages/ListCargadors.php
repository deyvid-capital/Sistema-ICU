<?php

namespace App\Filament\Resources\Cargadors\Pages;

use App\Filament\Resources\Cargadors\CargadorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCargadors extends ListRecords
{
    protected static string $resource = CargadorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuevo cargador'),
        ];
    }
}
