<?php

namespace App\Filament\Resources\Equipos\Pages;

use App\Filament\Resources\Equipos\EquipoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListEquipos extends ListRecords
{
    protected static string $resource = EquipoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo equipo')
                ->icon(Heroicon::Plus)
                ->color('primary')
                ->button()
                ->tooltip('Nuevo equipo'),
        ];
    }
}
