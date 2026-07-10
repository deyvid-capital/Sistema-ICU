<?php

namespace App\Filament\Resources\AccesoInternets\Pages;

use App\Filament\Resources\AccesoInternets\AccesoInternetResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateAccesoInternet extends CreateRecord
{
    protected static string $resource = AccesoInternetResource::class;

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
            ->body('El acceso a Internet fue registrado correctamente.');
    }
}
