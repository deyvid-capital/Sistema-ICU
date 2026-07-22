<?php

namespace App\Filament\Resources\Cargadors\Pages;

use App\Filament\Resources\Cargadors\CargadorResource;
use App\Models\Cargador;
use App\Models\AsignacionCargador;
use App\Enums\EstadoCargador;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateCargador extends CreateRecord
{
    protected static string $resource = CargadorResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Cargador registrado')
            ->body('El cargador fue registrado correctamente.');
    }

   
}
