<?php

namespace App\Filament\Resources\Equipos\Pages;

use App\Filament\Resources\Equipos\EquipoResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateEquipo extends CreateRecord
{
    protected static string $resource = EquipoResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Equipo registrado')
            ->body('El equipo fue registrado correctamente.');
    }
}
