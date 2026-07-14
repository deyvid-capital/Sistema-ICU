<?php

namespace App\Filament\Resources\ModeloLaptops\Pages;

use App\Filament\Resources\ModeloLaptops\ModeloLaptopResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageModeloLaptops extends ManageRecords
{
    protected static string $resource = ModeloLaptopResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo modelo')
                ->modalHeading('Registrar modelo de laptop')
                ->successNotificationTitle('Modelo registrado correctamente'),
        ];
    }
}
