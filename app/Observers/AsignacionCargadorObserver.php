<?php

namespace App\Observers;

use App\Filament\Resources\AsignacionCargadors\AsignacionCargadorResource;
use App\Models\AsignacionCargador;
use App\Notifications\CriticalEventNotification;
use App\Support\Notifications\NotifyAdmins;

class AsignacionCargadorObserver
{
    public function created(AsignacionCargador $asignacion): void
    {
        NotifyAdmins::dispatch(
            title: 'Asignación de cargador registrada',
            body: "Cargador {$asignacion->serial_cargador} → equipo {$asignacion->service_tag}, por {$this->actor()}.",
            url: AsignacionCargadorResource::getUrl('view', ['record' => $asignacion]),
            icon: 'heroicon-o-bolt',
            color: 'warning',
            severity: CriticalEventNotification::SEVERIDAD_MEDIA,
        );
    }

    public function deleted(AsignacionCargador $asignacion): void
    {
        NotifyAdmins::dispatch(
            title: 'Asignación de cargador eliminada',
            body: "Se eliminó la asignación del cargador {$asignacion->serial_cargador} (equipo {$asignacion->service_tag}) — por {$this->actor()}.",
            url: AsignacionCargadorResource::getUrl('index'),
            icon: 'heroicon-o-trash',
            color: 'danger',
            severity: CriticalEventNotification::SEVERIDAD_MEDIA,
        );
    }

    private function actor(): string
    {
        return auth()->user()?->name ?? 'Sistema';
    }
}
