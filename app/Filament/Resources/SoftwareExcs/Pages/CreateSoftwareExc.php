<?php

namespace App\Filament\Resources\SoftwareExcs\Pages;

use App\Filament\Resources\SoftwareExcs\SoftwareExcResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateSoftwareExc extends CreateRecord
{
    protected static string $resource = SoftwareExcResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Excepcion registrada')
            ->body('La excepcion quedó pendiente de aprobación. Se notificó al aprobador por campana y correo.');
    }
}
