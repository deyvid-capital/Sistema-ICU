<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\PermissionMatrix;
use App\Filament\Resources\Roles\RoleResource;
use App\Models\User;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionsFormTest extends TestCase
{
    use RefreshDatabase;

    private Role $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('shield:generate', [
            '--all' => true,
            '--panel' => 'admin',
            '--option' => 'permissions',
            '--ignore-existing-policies' => true,
        ]);

        $this->superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $this->superAdmin->syncPermissions(Permission::query()->get());

        $admin = User::factory()->create();
        $admin->assignRole($this->superAdmin);

        $this->actingAs($admin);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();

        PermissionMatrix::flush();
    }

    public function test_no_se_generan_permisos_replicate_ni_reorder(): void
    {
        $permisos = collect(FilamentShield::getEntitiesPermissions());

        $this->assertNotEmpty($permisos);
        $this->assertEmpty($permisos->filter(fn (string $p): bool => str_starts_with($p, 'Replicate:')));
        $this->assertEmpty($permisos->filter(fn (string $p): bool => str_starts_with($p, 'Reorder:')));
        $this->assertSame(0, Permission::query()
            ->where('name', 'like', 'Replicate:%')
            ->orWhere('name', 'like', 'Reorder:%')
            ->count());
    }

    public function test_las_policies_no_declaran_replicate_ni_reorder(): void
    {
        foreach (glob(app_path('Policies/*.php')) as $policy) {
            $contenido = file_get_contents($policy);

            $this->assertStringNotContainsString('function replicate', $contenido, basename($policy));
            $this->assertStringNotContainsString('function reorder', $contenido, basename($policy));
        }
    }

    public function test_la_pagina_de_edicion_renderiza_con_todos_los_permisos(): void
    {
        $this->get(RoleResource::getUrl('edit', ['record' => $this->superAdmin]))
            ->assertSuccessful()
            ->assertSee('Buscar recurso');

        // La vista de solo lectura hidrata con la operación `view`.
        $this->get(RoleResource::getUrl('view', ['record' => $this->superAdmin]))
            ->assertSuccessful();

        $this->get(RoleResource::getUrl('index'))->assertSuccessful();
    }

    public function test_hidratar_el_formulario_no_hace_una_query_por_permiso(): void
    {
        $total = $this->superAdmin->permissions()->count();
        $this->assertGreaterThan(400, $total, 'El rol de prueba debe tener todos los permisos.');

        DB::flushQueryLog();
        DB::enableQueryLog();

        Livewire::test(EditRole::class, ['record' => $this->superAdmin->getKey()])
            ->assertFormFieldExists('name');

        $queries = count(DB::getRawQueryLog());
        DB::disableQueryLog();

        $this->assertLessThan(50, $queries, "Montar el formulario hizo {$queries} queries para {$total} permisos.");
    }

    public function test_guardar_no_escala_las_queries_con_el_numero_de_permisos(): void
    {
        $total = $this->superAdmin->permissions()->count();

        $componente = Livewire::test(EditRole::class, ['record' => $this->superAdmin->getKey()]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $componente->call('save')->assertHasNoFormErrors();

        $queries = count(DB::getRawQueryLog());
        DB::disableQueryLog();

        $this->assertLessThan(100, $queries, "Guardar hizo {$queries} queries para {$total} permisos.");
        $this->assertSame($total, $this->superAdmin->fresh()->permissions()->count());
    }

    public function test_guardar_con_un_filtro_activo_conserva_los_permisos_ocultos(): void
    {
        $antes = $this->superAdmin->permissions()->pluck('name')->sort()->values();

        Livewire::test(EditRole::class, ['record' => $this->superAdmin->getKey()])
            ->fillForm([PermissionMatrix::FILTER_SEARCH => 'celular'])
            ->call('save')
            ->assertHasNoFormErrors();

        $despues = $this->superAdmin->fresh()->permissions()->pluck('name')->sort()->values();

        $this->assertSame($antes->all(), $despues->all());
    }

    public function test_el_filtro_de_busqueda_oculta_los_recursos_que_no_coinciden(): void
    {
        $celular = collect(PermissionMatrix::entities())
            ->firstWhere(fn (array $entity): bool => str_contains($entity['haystack'], 'celular'));

        $otro = collect(PermissionMatrix::entities())
            ->firstWhere(fn (array $entity): bool => ! str_contains($entity['haystack'], 'celular'));

        $this->assertNotNull($celular);
        $this->assertNotNull($otro);

        $componente = Livewire::test(EditRole::class, ['record' => $this->superAdmin->getKey()])
            ->fillForm([PermissionMatrix::FILTER_SEARCH => 'celular']);

        $visibles = $this->camposVisibles($componente);

        $this->assertContains($celular['fqcn'], $visibles);
        $this->assertNotContains($otro['fqcn'], $visibles);
    }

    public function test_el_filtro_de_estado_separa_los_recursos_con_y_sin_permisos(): void
    {
        $entidades = collect(PermissionMatrix::entities());
        $conPermisos = $entidades->first();
        $sinPermisos = $entidades->last();

        $rol = Role::create(['name' => 'parcial', 'guard_name' => 'web']);
        $rol->syncPermissions(array_keys($conPermisos['options']));

        $componente = Livewire::test(EditRole::class, ['record' => $rol->getKey()])
            ->fillForm([PermissionMatrix::FILTER_ESTADO => PermissionMatrix::ESTADO_CON_PERMISOS]);

        $visibles = $this->camposVisibles($componente);
        $this->assertContains($conPermisos['fqcn'], $visibles);
        $this->assertNotContains($sinPermisos['fqcn'], $visibles);

        $visibles = $this->camposVisibles(
            $componente->fillForm([PermissionMatrix::FILTER_ESTADO => PermissionMatrix::ESTADO_SIN_PERMISOS])
        );
        $this->assertNotContains($conPermisos['fqcn'], $visibles);
        $this->assertContains($sinPermisos['fqcn'], $visibles);
    }

    public function test_marcar_y_limpiar_todo_actualizan_el_estado_del_formulario(): void
    {
        $rol = Role::create(['name' => 'vacio', 'guard_name' => 'web']);
        $campo = collect(PermissionMatrix::entities())->first()['fqcn'];
        $esperado = PermissionMatrix::fieldOptions()[$campo];

        Livewire::test(EditRole::class, ['record' => $rol->getKey()])
            ->callAction(TestAction::make('marcarTodo')->schemaComponent(RoleResource::SELECT_ALL_KEY))
            ->assertFormSet([$campo => $esperado])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(
            Permission::query()->count(),
            $rol->fresh()->permissions()->count(),
        );

        Livewire::test(EditRole::class, ['record' => $rol->getKey()])
            ->callAction(TestAction::make('limpiarTodo')->schemaComponent(RoleResource::SELECT_ALL_KEY))
            ->assertFormSet([$campo => []])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(0, $rol->fresh()->permissions()->count());
    }

    public function test_crear_un_rol_persiste_solo_los_permisos_marcados(): void
    {
        $entidad = collect(PermissionMatrix::entities())->first();
        $permisos = array_slice(array_keys($entidad['options']), 0, 3);

        Livewire::test(CreateRole::class)
            ->fillForm([
                'name' => 'soporte',
                'guard_name' => 'web',
                $entidad['fqcn'] => $permisos,
                PermissionMatrix::FILTER_SEARCH => 'texto que no debe guardarse',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $rol = Role::query()->where('name', 'soporte')->sole();

        sort($permisos);

        $this->assertSame($permisos, $rol->permissions->pluck('name')->sort()->values()->all());
    }

    /**
     * Nombres de los CheckboxList que el formulario está renderizando ahora
     * mismo. Los ocultos por los filtros no aparecen.
     *
     * @return array<int, string>
     */
    private function camposVisibles(mixed $componente): array
    {
        return collect($componente->instance()->getSchema('form')->getFlatComponents(withHidden: false))
            ->filter(fn (mixed $component): bool => $component instanceof CheckboxList)
            ->map(fn (CheckboxList $component): string => $component->getName())
            ->values()
            ->all();
    }

    public function test_el_recurso_publicado_reemplaza_al_del_paquete(): void
    {
        $registrados = collect(Filament::getPanel('admin')->getResources())
            ->filter(fn (string $resource): bool => str_contains($resource, 'RoleResource'))
            ->values();

        $this->assertSame([RoleResource::class], $registrados->all());
        $this->assertSame('shield/roles', RoleResource::getSlug());
    }
}
