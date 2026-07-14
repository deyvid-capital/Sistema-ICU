<?php

namespace App\Filament\Resources\Microprocesadors\Pages;

use App\Filament\Resources\Microprocesadors\MicroprocesadorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageMicroprocesadors extends ManageRecords
{
    protected static string $resource = MicroprocesadorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo microprocesador')
                ->modalHeading('Registrar microprocesador')
                ->successNotificationTitle('Microprocesador registrado correctamente'),
        ];
    }
}
