<?php

namespace App\Filament\Resources\AccesoVirtuals\Pages;

use App\Filament\Resources\AccesoVirtuals\AccesoVirtualResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateAccesoVirtual extends CreateRecord
{
    protected static string $resource = AccesoVirtualResource::class;

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
            ->body('El acceso virtual fue registrado correctamente.');
    }
}
