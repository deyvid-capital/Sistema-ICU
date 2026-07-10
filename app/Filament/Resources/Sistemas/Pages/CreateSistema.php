<?php

namespace App\Filament\Resources\Sistemas\Pages;

use App\Filament\Resources\Sistemas\SistemaResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateSistema extends CreateRecord
{
    protected static string $resource = SistemaResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Sistema registrado')
            ->body('El sistema fue creado correctamente.');
    }
}
