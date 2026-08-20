<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;

class ResetearIntentosAlIniciarSesion
{
    /**
     * Reinicia el contador de intentos fallidos cuando el usuario entra bien.
     */
    public function handle(Login $event): void
    {
        if ($event->user instanceof User) {
            $event->user->resetearIntentosFallidos();
        }
    }
}
