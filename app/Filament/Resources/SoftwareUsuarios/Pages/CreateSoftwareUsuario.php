<?php

namespace App\Filament\Resources\SoftwareUsuarios\Pages;

use App\Filament\Resources\SoftwareUsuarios\SoftwareUsuarioResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateSoftwareUsuario extends CreateRecord
{
    protected static string $resource = SoftwareUsuarioResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Asignacion registrada')
            ->body('El software fue asignado correctamente al usuario.');
    }
}
