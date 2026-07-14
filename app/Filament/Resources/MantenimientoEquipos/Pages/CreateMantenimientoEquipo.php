<?php

namespace App\Filament\Resources\MantenimientoEquipos\Pages;

use App\Filament\Resources\MantenimientoEquipos\MantenimientoEquipoResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateMantenimientoEquipo extends CreateRecord
{
    protected static string $resource = MantenimientoEquipoResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Mantenimiento registrado')
            ->body('El mantenimiento de equipo fue registrado correctamente.');
    }
}
