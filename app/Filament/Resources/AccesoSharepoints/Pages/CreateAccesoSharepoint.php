<?php

namespace App\Filament\Resources\AccesoSharepoints\Pages;

use App\Filament\Resources\AccesoSharepoints\AccesoSharepointResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateAccesoSharepoint extends CreateRecord
{
    protected static string $resource = AccesoSharepointResource::class;

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
            ->body('El acceso a SharePoint fue registrado correctamente.');
    }
}
