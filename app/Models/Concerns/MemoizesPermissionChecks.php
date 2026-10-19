<?php

namespace App\Models\Concerns;

use App\Listeners\InvalidarPermisosMemorizados;
use BackedEnum;
use Closure;
use Spatie\Permission\Contracts\Permission;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

/**
 * Recuerda, solo durante la vida de la instancia, el resultado de
 * `hasPermissionTo()`. Spatie recorre todo el catálogo de permisos en cada
 * consulta y Filament pregunta lo mismo decenas de veces por página.
 *
 * Reglas de seguridad:
 *  - La memoria vive en la instancia, nunca en estado estático: dos usuarios
 *    jamás comparten respuestas, ni en workers de cola ni en Octane.
 *  - Se vacía cuando cambian los roles o permisos directos del usuario
 *    (`unsetRelation`/`setRelation` que hace Spatie en assign/remove/sync).
 *  - Se vacía en todas las instancias cuando se olvida la caché global de
 *    permisos (editar un rol, `permission:cache-reset`), vía
 *    {@see InvalidarPermisosMemorizados}.
 *  - No sobrevive a la serialización: `initializeMemoizesPermissionChecks`
 *    corre al construir y al deserializar el modelo.
 *
 * El modelo debe resolver el conflicto con HasRoles y exponer el método
 * original como `spatieHasPermissionTo`.
 */
trait MemoizesPermissionChecks
{
    /**
     * Generación global de permisos; cambia cada vez que Spatie olvida su caché.
     * Solo es un contador: no guarda resultados, así que no puede filtrar
     * permisos entre usuarios.
     */
    protected static int $permissionChecksGeneration = 0;

    /**
     * @var array<string, bool|PermissionDoesNotExist>
     */
    protected array $memoizedPermissionChecks = [];

    protected int $memoizedPermissionChecksGeneration = 0;

    public function initializeMemoizesPermissionChecks(): void
    {
        $this->forgetMemoizedPermissionChecks();
    }

    public static function invalidateAllMemoizedPermissionChecks(): void
    {
        static::$permissionChecksGeneration++;
    }

    public function forgetMemoizedPermissionChecks(): void
    {
        $this->memoizedPermissionChecks = [];
        $this->memoizedPermissionChecksGeneration = static::$permissionChecksGeneration;
    }

    /**
     * @param  string|int|Permission|BackedEnum  $permission
     *
     * @throws PermissionDoesNotExist
     */
    public function hasPermissionTo($permission, ?string $guardName = null): bool
    {
        $key = $this->memoizedPermissionCheckKey($permission, $guardName);

        if ($key === null) {
            return $this->spatieHasPermissionTo($permission, $guardName);
        }

        return $this->rememberPermissionCheck(
            $key,
            fn (): bool => $this->spatieHasPermissionTo($permission, $guardName),
        );
    }

    /**
     * @param  string  $relation
     * @return $this
     */
    public function setRelation($relation, $value)
    {
        $this->forgetMemoizedPermissionChecksFor($relation);

        return parent::setRelation($relation, $value);
    }

    /**
     * @param  string  $relation
     * @return $this
     */
    public function unsetRelation($relation)
    {
        $this->forgetMemoizedPermissionChecksFor($relation);

        return parent::unsetRelation($relation);
    }

    /**
     * @param  array<string, mixed>  $relations
     * @return $this
     */
    public function setRelations(array $relations)
    {
        $this->forgetMemoizedPermissionChecks();

        return parent::setRelations($relations);
    }

    /**
     * @return $this
     */
    public function unsetRelations()
    {
        $this->forgetMemoizedPermissionChecks();

        return parent::unsetRelations();
    }

    /**
     * @param  Closure(): bool  $resolver
     *
     * @throws PermissionDoesNotExist
     */
    protected function rememberPermissionCheck(string $key, Closure $resolver): bool
    {
        if ($this->memoizedPermissionChecksGeneration !== static::$permissionChecksGeneration) {
            $this->forgetMemoizedPermissionChecks();
        }

        if (! array_key_exists($key, $this->memoizedPermissionChecks)) {
            try {
                $this->memoizedPermissionChecks[$key] = $resolver();
            } catch (PermissionDoesNotExist $exception) {
                $this->memoizedPermissionChecks[$key] = $exception;
            }
        }

        $result = $this->memoizedPermissionChecks[$key];

        if ($result instanceof PermissionDoesNotExist) {
            throw $result;
        }

        return $result;
    }

    /**
     * Clave estable para la consulta, o null si el argumento no es memorizable.
     *
     * @param  mixed  $permission
     */
    protected function memoizedPermissionCheckKey($permission, ?string $guardName): ?string
    {
        if ($permission instanceof Permission) {
            $permission = 'id:'.$permission->getKey();
        } elseif ($permission instanceof BackedEnum) {
            $permission = (string) $permission->value;
        } elseif (is_int($permission)) {
            $permission = 'id:'.$permission;
        } elseif (! is_string($permission)) {
            return null;
        }

        return ($guardName ?? '').'|'.$permission;
    }

    protected function forgetMemoizedPermissionChecksFor(string $relation): void
    {
        if (in_array($relation, ['roles', 'permissions'], true)) {
            $this->forgetMemoizedPermissionChecks();
        }
    }
}
