<?php

namespace App\Support\Notifications;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Resuelve los administradores que deben recibir notificaciones de eventos críticos.
 *
 * Destinatarios: todos los usuarios con rol `super_admin` o `admin`.
 */
class AdminRecipients
{
    /**
     * @var array<int, string>
     */
    public const ADMIN_ROLES = ['super_admin', 'admin'];

    /**
     * Administradores a notificar, excluyendo opcionalmente al usuario que originó el evento.
     *
     * @return Collection<int, User>
     */
    public static function all(?User $except = null): Collection
    {
        // Se filtra por el nombre del rol con whereHas (en lugar del scope role())
        // para no lanzar excepción cuando alguno de los roles todavía no existe.
        return User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', self::ADMIN_ROLES))
            ->when($except !== null, fn ($query) => $query->whereKeyNot($except->getKey()))
            ->get();
    }
}
