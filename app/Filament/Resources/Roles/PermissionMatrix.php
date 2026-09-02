<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles;

use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Clusters\Cluster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Catálogo precomputado de entidades y permisos para el formulario de roles.
 *
 * ⚠️ Depende de API interna de Filament Shield: la forma del array que devuelve
 * `FilamentShield::getResources()` / `getPages()` / `getWidgets()`
 * (`resourceFqcn`, `model`, `modelFqcn`, `permissions[]['key'|'label']`).
 * Al actualizar Shield a un major nuevo, comparar con
 * `vendor/bezhansalleh/filament-shield/src/Concerns/HasEntityTransformers.php`.
 *
 * Existe para que el formulario no pague, en cada render, los `once()`
 * (que internamente hacen `debug_backtrace()`) ni un `checkPermissionTo()`
 * por cada uno de los ~500 permisos.
 *
 * @see RoleResource
 */
final class PermissionMatrix
{
    public const string FILTER_SEARCH = 'resource_search';

    public const string FILTER_CLUSTER = 'resource_cluster';

    public const string FILTER_ESTADO = 'resource_estado';

    public const string ESTADO_CON_PERMISOS = 'con_permisos';

    public const string ESTADO_SIN_PERMISOS = 'sin_permisos';

    public const string SIN_CLUSTER = '__sin_cluster__';

    /** @var array<string, array{fqcn: string, label: string, modelFqcn: string, options: array<string, string>, cluster: string, haystack: string}>|null */
    private static ?array $entities = null;

    /** @var array<string, array<string, int>> */
    private static array $grantedNames = [];

    /** @var array<string, string>|null */
    private static ?array $clusters = null;

    /** @var array<string, array<int, string>>|null */
    private static ?array $fieldOptions = null;

    /**
     * Entidades de tipo recurso, con sus permisos y metadatos de filtrado.
     *
     * @return array<string, array{fqcn: string, label: string, modelFqcn: string, options: array<string, string>, cluster: string, haystack: string}>
     */
    public static function entities(): array
    {
        return self::$entities ??= collect(FilamentShield::getResources())
            ->map(fn (array $entity): array => [
                'fqcn' => $entity['resourceFqcn'],
                'label' => $entity['model'],
                'modelFqcn' => $entity['modelFqcn'],
                'options' => collect($entity['permissions'])
                    ->mapWithKeys(fn (array $permission): array => [$permission['key'] => $permission['label']])
                    ->all(),
                'cluster' => self::resolveClusterKey($entity['resourceFqcn']),
                'haystack' => self::buildHaystack($entity['resourceFqcn'], $entity['model']),
            ])
            ->all();
    }

    /**
     * Nombres de los permisos que el rol ya tiene guardados, indexados para
     * consulta con `isset()`. Una query por rol y por request.
     *
     * @return array<string, int>
     */
    public static function grantedNames(?Model $record): array
    {
        if (blank($record)) {
            return [];
        }

        $cacheKey = (string) $record->getKey();

        return self::$grantedNames[$cacheKey] ??= $record->permissions // @phpstan-ignore-line
            ->pluck('name')
            ->flip()
            ->all();
    }

    /**
     * Opciones del select de módulo, ordenadas por etiqueta.
     *
     * @return array<string, string>
     */
    public static function clusters(): array
    {
        if (self::$clusters !== null) {
            return self::$clusters;
        }

        $clusters = collect(self::entities())
            ->pluck('cluster')
            ->unique()
            ->mapWithKeys(fn (string $cluster): array => [$cluster => self::clusterLabel($cluster)])
            ->sort()
            ->all();

        return self::$clusters = $clusters;
    }

    /**
     * Decide si la sección de un recurso debe verse con los filtros activos.
     *
     * @param  array{label: string, options: array<string, string>, cluster: string, haystack: string}  $entity
     */
    public static function matchesFilters(array $entity, ?string $search, ?string $cluster, ?string $estado, ?Model $record): bool
    {
        if (filled($cluster) && $entity['cluster'] !== $cluster) {
            return false;
        }

        if (filled($search) && ! str_contains($entity['haystack'], Str::lower(trim($search)))) {
            return false;
        }

        if (blank($estado)) {
            return true;
        }

        $tienePermisos = array_intersect_key(
            $entity['options'],
            self::grantedNames($record),
        ) !== [];

        return match ($estado) {
            self::ESTADO_CON_PERMISOS => $tienePermisos,
            self::ESTADO_SIN_PERMISOS => ! $tienePermisos,
            default => true,
        };
    }

