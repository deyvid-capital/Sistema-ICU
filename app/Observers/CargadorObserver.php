<?php

namespace App\Observers;

use App\Enums\EstadoCargador;
use App\Filament\Resources\Cargadors\CargadorResource;
use App\Models\Cargador;
use App\Notifications\CriticalEventNotification;
use App\Support\Notifications\NotifyAdmins;

class CargadorObserver
{
    /**
     * Avisa cuando un cargador pasa a un estado bloqueante (Dañado o Baja).
     */
    public function updated(Cargador $cargador): void
    {
        
        if (!$cargador->wasChanged('estado')) {
            return;
        }

        $estado = $cargador->estado instanceof EstadoCargador
            ? $cargador->estado
            : EstadoCargador::tryFrom((string) $cargador->estado);
        
        
        if ($estado === null || ! $estado->estaBloqueado()) {
            return;
        }

        NotifyAdmins::dispatch(
            title: "Cargador marcado como «{$estado->getLabel()}»",
            body: "El cargador {$cargador->getKey()} cambió a estado «{$estado->getLabel()}» — por {$this->actor()}.",
            url: CargadorResource::getUrl('view', ['record' => $cargador]),
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
