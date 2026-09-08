<?php

namespace App\Filament\Resources\AsignacionCargadors\Pages;

use App\Filament\Resources\AsignacionCargadors\Actions\UsarCargadorDeOtroEquipoAction;
use App\Filament\Resources\AsignacionCargadors\AsignacionCargadorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAsignacionCargadors extends ListRecords
{
    protected static string $resource = AsignacionCargadorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            UsarCargadorDeOtroEquipoAction::make(),
            CreateAction::make()
                ->label('Nueva asignación de cargador'),
        ];
    }
}
