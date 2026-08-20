<?php

namespace App\Support\Sessions;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Jeffgreco13\FilamentBreezy\Agent;

/**
 * Consulta y termina las sesiones activas de un usuario en la tabla `sessions`.
 *
 * Reproduce el patrón del componente BrowserSessions de Breezy, pero apuntando
 * a un usuario arbitrario (para que un administrador pueda ver o cerrar las
 * sesiones de otro usuario, no solo las propias).
 */
class UserSessions
{
    /**
     * Sesiones activas del usuario, mapeadas con información de dispositivo.
     *
     * @return Collection<int, array{id: string, ip_address: ?string, browser: ?string, platform: ?string, is_desktop: bool, is_mobile: bool, last_active: int}>
     */
    public static function active(User $user): Collection
    {
        if (config('session.driver') !== 'database') {
            return collect();
        }

        return static::connection()
            ->where('user_id', $user->getAuthIdentifier())
            ->latest('last_activity')
            ->get()
            ->map(function (object $session): array {
                $agent = tap(new Agent, fn (Agent $agent) => $agent->setUserAgent($session->user_agent ?? ''));

                return [
                    'id' => $session->id,
                    'ip_address' => $session->ip_address,
                    'browser' => $agent->browser() ?: null,
                    'platform' => $agent->platform() ?: null,
                    'is_desktop' => $agent->isDesktop(),
                    'is_mobile' => $agent->isMobile(),
                    'last_active' => (int) $session->last_activity,
                ];
            });
    }

    /**
     * Cierra todas las sesiones del usuario. Devuelve cuántas se eliminaron.
     */
    public static function terminate(User $user): int
    {
        return static::connection()
            ->where('user_id', $user->getAuthIdentifier())
            ->delete();
    }

    protected static function connection(): Builder
    {
        return DB::connection(config('session.connection'))
            ->table(config('session.table', 'sessions'));
    }
}
