<?php

namespace App\Listeners;

use App\Models\User;
use App\Support\Auditoria\AuditorSeguridad;
use Illuminate\Auth\Events\Login;

class AuditarInicioSesion
{
    public function __construct(private readonly AuditorSeguridad $auditor) {}

    /**
     * Deja constancia de quién entró al sistema, desde qué IP y con qué navegador.
     */
    public function handle(Login $event): void
    {
        if ($event->user instanceof User) {
            $this->auditor->ingresoExitoso($event->user);
        }
    }
}
