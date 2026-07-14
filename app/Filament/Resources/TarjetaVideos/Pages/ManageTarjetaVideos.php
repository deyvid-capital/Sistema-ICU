<?php

namespace App\Filament\Resources\TarjetaVideos\Pages;

use App\Filament\Resources\TarjetaVideos\TarjetaVideoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTarjetaVideos extends ManageRecords
{
    protected static string $resource = TarjetaVideoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nueva tarjeta de video')
                ->modalHeading('Registrar tarjeta de video')
                ->successNotificationTitle('Tarjeta de video registrada correctamente'),
        ];
    }
}
