<?php

namespace App\Filament\Resources\ReciboEquipos\Pages;

use App\Filament\Resources\ReciboEquipos\ReciboEquipoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReciboEquipos extends ListRecords
{
    protected static string $resource = ReciboEquipoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo recibo de equipo'),
        ];
    }
}
