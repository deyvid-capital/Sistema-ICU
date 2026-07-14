<?php

namespace App\Filament\Resources\AsignacionCargadors\Pages;

use App\Filament\Resources\AsignacionCargadors\AsignacionCargadorResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateAsignacionCargador extends CreateRecord
{
    protected static string $resource = AsignacionCargadorResource::class;

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
            ->body('La asignación de cargador fue registrada correctamente.');
    }
}
