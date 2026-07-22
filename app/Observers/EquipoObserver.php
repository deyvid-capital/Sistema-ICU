<?php

namespace App\Observers;

use App\Enums\EstadoEquipo;
use App\Filament\Resources\Equipos\EquipoResource;
use App\Models\Equipo;
use App\Notifications\CriticalEventNotification;
use App\Support\Notifications\NotifyAdmins;

class EquipoObserver
{
    /**
     * Avisa cuando un equipo se da de baja.
     */
    public function updated(Equipo $equipo): void
    {
        if (! $equipo->wasChanged('estado')) {
            return;
        }

        $estado = $equipo->estado instanceof EstadoEquipo
            ? $equipo->estado
            : EstadoEquipo::tryFrom((string) $equipo->estado);

        if ($estado !== EstadoEquipo::Baja) {
            return;
        }

        NotifyAdmins::dispatch(
            title: 'Equipo dado de baja',
            body: "El equipo {$equipo->getKey()} se dio de baja — por {$this->actor()}.",
            url: EquipoResource::getUrl('view', ['record' => $equipo]),
            icon: 'heroicon-o-exclamation-triangle',
            color: 'danger',
            severity: CriticalEventNotification::SEVERIDAD_ALTA,
        );
    }

    private function actor(): string
    {
        return auth()->user()?->name ?? 'Sistema';
    }
}
