<?php

namespace App\Observers;

use App\Models\User;
use App\Services\Firma\SuspenderCertificadoFirma;

class UserObserver
{
    /**
     * Cualquier cambio de contraseña que no pase por Mi perfil (admin, recuperación
     * por correo, importación) deja inservible la contraseña envuelta del .p12.
     */
    public function updated(User $user): void
    {
        if ($user->wasChanged('password')) {
            app(SuspenderCertificadoFirma::class)->trasCambioDeContrasena($user);
        }
    }
}
