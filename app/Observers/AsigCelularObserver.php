<?php

namespace App\Observers;

use App\Models\AsigCelular;
use App\Models\User;
use App\Notifications\CriticalEventNotification;
use App\Support\Notifications\NotifyAdmins;

/**
 * Los celulares no tienen un Filament Resource propio, por eso estas
 * notificaciones no incluyen enlace directo (url = null).
 */
class AsigCelularObserver
{
    public function created(AsigCelular $asignacion): void
    {
        NotifyAdmins::dispatch(
            title: 'Asignación de celular registrada',
            body: "Celular #{$asignacion->celular_id} asignado a {$this->usuario($asignacion)} por {$this->actor()}.",
            icon: 'heroicon-o-device-phone-mobile',
            color: 'info',
            severity: CriticalEventNotification::SEVERIDAD_MEDIA,
        );
    }

    public function deleted(AsigCelular $asignacion): void
    {
        NotifyAdmins::dispatch(
            title: 'Asignación de celular eliminada',
            body: "Se eliminó la asignación del celular #{$asignacion->celular_id} ({$this->usuario($asignacion)}) — por {$this->actor()}.",
            icon: 'heroicon-o-trash',
            color: 'danger',
            severity: CriticalEventNotification::SEVERIDAD_MEDIA,
        );
    }

    private function usuario(AsigCelular $asignacion): string
    {
        return User::withTrashed()->find($asignacion->usuario_id)?->name ?? (string) $asignacion->usuario_id;
    }

    private function actor(): string
    {
        return auth()->user()?->name ?? 'Sistema';
    }
}
