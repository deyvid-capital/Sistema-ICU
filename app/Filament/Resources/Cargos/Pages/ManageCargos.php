<?php

namespace App\Filament\Resources\Cargos\Pages;

use App\Filament\Resources\Cargos\CargoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCargos extends ManageRecords
{
    protected static string $resource = CargoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo cargo')
                ->modalHeading('Crear cargo')
                ->createAnother(false)
                ->successNotificationTitle('Cargo creado correctamente'),
        ];
    }
}
