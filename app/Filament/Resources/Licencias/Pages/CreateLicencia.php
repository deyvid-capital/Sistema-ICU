<?php

namespace App\Filament\Resources\Licencias\Pages;

use App\Filament\Resources\Licencias\LicenciaResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateLicencia extends CreateRecord
{
    protected static string $resource = LicenciaResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Licencia registrada')
            ->body('La licencia fue creada correctamente.');
    }
}
