<?php

namespace App\Observers;

use App\Filament\Resources\SoftwareExcs\SoftwareExcResource;
use App\Models\SoftwareExc;
use App\Models\User;
use App\Notifications\CriticalEventNotification;
use App\Support\Notifications\NotifyAdmins;

class SoftwareExcObserver
{
    public function created(SoftwareExc $excepcion): void
    {
        NotifyAdmins::dispatch(
            title: 'Excepción de software registrada',
            body: "Excepción «{$excepcion->tipo_excepcion}» para {$this->usuario($excepcion)} — por {$this->actor()}.",
            url: SoftwareExcResource::getUrl('view', ['record' => $excepcion]),
            icon: 'heroicon-o-shield-exclamation',
            color: 'warning',
            severity: CriticalEventNotification::SEVERIDAD_MEDIA,
        );
    }

    public function deleted(SoftwareExc $excepcion): void
    {
        NotifyAdmins::dispatch(
            title: 'Excepción de software eliminada',
            body: "Se eliminó la excepción «{$excepcion->tipo_excepcion}» de {$this->usuario($excepcion)} — por {$this->actor()}.",
            url: SoftwareExcResource::getUrl('index'),
            icon: 'heroicon-o-trash',
            color: 'danger',
            severity: CriticalEventNotification::SEVERIDAD_MEDIA,
        );
    }

    private function usuario(SoftwareExc $excepcion): string
    {
        return User::withTrashed()->find($excepcion->usuario_id)?->name ?? (string) $excepcion->usuario_id;
    }

    private function actor(): string
    {
        return auth()->user()?->name ?? 'Sistema';
    }
}
