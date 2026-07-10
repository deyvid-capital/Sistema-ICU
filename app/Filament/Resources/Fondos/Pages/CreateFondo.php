<?php

namespace App\Filament\Resources\Fondos\Pages;

use App\Filament\Resources\Fondos\FondoResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateFondo extends CreateRecord
{
    protected static string $resource = FondoResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Fondo registrado')
            ->body('El fondo fue creado correctamente.');
    }
}
