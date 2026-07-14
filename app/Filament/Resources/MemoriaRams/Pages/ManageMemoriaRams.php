<?php

namespace App\Filament\Resources\MemoriaRams\Pages;

use App\Filament\Resources\MemoriaRams\MemoriaRamResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageMemoriaRams extends ManageRecords
{
    protected static string $resource = MemoriaRamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nueva memoria RAM')
                ->modalHeading('Registrar memoria RAM')
                ->successNotificationTitle('Memoria RAM registrada correctamente'),
        ];
    }
}
