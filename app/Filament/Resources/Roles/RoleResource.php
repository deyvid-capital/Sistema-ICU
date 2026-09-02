<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles;

use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Filament\Resources\Roles\Pages\ViewRole;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource as BaseRoleResource;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Recurso de roles: hereda el de Filament Shield y solo sustituye las partes
 * del formulario de permisos que no escalan con ~50 recursos.
 *
 * Se hereda en lugar de usar `shield:publish` para no congelar una copia del
 * recurso de vendor: `form()`, `table()`, `getSlug()` y el resto siguen
 * viniendo del paquete y se actualizan con `composer update`.
 *
 * ⚠️ Sobrescribe API interna del trait
 * `BezhanSalleh\FilamentShield\Traits\HasShieldFormComponents`. Al actualizar
 * Shield a un major nuevo, comparar estos cuatro métodos con
 * `vendor/bezhansalleh/filament-shield/src/Traits/HasShieldFormComponents.php`.
 *
 * Qué se cambia y por qué:
 *  - La hidratación pasa de un `checkPermissionTo()` por permiso (~500, O(n²)
 *    contra la colección de permisos del rol) a una query y un `isset()`.
 *  - Se elimina el `->live()` de los CheckboxList y el `select_all` que
 *    recorría `getFlatComponents()` en cada hidratación y en cada clic.
 *  - Se añaden buscador, filtro por módulo y filtro por estado, porque con 48
 *    secciones encontrar un recurso a ojo era impracticable.
 *
 * @see PermissionMatrix
 */
class RoleResource extends BaseRoleResource
{
    /**
     * Clave del componente que agrupa "Marcar todo" / "Limpiar todo".
     */
    public const string SELECT_ALL_KEY = 'shield-select-all';

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'view' => ViewRole::route('/{record}'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }

    /**
     * Sin `->live()` ni recorrido del árbol de componentes: marcar un checkbox
     * ya no dispara una petición a Livewire ni re-renderiza el formulario.
     */
    public static function getCheckboxListFormComponent(string $name, array $options, bool $searchable = true, array|int|string|null $columns = null, array|int|string|null $columnSpan = null): Component
    {
        return CheckboxList::make($name)
            ->hiddenLabel()
            ->options($options)
            ->searchable($searchable)
            ->afterStateHydrated(function (CheckboxList $component, string $operation, ?Model $record) use ($options): void {
                if (! in_array($operation, ['edit', 'view'], true) || blank($record)) {
                    return;
                }

                $component->state(array_keys(array_intersect_key(
                    $options,
                    PermissionMatrix::grantedNames($record),
                )));
            })
            ->dehydratedWhenHidden()
            ->bulkToggleable()
            ->gridDirection('row')
            ->columns($columns ?? static::shield()->getCheckboxListColumns())
            ->columnSpan($columnSpan ?? static::shield()->getCheckboxListColumnSpan());
    }

    /**
     * Una sección por recurso, ocultable por los filtros de la pestaña.
     *
     * `dehydratedWhenHidden()` es obligatorio aquí: una `Section` oculta
     * propaga `isDehydrated: false` a sus hijos y borraría del state los
     * permisos de los recursos filtrados.
     */
    public static function getResourceEntitiesSchema(): ?array
    {
        return collect(PermissionMatrix::entities())
            ->map(fn (array $entity): Section => Section::make($entity['label'])
                ->key('shield-entity-'.Str::slug($entity['fqcn']))
                ->description(fn (): HtmlString => new HtmlString('<span style="word-break: break-word;">'.Utils::showModelPath($entity['modelFqcn']).'</span>'))
                ->compact()
                ->schema([
                    static::getCheckboxListFormComponent(
                        name: $entity['fqcn'],
                        options: $entity['options'],
                        searchable: false,
                        columns: static::shield()->getResourceCheckboxListColumns(),
                        columnSpan: static::shield()->getResourceCheckboxListColumnSpan(),
                    ),
                ])
                ->columnSpan(static::shield()->getSectionColumnSpan())
                ->collapsible()
                ->dehydratedWhenHidden()
                ->visible(fn (Get $get, ?Model $record): bool => PermissionMatrix::matchesFilters(
                    entity: $entity,
                    search: $get(PermissionMatrix::FILTER_SEARCH),
                    cluster: $get(PermissionMatrix::FILTER_CLUSTER),
                    estado: $get(PermissionMatrix::FILTER_ESTADO),
                    record: $record,
                )))
            ->all();
    }

    /**
     * Igual que la del paquete, con la barra de filtros delante del grid.
     */
    public static function getTabFormComponentForResources(): Component
    {
        if (static::shield()->hasSimpleResourcePermissionView()) {
            return parent::getTabFormComponentForResources();
        }

        return Tab::make('resources')
            ->label(__('filament-shield::filament-shield.resources'))
            ->visible(fn (): bool => Utils::isResourceTabEnabled())
            ->badge(static::getResourceTabBadgeCount())
            ->schema([
                static::getResourceFiltersFormComponent(),
                Grid::make()
                    ->schema(static::getResourceEntitiesSchema())
                    ->columns(static::shield()->getGridColumns()),
            ]);
    }

    /**
     * Reemplaza el toggle `select_all` del paquete, que forzaba `->live()` en
     * los ~51 CheckboxList y recorría `getFlatComponents()` en cada cambio.
     * Estas dos acciones hacen un único barrido sobre un mapa precomputado.
     */
    public static function getSelectAllFormComponent(): Component
    {
        return Actions::make([
            Action::make('marcarTodo')
                ->label(__('filament-shield::filament-shield.field.select_all.name'))
                ->icon(Heroicon::ShieldCheck)
                ->color('primary')
                ->action(function (Set $set): void {
                    foreach (PermissionMatrix::fieldOptions() as $field => $permissions) {
                        $set($field, $permissions);
                    }
                }),
            Action::make('limpiarTodo')
                ->label('Limpiar todo')
                ->icon(Heroicon::ShieldExclamation)
                ->color('gray')
                ->action(function (Set $set): void {
                    foreach (array_keys(PermissionMatrix::fieldOptions()) as $field) {
                        $set($field, []);
                    }
                }),
        ])
            ->key(self::SELECT_ALL_KEY)
            ->columnSpanFull();
    }

    /**
     * Buscador, módulo y estado. Los tres son campos de UI: `dehydrated(false)`
     * evita que sus valores acaben tratados como nombres de permiso.
     */
    protected static function getResourceFiltersFormComponent(): Component
    {
        return Section::make()
            ->compact()
            ->columnSpanFull()
            ->columns(['default' => 1, 'lg' => 3])
            ->schema([
                TextInput::make(PermissionMatrix::FILTER_SEARCH)
                    ->label('Buscar recurso')
                    ->placeholder('Nombre del recurso...')
                    ->prefixIcon(Heroicon::MagnifyingGlass)
                    ->live(debounce: 400)
                    ->dehydrated(false),
                Select::make(PermissionMatrix::FILTER_CLUSTER)
                    ->label('Módulo')
                    ->placeholder('Todos los módulos')
                    ->options(fn (): array => PermissionMatrix::clusters())
                    ->native(false)
                    ->live()
                    ->dehydrated(false),
                Select::make(PermissionMatrix::FILTER_ESTADO)
                    ->label('Estado')
                    ->placeholder('Todos los recursos')
                    ->options([
                        PermissionMatrix::ESTADO_CON_PERMISOS => 'Con permisos asignados',
                        PermissionMatrix::ESTADO_SIN_PERMISOS => 'Sin permisos',
                    ])
                    ->native(false)
                    ->live()
                    ->dehydrated(false),
            ]);
    }
}
