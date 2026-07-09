<?php

namespace App\Filament\Resources\Software\Pages;

use App\Filament\Resources\Software\SoftwareResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateSoftware extends CreateRecord
{
    protected static string $resource = SoftwareResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Software registrado')
            ->body('El software fue creado correctamente.');
    }
}
