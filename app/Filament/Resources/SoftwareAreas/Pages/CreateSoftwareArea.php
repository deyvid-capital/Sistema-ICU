<?php

namespace App\Filament\Resources\SoftwareAreas\Pages;

use App\Filament\Resources\SoftwareAreas\SoftwareAreaResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateSoftwareArea extends CreateRecord
{
    protected static string $resource = SoftwareAreaResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Relacion registrada')
            ->body('La relacion entre software y area fue creada correctamente.');
    }
}
