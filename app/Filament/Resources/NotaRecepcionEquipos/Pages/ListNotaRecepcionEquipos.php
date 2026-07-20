<?php

namespace App\Filament\Resources\NotaRecepcionEquipos\Pages;

use App\Filament\Resources\NotaRecepcionEquipos\NotaRecepcionEquipoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNotaRecepcionEquipos extends ListRecords
{
    protected static string $resource = NotaRecepcionEquipoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
