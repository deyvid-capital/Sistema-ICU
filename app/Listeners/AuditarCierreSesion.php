<?php

namespace App\Listeners;

use App\Models\User;
use App\Support\Auditoria\AuditorSeguridad;
use Illuminate\Auth\Events\Logout;

class AuditarCierreSesion
{
    public function __construct(private readonly AuditorSeguridad $auditor) {}

    /**
     * Cubre el botón de salir, la cancelación del desafío de dos pasos y el
     * cierre automático de AuthenticateSession al cambiar la contraseña.
     */
    public function handle(Logout $event): void
    {
        if ($event->user instanceof User) {
            $this->auditor->cierreSesion($event->user);
        }
    }
}
