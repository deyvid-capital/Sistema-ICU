<?php

namespace App\Observers;

use App\Filament\Resources\AsignacionEquipos\AsignacionEquipoResource;
use App\Models\AsignacionEquipo;
use App\Models\User;
use App\Notifications\CriticalEventNotification;
use App\Support\Notifications\NotifyAdmins;

class AsignacionEquipoObserver
{
    public function created(AsignacionEquipo $asignacion): void
    {
        NotifyAdmins::dispatch(
            title: 'Nueva asignación de equipo',
            body: "Equipo {$asignacion->service_tag} asignado a {$this->usuario($asignacion)} por {$this->actor()}.",
            url: AsignacionEquipoResource::getUrl('view', ['record' => $asignacion]),
            icon: 'heroicon-o-computer-desktop',
            color: 'info',
            severity: CriticalEventNotification::SEVERIDAD_ALTA,
        );
    }

    public function deleted(AsignacionEquipo $asignacion): void
    {
        NotifyAdmins::dispatch(
            title: 'Asignación de equipo eliminada',
            body: "Se eliminó la asignación del equipo {$asignacion->service_tag} ({$this->usuario($asignacion)}) — por {$this->actor()}.",
            url: AsignacionEquipoResource::getUrl('index'),
            icon: 'heroicon-o-trash',
            color: 'danger',
            severity: CriticalEventNotification::SEVERIDAD_ALTA,
        );
    }

    public function forceDeleted(AsignacionEquipo $asignacion): void
    {
        NotifyAdmins::dispatch(
            title: 'Asignación de equipo eliminada permanentemente',
            body: "Se eliminó permanentemente la asignación del equipo {$asignacion->service_tag} ({$this->usuario($asignacion)}) — por {$this->actor()}.",
            url: AsignacionEquipoResource::getUrl('index'),
            icon: 'heroicon-o-trash',
            color: 'danger',
            severity: CriticalEventNotification::SEVERIDAD_ALTA,
        );
    }

    private function usuario(AsignacionEquipo $asignacion): string
    {
        return User::withTrashed()->find($asignacion->usuario_id)?->name ?? (string) $asignacion->usuario_id;
    }

    private function actor(): string
    {
        return auth()->user()?->name ?? 'Sistema';
    }
}
