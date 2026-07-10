<?php

namespace App\Filament\Resources\AccUdCompartidas\Pages;

use App\Filament\Resources\AccUdCompartidas\AccUdCompartidaResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateAccUdCompartida extends CreateRecord
{
    protected static string $resource = AccUdCompartidaResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Asignación registrada')
            ->body('La unidad compartida fue asignada correctamente.');
    }
}
