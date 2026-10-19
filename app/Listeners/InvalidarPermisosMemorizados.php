<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Cache\Events\KeyForgotten;

class InvalidarPermisosMemorizados
{
    /**
     * Cuando Spatie olvida su caché (se editó un rol o un permiso), descarta lo
     * que cada usuario cargado en este proceso tenga memorizado.
     */
    public function handle(KeyForgotten $event): void
    {
        if ($event->key === config('permission.cache.key')) {
            User::invalidateAllMemoizedPermissionChecks();
        }
    }
}
