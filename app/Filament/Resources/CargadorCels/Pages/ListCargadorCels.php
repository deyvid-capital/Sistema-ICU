<?php

namespace App\Filament\Resources\CargadorCels\Pages;

use App\Filament\Resources\CargadorCels\CargadorCelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCargadorCels extends ListRecords
{
    protected static string $resource = CargadorCelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuevo cargador de celular'),
        ];
    }
}