    /**
     * Mapa `nombre de campo del formulario => claves de permiso`, para las
     * acciones "Marcar todo" / "Limpiar todo" sin recorrer el árbol del schema.
     *
     * @return array<string, array<int, string>>
     */
    public static function fieldOptions(): array
    {
        if (self::$fieldOptions !== null) {
            return self::$fieldOptions;
        }

        $fields = collect(self::entities())
            ->mapWithKeys(fn (array $entity): array => [$entity['fqcn'] => array_keys($entity['options'])])
            ->all();

        $fields['pages_tab'] = self::permissionKeysOf(FilamentShield::getPages());
        $fields['widgets_tab'] = self::permissionKeysOf(FilamentShield::getWidgets());
        $fields['custom_permissions_tab'] = array_keys(FilamentShield::getCustomPermissions() ?? []);

        return self::$fieldOptions = array_filter($fields);
    }

    /**
     * Extrae los nombres de permiso del state crudo del formulario.
     *
     * Se lee `$this->data` de la página en lugar del array dehidratado porque
     * las secciones que los filtros ocultan no se dehidratan.
     *
     * El recorrido va por lista blanca —solo los campos de `fieldOptions()` y,
     * dentro de cada uno, solo sus permisos válidos— porque `$this->data`
     * arrastra claves que no son campos del formulario (`created_at`,
     * `updated_at`… del registro) y una exclusión por nombre las dejaría pasar
     * como si fueran permisos.
     *
     * @param  array<string, mixed>  $state
     * @return Collection<int, string>
     */
    public static function permissionNamesFromFormState(array $state): Collection
    {
        $names = collect();

        foreach (self::fieldOptions() as $field => $permissions) {
            $selected = $state[$field] ?? null;

            if (! is_array($selected) || $selected === []) {
                continue;
            }

            $names = $names->concat(array_intersect($selected, $permissions));
        }

        return $names->unique()->values();
    }

    /**
     * Resuelve los modelos de permiso en 1-2 queries, creando los que falten.
     *
     * @param  Collection<int, string>  $names
     * @return Collection<int, Model>
     */
    public static function resolvePermissions(Collection $names, string $guard): Collection
    {
        if ($names->isEmpty()) {
            return collect();
        }

        $model = Utils::getPermissionModel();

        $existing = $model::query()
            ->whereIn('name', $names->all())
            ->where('guard_name', $guard)
            ->get();

        $created = $names
            ->diff($existing->pluck('name'))
            ->map(fn (string $name): Model => $model::create([
                'name' => $name,
                'guard_name' => $guard,
            ]));

        return $existing->concat($created);
    }

    /**
     * Vacía la memoización. Necesario entre asserts de un mismo test.
     */
    public static function flush(): void
    {
        self::$entities = null;
        self::$grantedNames = [];
        self::$clusters = null;
        self::$fieldOptions = null;
    }

    /**
     * @param  array<string, array{permissions?: array<string, string>}>|null  $entities
     * @return array<int, string>
     */
    private static function permissionKeysOf(?array $entities): array
    {
        return collect($entities)
            ->flatMap(fn (array $entity): array => array_keys($entity['permissions'] ?? []))
            ->unique()
            ->values()
            ->all();
    }

    private static function resolveClusterKey(string $resource): string
    {
        $cluster = $resource::getCluster();

        return filled($cluster) ? $cluster : self::SIN_CLUSTER;
    }

    private static function clusterLabel(string $cluster): string
    {
        if ($cluster === self::SIN_CLUSTER) {
            return 'Sin módulo';
        }

        /** @var class-string<Cluster> $cluster */
        return $cluster::getClusterBreadcrumb()
            ?? $cluster::getNavigationLabel();
    }

    /**
     * Texto contra el que busca el filtro: nombre del modelo más las etiquetas
     * de Filament, para que "celulares" encuentre el recurso `Celular`.
     */
    private static function buildHaystack(string $resource, string $model): string
    {
        return Str::lower(implode(' ', array_unique([
            $model,
            $resource::getModelLabel(),
            $resource::getPluralModelLabel(),
            $resource::getNavigationLabel(),
        ])));
    }
}
