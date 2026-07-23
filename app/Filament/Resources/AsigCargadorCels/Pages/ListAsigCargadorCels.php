<?php

namespace App\Filament\Resources\AsigCargadorCels\Pages;

use App\Filament\Resources\AsigCargadorCels\AsigCargadorCelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAsigCargadorCels extends ListRecords
{
    protected static string $resource = AsigCargadorCelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nueva asignación de cargador'),
        ];
    }
}
