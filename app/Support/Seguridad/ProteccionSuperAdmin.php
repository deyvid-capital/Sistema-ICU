<?php

namespace App\Support\Seguridad;

use App\Enums\TipoAcceso;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

/**
 * Reglas que impiden dejar al sistema sin un super_admin activo
 * y que un super_admin se auto-revoque acceso o roles en Seguridad.
 */
class ProteccionSuperAdmin
{
    public static function nombreRol(): string
    {
        return (string) config('filament-shield.super_admin.name', 'super_admin');
    }

    public static function esPropio(User $objetivo): bool
    {
        $authId = auth()->id();

        return $authId !== null && $objetivo->getKey() === $authId;
    }

    public static function esSuperAdminActivo(User $user): bool
    {
        return $user->hasRole(static::nombreRol())
            && $user->motivoAccesoDenegado() === null;
    }

    public static function existeOtroSuperAdminActivo(?User $excepto = null): bool
    {
        return static::superAdminsActivos($excepto)->isNotEmpty();
    }

    public static function puedeCerrarSesiones(User $objetivo): bool
    {
        return ! static::esPropio($objetivo);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $formState
     * @return list<string>
     */
    public static function erroresAlGuardar(User $objetivo, array $data, array $formState): array
    {
        $errores = [];
        $esPropio = static::esPropio($objetivo);

        $tipoAcceso = (string) ($formState['tipo_acceso'] ?? static::tipoAccesoDesdeExpiracion($objetivo->acceso_expira_at));
        $accesoHabilitado = (bool) ($data['acceso_habilitado'] ?? $objetivo->acceso_habilitado);
        $accesoExpiraAt = static::resolverExpiracion($tipoAcceso, $data, $objetivo);
        $roleIds = static::resolverRoleIds($data);

        if ($esPropio) {
            if ($accesoHabilitado !== (bool) $objetivo->acceso_habilitado) {
                $errores[] = 'No puedes deshabilitar ni vencer tu propio acceso. Pídele a otro super administrador con acceso vigente que lo haga.';
            }

            if (! static::mismaExpiracion($objetivo->acceso_expira_at, $accesoExpiraAt)) {
                $errores[] = 'No puedes deshabilitar ni vencer tu propio acceso. Pídele a otro super administrador con acceso vigente que lo haga.';
            }

            if ($roleIds !== null && $roleIds === []) {
                $errores[] = 'Debes conservar al menos un rol para no perder el acceso al panel.';
            }

            if ($roleIds !== null && ! static::quedaAlMenosUnSuperAdminActivo($objetivo, $roleIds, $accesoHabilitado, $accesoExpiraAt)) {
                $errores[] = 'No puedes quitar el rol super_admin: no hay otro super administrador con acceso vigente. El sistema quedaría sin nadie que pueda recuperarlo.';
            }
        } elseif (! static::quedaAlMenosUnSuperAdminActivo($objetivo, $roleIds, $accesoHabilitado, $accesoExpiraAt)) {
            $errores[] = 'No puedes quitar el rol super_admin: no hay otro super administrador con acceso vigente. El sistema quedaría sin nadie que pueda recuperarlo.';
        }

        return array_values(array_unique($errores));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<int>|null
     */
    private static function resolverRoleIds(array $data): ?array
    {
        if (! array_key_exists('roles', $data)) {
            return null;
        }

        $roles = $data['roles'];

        if ($roles === null) {
            return [];
        }

        return collect($roles)
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function resolverExpiracion(string $tipoAcceso, array $data, User $objetivo): ?Carbon
    {
        if ($tipoAcceso === TipoAcceso::Indefinido->value) {
            return null;
        }

        $state = $data['acceso_expira_at'] ?? $objetivo->acceso_expira_at;

        return $state !== null ? Carbon::parse($state) : null;
    }

    private static function tipoAccesoDesdeExpiracion(?Carbon $expiraAt): string
    {
        return $expiraAt !== null
            ? TipoAcceso::Definido->value
            : TipoAcceso::Indefinido->value;
    }

    private static function mismaExpiracion(?Carbon $original, ?Carbon $nuevo): bool
    {
        if ($original === null && $nuevo === null) {
            return true;
        }

        if ($original === null || $nuevo === null) {
            return false;
        }

        return $original->equalTo($nuevo);
    }

    /**
     * @param  list<int>|null  $roleIds
     */
    private static function quedaAlMenosUnSuperAdminActivo(
        User $objetivo,
        ?array $roleIds,
        bool $accesoHabilitado,
        ?Carbon $accesoExpiraAt,
    ): bool {
        if (static::idRolSuperAdmin() === null) {
            return true;
        }

        $activos = static::superAdminsActivos()
            ->reject(fn (User $user): bool => $user->is($objetivo))
            ->values();

        if (static::seriaSuperAdminActivo($objetivo, $roleIds, $accesoHabilitado, $accesoExpiraAt)) {
            $activos->push($objetivo);
        }

        return $activos->isNotEmpty();
    }

    /**
     * @param  list<int>|null  $roleIds
     */
    private static function seriaSuperAdminActivo(
        User $objetivo,
        ?array $roleIds,
        bool $accesoHabilitado,
        ?Carbon $accesoExpiraAt,
    ): bool {
        $rolSuperAdminId = static::idRolSuperAdmin();

        if ($rolSuperAdminId === null) {
            return false;
        }

        if (! static::tendraRol($objetivo, $roleIds, $rolSuperAdminId) || ! static::tendraAlgunRol($objetivo, $roleIds)) {
            return false;
        }

        if ($objetivo->trashed() || ! $accesoHabilitado || $objetivo->estaBloqueado()) {
            return false;
        }

        if ($accesoExpiraAt !== null && ! $accesoExpiraAt->isFuture()) {
            return false;
        }

        return true;
    }

    /**
     * @param  list<int>|null  $roleIds
     */
    private static function tendraRol(User $objetivo, ?array $roleIds, int $rolId): bool
    {
        if ($roleIds === null) {
            return $objetivo->hasRole(static::nombreRol());
        }

        return in_array($rolId, $roleIds, true);
    }

    /**
     * @param  list<int>|null  $roleIds
     */
    private static function tendraAlgunRol(User $objetivo, ?array $roleIds): bool
    {
        if ($roleIds === null) {
            return $objetivo->roles()->exists();
        }

        return $roleIds !== [];
    }

    /**
     * @return Collection<int, User>
     */
    private static function superAdminsActivos(?User $excepto = null): Collection
    {
        return User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', static::nombreRol()))
            ->when($excepto !== null, fn ($query) => $query->whereKeyNot($excepto->getKey()))
            ->get()
            ->filter(fn (User $user): bool => static::esSuperAdminActivo($user))
            ->values();
    }

    public static function idRolSuperAdmin(): ?int
    {
        $rol = Role::query()
            ->where('name', static::nombreRol())
            ->where('guard_name', 'web')
            ->first();

        return $rol?->id;
    }
}
