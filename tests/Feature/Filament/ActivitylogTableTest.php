<?php

namespace Tests\Feature\Filament;

use App\Enums\DominioActividad;
use App\Filament\Resources\Activitylogs\ActivitylogResource;
use App\Filament\Resources\Activitylogs\Pages\ListActivitylogs;
use App\Filament\Resources\Activitylogs\RelationManagers\ActivitylogRelationManager;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Models\Area;
use App\Models\Software;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ActivitylogTableTest extends TestCase
{
    use RefreshDatabase;

    protected User $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(fn () => true);

        $this->usuario = User::factory()->create(['name' => 'Deyvid Vladimir']);
        $this->usuario->assignRole(Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']));

        $this->actingAs($this->usuario);
    }

    public function test_la_tabla_permite_buscar_por_el_nombre_del_usuario_que_causa_la_actividad(): void
    {
        $otro = User::factory()->create(['name' => 'Ana Perez']);

        $propia = activity()->causedBy($this->usuario)->log('creado');
        $ajena = activity()->causedBy($otro)->log('creado');

        Livewire::test(ListActivitylogs::class)
            ->assertCanSeeTableRecords([$propia, $ajena])
            ->searchTable('Deyvid')
            ->assertCanSeeTableRecords([$propia])
            ->assertCanNotSeeTableRecords([$ajena]);
    }

    public function test_la_busqueda_tambien_encuentra_por_columnas_propias_del_registro(): void
    {
        $registro = activity()->causedBy($this->usuario)->performedOn($this->usuario)->log('creado');

        Livewire::test(ListActivitylogs::class)
            ->searchTable('User')
            ->assertCanSeeTableRecords([$registro]);
    }

    public function test_el_filtro_de_log_name_se_etiqueta_como_tipo(): void
    {
        $this->assertSame('Tipo', ActivitylogResource::getLogNameFilterComponent()->getLabel());
        $this->assertSame('Tipo', ActivitylogResource::getLogNameColumnComponent()->getLabel());
    }

    public function test_la_pagina_de_listado_carga(): void
    {
        activity()->causedBy($this->usuario)->log('creado');

        $this->get(ActivitylogResource::getUrl('index', panel: 'admin'))
            ->assertSuccessful();
    }

    public function test_el_relation_manager_permite_buscar_por_usuario(): void
    {
        $registro = activity()->causedBy($this->usuario)->performedOn($this->usuario)->log('creado');

        Livewire::test(ActivitylogRelationManager::class, [
            'ownerRecord' => $this->usuario,
            'pageClass' => ViewUser::class,
        ])
            ->searchTable('Deyvid')
            ->assertCanSeeTableRecords([$registro]);
    }

    public function test_cada_modelo_registra_su_dominio_en_log_name(): void
    {
        $area = Area::factory()->create();
        $software = Software::factory()->create();

        $this->assertSame(
            DominioActividad::Usuarios->value,
            Activity::query()->where('subject_type', Area::class)->where('subject_id', (string) $area->getKey())->value('log_name'),
        );

        $this->assertSame(
            DominioActividad::Software->value,
            Activity::query()->where('subject_type', Software::class)->where('subject_id', (string) $software->getKey())->value('log_name'),
        );
    }

    public function test_el_filtro_tipo_ofrece_los_dominios_presentes_con_etiqueta_legible(): void
    {
        Area::factory()->create();
        Software::factory()->create();

        $opciones = ActivitylogResource::getLogNameFilterComponent()->getOptions();

        $this->assertSame([
            DominioActividad::Software->value => 'Software y licencias',
            DominioActividad::Usuarios->value => 'Usuarios',
        ], $opciones);
    }

    public function test_el_filtro_tipo_acota_los_registros_al_dominio_elegido(): void
    {
        $area = Area::factory()->create();
        $software = Software::factory()->create();

        $registroArea = Activity::query()->where('subject_type', Area::class)->where('subject_id', (string) $area->getKey())->sole();
        $registroSoftware = Activity::query()->where('subject_type', Software::class)->where('subject_id', (string) $software->getKey())->sole();

        Livewire::test(ListActivitylogs::class)
            ->filterTable('log_name', DominioActividad::Usuarios->value)
            ->assertCanSeeTableRecords([$registroArea])
            ->assertCanNotSeeTableRecords([$registroSoftware]);
    }

    public function test_la_columna_tipo_muestra_la_etiqueta_del_dominio(): void
    {
        Software::factory()->create();

        // 'Software y licencias' solo puede provenir de la columna: ningún
        // elemento de navegación del panel usa esa etiqueta.
        $this->get(ActivitylogResource::getUrl('index', panel: 'admin'))
            ->assertSuccessful()
            ->assertSee('Software y licencias');
    }

    public function test_el_recurso_usado_por_el_plugin_es_el_personalizado(): void
    {
        $this->assertInstanceOf(Activity::class, Activity::first() ?? new Activity);
        $this->assertSame(ActivitylogResource::class, config('filament-activitylog.resources.resource'));
    }
}
