<?php

namespace Tests\Feature\Movimientos;

use App\Enums\EstadoCargador;
use App\Enums\EstadoEquipo;
use App\Models\AccesoDaza;
use App\Models\AccesoInternet;
use App\Models\AccesoSharepoint;
use App\Models\AccesoSistema;
use App\Models\AccesoVirtual;
use App\Models\AccUdCompartida;
use App\Models\AsigCargadorCel;
use App\Models\AsigCelular;
use App\Models\AsignacionCargador;
use App\Models\AsignacionEquipo;
use App\Models\AzureAd;
use App\Models\Cargador;
use App\Models\CargadorCel;
use App\Models\Celular;
use App\Models\Equipo;
use App\Models\LicenciaUsuario;
use App\Models\Movimiento;
use App\Models\SitioSharepoint;
use App\Models\Software;
use App\Models\SoftwareExc;
use App\Models\SoftwareUsuario;
use App\Models\User;
use App\Services\Movimientos\ProcesarBajaColaborador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BajaColaboradorCascadaTest extends TestCase
{
    use RefreshDatabase;

    private function servicio(): ProcesarBajaColaborador
    {
        return app(ProcesarBajaColaborador::class);
    }

    public function test_la_baja_desactiva_todos_los_accesos_con_estado(): void
    {
        $user = User::factory()->create();

        $registros = [
            SoftwareUsuario::factory()->create(['usuario_id' => $user->id, 'estado' => true]),
            LicenciaUsuario::factory()->create(['usuario_id' => $user->id, 'estado' => true]),
            AccesoSharepoint::factory()->create(['usuario_id' => $user->id, 'estado' => true]),
            AccesoSistema::factory()->create(['usuario_id' => $user->id, 'estado' => true]),
            AccesoDaza::factory()->create(['usuario_id' => $user->id, 'estado' => true]),
            AccesoInternet::factory()->create(['usuario_id' => $user->id, 'estado' => true]),
            AzureAd::factory()->create(['usuario_id' => $user->id, 'estado' => true]),
            AccUdCompartida::factory()->create(['usuario_id' => $user->id, 'estado' => true]),
        ];

        $resumen = $this->servicio()->ejecutar($user->id, Carbon::parse('2026-08-12'));

        foreach ($registros as $registro) {
            $this->assertFalse($registro->fresh()->estado, $registro::class.' debería quedar inactivo');
        }

        $this->assertSame(8, $resumen->accesosDesactivados);
    }

    public function test_la_baja_desactiva_los_accesos_virtuales_a_traves_del_acceso_de_internet(): void
    {
        $user = User::factory()->create();
        $internet = AccesoInternet::factory()->create(['usuario_id' => $user->id, 'estado' => true]);
        $virtual = AccesoVirtual::factory()->create(['internet_id' => $internet->id, 'estado' => true]);

        $this->servicio()->ejecutar($user->id, Carbon::parse('2026-08-12'));

        $this->assertFalse($virtual->fresh()->estado);
        $this->assertFalse($internet->fresh()->estado);
    }

    public function test_la_baja_cierra_la_asignacion_de_equipo_y_deja_el_equipo_sin_asignar(): void
    {
        $user = User::factory()->create();
        $equipo = Equipo::factory()->create(['estado' => EstadoEquipo::Asignado]);
        $asignacion = AsignacionEquipo::factory()->create([
            'usuario_id' => $user->id,
            'service_tag' => $equipo->service_tag,
            'fecha_inicio' => '2026-01-10',
            'fecha_fin' => null,
        ]);

        $resumen = $this->servicio()->ejecutar($user->id, Carbon::parse('2026-08-12'));

        $this->assertSame('2026-08-12', $asignacion->fresh()->fecha_fin?->toDateString());
        $this->assertSame(EstadoEquipo::SinAsignar, $equipo->fresh()->estado);
        $this->assertSame(1, $resumen->asignacionesEquipoCerradas);
    }

    public function test_la_baja_cierra_la_asignacion_de_cargador_del_equipo_y_libera_el_cargador(): void
    {
        $user = User::factory()->create();
        $equipo = Equipo::factory()->create(['estado' => EstadoEquipo::Asignado]);
        $cargador = Cargador::factory()->create(['estado' => EstadoCargador::Asignado]);

        AsignacionEquipo::factory()->create([
            'usuario_id' => $user->id,
            'service_tag' => $equipo->service_tag,
            'fecha_inicio' => '2026-01-10',
            'fecha_fin' => null,
        ]);
        $asignacionCargador = AsignacionCargador::factory()->create([
            'serial_cargador' => $cargador->serial,
            'service_tag' => $equipo->service_tag,
            'fecha_inicio' => '2026-01-10',
            'fecha_fin' => null,
        ]);

        $resumen = $this->servicio()->ejecutar($user->id, Carbon::parse('2026-08-12'));

        $this->assertSame('2026-08-12', $asignacionCargador->fresh()->fecha_fin?->toDateString());
        $this->assertSame(EstadoCargador::Disponible, $cargador->fresh()->estado);
        $this->assertSame(1, $resumen->asignacionesCargadorCerradas);
    }

    public function test_la_baja_no_cierra_la_asignacion_de_cargador_de_un_equipo_ajeno(): void
    {
        $user = User::factory()->create();
        $equipoAjeno = Equipo::factory()->create();
        $cargador = Cargador::factory()->create(['estado' => EstadoCargador::Asignado]);
        $asignacionAjena = AsignacionCargador::factory()->create([
            'serial_cargador' => $cargador->serial,
            'service_tag' => $equipoAjeno->service_tag,
            'fecha_inicio' => '2026-01-10',
            'fecha_fin' => null,
        ]);

        $this->servicio()->ejecutar($user->id, Carbon::parse('2026-08-12'));

        $this->assertNull($asignacionAjena->fresh()->fecha_fin);
        $this->assertSame(EstadoCargador::Asignado, $cargador->fresh()->estado);
    }

    public function test_la_baja_cierra_la_asignacion_de_celular(): void
    {
        $user = User::factory()->create();
        $asignacion = AsigCelular::factory()->create([
            'usuario_id' => $user->id,
            'fecha_ini' => '2026-01-10',
            'fecha_fin' => null,
        ]);

        $resumen = $this->servicio()->ejecutar($user->id, Carbon::parse('2026-08-12'));

        $this->assertSame('2026-08-12', $asignacion->fresh()->fecha_fin?->toDateString());
        $this->assertSame(1, $resumen->asignacionesCelularCerradas);
    }

    public function test_la_baja_cierra_las_excepciones_de_software(): void
    {
        $user = User::factory()->create();
        $excepcion = SoftwareExc::factory()->create([
            'usuario_id' => $user->id,
            'fecha_inicio' => '2026-01-10',
            'fecha_fin' => null,
        ]);

        $resumen = $this->servicio()->ejecutar($user->id, Carbon::parse('2026-08-12'));

        $this->assertSame('2026-08-12', $excepcion->fresh()->fecha_fin?->toDateString());
        $this->assertSame(1, $resumen->excepcionesSoftwareCerradas);
    }

    public function test_la_baja_no_toca_el_cargador_del_celular(): void
    {
        $user = User::factory()->create();
        $celular = Celular::factory()->create();
        $cargadorCel = CargadorCel::factory()->create();
        AsigCelular::factory()->create([
            'usuario_id' => $user->id,
            'celular_id' => $celular->id,
            'fecha_ini' => '2026-01-10',
            'fecha_fin' => null,
        ]);
        $asignacionCargadorCel = AsigCargadorCel::factory()->create([
            'celulares_id' => $celular->id,
            'cargador_serial' => $cargadorCel->serial,
        ]);

        $this->servicio()->ejecutar($user->id, Carbon::parse('2026-08-12'));

        $this->assertDatabaseHas('asignaciones_cargadores_celulares', [
            'id' => $asignacionCargadorCel->id,
            'deleted_at' => null,
        ]);
    }

    public function test_la_baja_usa_la_fecha_del_movimiento_y_no_la_de_hoy(): void
    {
        $user = User::factory()->create();
        $asignacion = AsignacionEquipo::factory()->create([
            'usuario_id' => $user->id,
            'fecha_inicio' => '2026-01-10',
            'fecha_fin' => null,
        ]);

        $this->servicio()->ejecutar($user->id, Carbon::parse('2026-03-05'));

        $this->assertSame('2026-03-05', $asignacion->fresh()->fecha_fin?->toDateString());
    }

    public function test_la_fecha_fin_nunca_es_anterior_a_la_fecha_de_inicio(): void
    {
        $user = User::factory()->create();
        $asignacion = AsignacionEquipo::factory()->create([
            'usuario_id' => $user->id,
            'fecha_inicio' => '2026-09-01',
            'fecha_fin' => null,
        ]);

        $this->servicio()->ejecutar($user->id, Carbon::parse('2026-08-12'));

        $this->assertSame('2026-09-01', $asignacion->fresh()->fecha_fin?->toDateString());
    }

    public function test_no_toca_registros_ya_cerrados_ni_inactivos(): void
    {
        $user = User::factory()->create();
        $inactivo = SoftwareUsuario::factory()->create(['usuario_id' => $user->id, 'estado' => false]);
        $cerrada = AsignacionEquipo::factory()->create([
            'usuario_id' => $user->id,
            'fecha_inicio' => '2026-01-10',
            'fecha_fin' => '2026-02-20',
        ]);

        $actualizadoAntes = $inactivo->updated_at;

        $resumen = $this->servicio()->ejecutar($user->id, Carbon::parse('2026-08-12'));

        $this->assertSame('2026-02-20', $cerrada->fresh()->fecha_fin?->toDateString());
        $this->assertEquals($actualizadoAntes, $inactivo->fresh()->updated_at);
        $this->assertFalse($resumen->huboCambios());
    }

    public function test_es_idempotente(): void
    {
        $user = User::factory()->create();
        SoftwareUsuario::factory()->create(['usuario_id' => $user->id, 'estado' => true]);
        $asignacion = AsignacionEquipo::factory()->create([
            'usuario_id' => $user->id,
            'fecha_inicio' => '2026-01-10',
            'fecha_fin' => null,
        ]);

        $primero = $this->servicio()->ejecutar($user->id, Carbon::parse('2026-08-12'));
        $segundo = $this->servicio()->ejecutar($user->id, Carbon::parse('2026-11-30'));

        $this->assertTrue($primero->huboCambios());
        $this->assertSame(0, $segundo->total());
        $this->assertSame('2026-08-12', $asignacion->fresh()->fecha_fin?->toDateString());
    }

    public function test_no_afecta_a_otros_usuarios(): void
    {
        $user = User::factory()->create();
        $otro = User::factory()->create();
        $accesoAjeno = SoftwareUsuario::factory()->create(['usuario_id' => $otro->id, 'estado' => true]);

        $this->servicio()->ejecutar($user->id, Carbon::parse('2026-08-12'));

        $this->assertTrue($accesoAjeno->fresh()->estado);
    }

    public function test_un_alta_no_dispara_la_cascada_ni_reactiva_nada(): void
    {
        $user = User::factory()->create();
        $acceso = SoftwareUsuario::factory()->create(['usuario_id' => $user->id, 'estado' => true]);

        Movimiento::factory()->alta()->create([
            'usuario_id' => $user->id,
            'fecha' => '2026-01-10',
        ]);

        $this->assertTrue($acceso->fresh()->estado);

        Movimiento::factory()->baja()->create([
            'usuario_id' => $user->id,
            'fecha' => '2026-08-12',
        ]);

        $this->assertFalse($acceso->fresh()->estado);

        Movimiento::factory()->alta()->create([
            'usuario_id' => $user->id,
            'fecha' => '2026-09-01',
        ]);

        $this->assertFalse($acceso->fresh()->estado);
    }

    public function test_desactiva_los_accesos_con_un_solo_update_por_tabla(): void
    {
        $user = User::factory()->create();

        Software::factory()->count(5)->create()->each(
            fn (Software $software) => SoftwareUsuario::factory()->create([
                'usuario_id' => $user->id,
                'id_software' => $software->id_software,
                'estado' => true,
            ]),
        );

        SitioSharepoint::factory()->count(5)->create()->each(
            fn (SitioSharepoint $sitio) => AccesoSharepoint::factory()->create([
                'usuario_id' => $user->id,
                'sitio_id' => $sitio->id,
                'estado' => true,
            ]),
        );

        $updates = [];

        DB::listen(function ($query) use (&$updates): void {
            if (str_starts_with(strtolower(trim($query->sql)), 'update')) {
                $updates[] = $query->sql;
            }
        });

        $this->servicio()->ejecutar($user->id, Carbon::parse('2026-08-12'));

        $softwareUpdates = array_filter($updates, fn (string $sql): bool => str_contains($sql, 'software_usuarios'));
        $sharepointUpdates = array_filter($updates, fn (string $sql): bool => str_contains($sql, 'accesos_sharepoint'));

        $this->assertCount(1, $softwareUpdates);
        $this->assertCount(1, $sharepointUpdates);
    }
}
