<?php

namespace App\Observers;

use Jeffgreco13\FilamentBreezy\Models\BreezySession;

class BreezySessionObserver
{
    /**
     * Los códigos de recuperación no se usan: no se persisten ni se regeneran.
     * El desbloqueo lo hace un administrador desde Seguridad del usuario.
     */
    public function saving(BreezySession $session): void
    {
        $session->two_factor_recovery_codes = null;
    }
}
