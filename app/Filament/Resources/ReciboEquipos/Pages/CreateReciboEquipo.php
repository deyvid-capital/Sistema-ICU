<?php

namespace App\Filament\Resources\ReciboEquipos\Pages;

use App\Filament\Resources\ReciboEquipos\ReciboEquipoResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateReciboEquipo extends CreateRecord
{
    protected static string $resource = ReciboEquipoResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Recibo registrado')
            ->body('El recibo de equipo fue registrado correctamente.');
    }
}
