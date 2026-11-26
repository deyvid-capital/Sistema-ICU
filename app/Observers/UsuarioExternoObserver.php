<?php

namespace App\Observers;

use App\Models\UsuarioExterno;

class UsuarioExternoObserver
{
    /**
     * Si cambia la fecha fin, el recordatorio vuelve a quedar programado para
     * la nueva fecha, aunque ya se hubiera enviado el de la anterior.
     */
    public function updating(UsuarioExterno $usuarioExterno): void
    {
        if ($usuarioExterno->isDirty('fecha_fin')) {
            $usuarioExterno->recordatorio_enviado_at = null;
        }
    }
}
