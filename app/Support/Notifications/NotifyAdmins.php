<?php

namespace App\Support\Notifications;

use App\Models\User;
use App\Notifications\CriticalEventNotification;
use Illuminate\Support\Facades\Notification;

/**
 * Punto único para notificar a los administradores un evento crítico.
 *
 * Resuelve los destinatarios ({@see AdminRecipients}) y despacha una
 * {@see CriticalEventNotification} en cola (fan-out y correo en el worker).
 */
class NotifyAdmins
{
    /**
     * @param  string  $severity  Una de las constantes CriticalEventNotification::SEVERIDAD_*
     * @param  User|null  $actor  Usuario que originó el evento (se excluye de los destinatarios).
     *                            Si es null se usa el usuario autenticado (contexto web).
     */
    public static function dispatch(
        string $title,
        string $body,
        ?string $url = null,
        string $icon = 'heroicon-o-bell',
        string $color = 'warning',
        string $severity = CriticalEventNotification::SEVERIDAD_MEDIA,
        ?User $actor = null,
    ): void {
        $actor ??= auth()->user();

        $recipients = AdminRecipients::all($actor);

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send(
            $recipients,
            new CriticalEventNotification($title, $body, $url, $icon, $color, $severity),
        );
    }
}
