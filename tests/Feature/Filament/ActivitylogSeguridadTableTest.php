<?php

namespace Tests\Feature\Filament;

use App\Enums\EventoSeguridad;
use App\Filament\Resources\Activitylogs\ActivitylogResource;
use App\Filament\Resources\Activitylogs\Pages\ListActivitylogs;
use App\Models\Area;
use App\Models\User;
use App\Support\Auditoria\AuditorSeguridad;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * El registro de actividad es la única tabla de auditoría: muestra tanto los
 * cambios de datos como los eventos de acceso.
 */
class ActivitylogSeguridadTableTest extends TestCase
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

    private function auditor(): AuditorSeguridad
    {
        return app(AuditorSeguridad::class);
    }

    private function ultimo(): Activity
    {
        return Activity::query()->latest('id')->firstOrFail();
    }

    public function test_la_tabla_muestra_eventos_de_acceso_junto_a_los_cambios_de_datos(): void
    {
        $this->auditor()->correoDesconocido('intruso@example.com');
        $acceso = $this->ultimo();

        Area::factory()->create();
        $negocio = Activity::query()->where('log_name', 'usuarios')->latest('id')->firstOrFail();

        Livewire::test(ListActivitylogs::class)
            ->assertCanSeeTableRecords([$acceso, $negocio]);
    }

    public function test_muestra_las_columnas_de_correo_e_ip(): void
    {
        $this->auditor()->correoDesconocido('intruso@example.com');

        Livewire::test(ListActivitylogs::class)
            ->assertCanRenderTableColumn('correo')
            ->assertSee('intruso@example.com')
            // La IP existe pero llega apagada: solo estorba en las filas de
            // negocio, que son la mayoría. Se activa desde el selector.
            ->assertCanNotRenderTableColumn('ip');
    }

    public function test_la_columna_evento_traduce_los_eventos_de_seguridad(): void
    {
        $this->auditor()->passwordIncorrecta($this->usuario, 2);

        Livewire::test(ListActivitylogs::class)
            ->assertSee(EventoSeguridad::PasswordIncorrecta->getLabel());
    }

    public function test_la_columna_evento_traduce_los_eventos_de_modelo(): void
    {
        Area::factory()->create();

        Livewire::test(ListActivitylogs::class)
            ->assertSee('Creado')
            ->assertDontSee('activitylog::action.event');
    }

    public function test_el_filtro_de_evento_no_ofrece_la_clave_cruda_de_traduccion(): void
    {
        // Registro histórico sin `event`: el filtro del paquete lo convertía en
        // una opción vacía etiquetada "activitylog::action.event.".
        activity('configuracion')->log('Registro antiguo sin evento');

        $opciones = ActivitylogResource::getEventFilterComponent()->getOptions();

        $planas = [];

        foreach ($opciones as $grupo) {
            $planas = array_merge($planas, is_array($grupo) ? $grupo : [$grupo]);
        }

        $this->assertNotEmpty($planas);
        $this->assertArrayNotHasKey('', $opciones);

        foreach ($planas as $etiqueta) {
            $this->assertStringNotContainsString('activitylog::', (string) $etiqueta);
        }
    }

    public function test_el_filtro_de_evento_ofrece_los_eventos_de_seguridad_y_de_modelo(): void
    {
        $opciones = ActivitylogResource::getEventFilterComponent()->getOptions();

        $this->assertArrayHasKey('created', $opciones['Cambios en datos']);
        $this->assertArrayHasKey('updated', $opciones['Cambios en datos']);

        foreach (EventoSeguridad::cases() as $evento) {
            $this->assertArrayHasKey($evento->value, $opciones['Seguridad y accesos']);
        }
    }

    public function test_el_filtro_de_evento_acota_los_registros(): void
    {
        $this->auditor()->ingresoExitoso($this->usuario);
        $ingreso = $this->ultimo();

        $this->auditor()->correoDesconocido('intruso@example.com');
        $desconocido = $this->ultimo();

        Livewire::test(ListActivitylogs::class)
            ->filterTable('event', [EventoSeguridad::CorreoDesconocido->value])
            ->assertCanSeeTableRecords([$desconocido])
            ->assertCanNotSeeTableRecords([$ingreso]);
    }

    public function test_el_filtro_de_resultado_agrupa_los_accesos_fallidos(): void
    {
        $this->auditor()->ingresoExitoso($this->usuario);
        $ingreso = $this->ultimo();

        $this->auditor()->passwordIncorrecta($this->usuario, 2);
        $fallo = $this->ultimo();

        Livewire::test(ListActivitylogs::class)
            ->filterTable('resultado', 'fallo')
            ->assertCanSeeTableRecords([$fallo])
            ->assertCanNotSeeTableRecords([$ingreso]);
    }

    public function test_la_busqueda_encuentra_por_correo(): void
    {
        $this->auditor()->correoDesconocido('buscado@example.com');
        $buscado = $this->ultimo();

        $this->auditor()->correoDesconocido('otro@example.com');
        $otro = $this->ultimo();

        Livewire::test(ListActivitylogs::class)
            ->searchTable('buscado@')
            ->assertCanSeeTableRecords([$buscado])
            ->assertCanNotSeeTableRecords([$otro]);
    }

    public function test_el_detalle_de_un_evento_de_acceso_carga(): void
    {
        $this->auditor()->accesoDenegado($this->usuario, 'Tu acceso está deshabilitado.');

        $this->get(ActivitylogResource::getUrl('view', ['record' => $this->ultimo()->getKey()], panel: 'admin'))
            ->assertSuccessful();
    }
}
