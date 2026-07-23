<?php

namespace App\Filament\Resources\AsigCelulars\Pages;

use App\Filament\Resources\AsigCelulars\AsigCelularResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAsigCelulars extends ListRecords
{
    protected static string $resource = AsigCelularResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nueva asignación de celular'),
        ];
    }
}
