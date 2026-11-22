<?php

namespace App\Listeners;

use App\Models\User;
use App\Support\Auditoria\AuditorSeguridad;
use CapitalSafi\Breezy\Events\TwoFactorCodeFailed;
use CapitalSafi\Breezy\Events\TwoFactorConfirmed;
use CapitalSafi\Breezy\Events\TwoFactorDisabled;

/**
 * Registra en la auditoría de seguridad lo que avisa el plugin de 2FA
 * (capitalsafi/filament-breezy). Laravel lo descubre por los métodos `handle*`.
 */
class AuditarDosFactores
{
    public function __construct(private readonly AuditorSeguridad $auditor) {}

    /**
     * Confirmación del 2FA, desde el asistente o desde «Mi perfil».
     */
    public function handleConfirmado(TwoFactorConfirmed $evento): void
    {
        if ($evento->user instanceof User) {
            $this->auditor->dosFactoresConfirmado($evento->user);
        }
    }

    /**
     * Código incorrecto en el desafío del login, en el asistente o en «Mi perfil».
     */
    public function handleCodigoIncorrecto(TwoFactorCodeFailed $evento): void
    {
        if ($evento->user instanceof User) {
            $this->auditor->dosFactoresCodigoIncorrecto($evento->user);
        }
    }

    /**
     * Solo se audita cuando un administrador quita el 2FA de **otra** cuenta, como hasta ahora.
     * Desactivar el propio desde «Mi perfil» no generaba registro.
     */
    public function handleDesactivado(TwoFactorDisabled $evento): void
    {
        if (! $evento->user instanceof User || ! $evento->actor instanceof User) {
            return;
        }

        if ($evento->actor->is($evento->user)) {
            return;
        }

        $this->auditor->dosFactoresReseteado($evento->user, $evento->actor);
    }
}
