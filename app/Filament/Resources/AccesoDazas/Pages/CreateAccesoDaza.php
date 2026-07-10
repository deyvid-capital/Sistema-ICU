<?php

namespace App\Filament\Resources\AccesoDazas\Pages;

use App\Filament\Resources\AccesoDazas\AccesoDazaResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateAccesoDaza extends CreateRecord
{
    protected static string $resource = AccesoDazaResource::class;

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
            ->body('El acceso DAZA fue registrado correctamente.');
    }
}
