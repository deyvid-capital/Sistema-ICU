<?php

namespace App\Filament\Resources\LicenciasUsuarios\Pages;

use App\Filament\Resources\LicenciasUsuarios\LicenciaUsuarioResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateLicenciaUsuario extends CreateRecord
{
    protected static string $resource = LicenciaUsuarioResource::class;

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
            ->body('La licencia fue asignada al usuario correctamente.');
    }
}
