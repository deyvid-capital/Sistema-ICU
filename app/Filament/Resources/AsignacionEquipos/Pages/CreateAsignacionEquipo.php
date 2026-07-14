<?php

namespace App\Filament\Resources\AsignacionEquipos\Pages;

use App\Filament\Resources\AsignacionEquipos\AsignacionEquipoResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateAsignacionEquipo extends CreateRecord
{
    protected static string $resource = AsignacionEquipoResource::class;

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
            ->body('La asignación de equipo fue registrada correctamente.');
    }
}
