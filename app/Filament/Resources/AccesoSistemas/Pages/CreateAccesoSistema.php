<?php

namespace App\Filament\Resources\AccesoSistemas\Pages;

use App\Filament\Resources\AccesoSistemas\AccesoSistemaResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateAccesoSistema extends CreateRecord
{
    protected static string $resource = AccesoSistemaResource::class;

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
            ->body('El acceso al sistema fue registrado correctamente.');
    }
}
