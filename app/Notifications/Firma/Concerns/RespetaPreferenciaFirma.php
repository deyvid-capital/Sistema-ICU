<?php

namespace App\Notifications\Firma\Concerns;

use App\Enums\TipoEventoNotificacion;
use App\Models\User;

/**
 * Los avisos de firma digital respetan la preferencia "Firma digital" del usuario
 * (Usuarios → Seguridad → Notificaciones), por canal.
 */
trait RespetaPreferenciaFirma
{
    /**
     * @param  list<string>  $canales
     * @return list<string>
     */
    protected function canalesPermitidos(object $notifiable, array $canales): array
    {
        if (! $notifiable instanceof User) {
            return $canales;
        }

        return array_values(array_filter(
            $canales,
            fn (string $canal): bool => $notifiable->debeRecibir(TipoEventoNotificacion::FirmaDigital->value, $canal),
        ));
    }
}
