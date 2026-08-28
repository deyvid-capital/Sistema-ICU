<?php

namespace Tests\Feature;

use App\Enums\EstadoEquipo;
use App\Models\Area;
use App\Models\AsignacionEquipo;
use App\Models\Equipo;
use App\Models\User;
use App\Services\CambiarEstadoEquipo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class CambiarEstadoEquipoTest extends TestCase
{
    use RefreshDatabase;

    private function servicio(): CambiarEstadoEquipo
    {
        return app(CambiarEstadoEquipo::class);
    }

    private function equipoAsignado(?string $fechaInicio = null): array
    {
        $equipo = Equipo::factory()->create(['estado' => EstadoEquipo::Asignado]);
        $usuario = User::factory()->create();

        $asignacion = AsignacionEquipo::factory()->create([
            'service_tag' => $equipo->service_tag,
            'usuario_id' => $usuario->id,
            'fecha_inicio' => $fechaInicio ?? now()->subMonth()->toDateString(),
            'fecha_fin' => null,
        ]);

        return [$equipo, $usuario, $asignacion];
    }

    public function test_da_de_baja_definitiva_y_cierra_la_asignacion_con_la_fecha_de_hoy(): void
    {
        [$equipo, , $asignacion] = $this->equipoAsignado();

        $cerrada = $this->servicio()->ejecutar($equipo, EstadoEquipo::BajaDefinitiva);

        $this->assertNotNull($cerrada);
        $this->assertSame($asignacion->id, $cerrada->id);
        $this->assertSame(now()->toDateString(), $asignacion->fresh()->fecha_fin->toDateString());
        $this->assertSame(EstadoEquipo::BajaDefinitiva, $equipo->fresh()->estado);
    }

    public function test_el_estado_elegido_no_se_revierte_a_sin_asignar(): void
    {
        foreach ([EstadoEquipo::Robo, EstadoEquipo::Perdida, EstadoEquipo::Desarmado, EstadoEquipo::Depreciado] as $estado) {
            [$equipo] = $this->equipoAsignado();

            $this->servicio()->ejecutar($equipo, $estado);

            $this->assertSame($estado, $equipo->fresh()->estado, "Estado {$estado->value}");
        }
    }

    public function test_la_fecha_de_cierre_nunca_es_anterior_al_inicio_de_la_asignacion(): void
    {
        $inicioFuturo = now()->addDays(10)->toDateString();
        [$equipo, , $asignacion] = $this->equipoAsignado($inicioFuturo);

        $this->servicio()->ejecutar($equipo, EstadoEquipo::BajaTemporal);

        $this->assertSame($inicioFuturo, $asignacion->fresh()->fecha_fin->toDateString());
    }

    public function test_anota_el_cierre_en_las_observaciones_de_la_asignacion(): void
    {
        [$equipo, , $asignacion] = $this->equipoAsignado();

        $this->servicio()->ejecutar($equipo, EstadoEquipo::Robo);

        $this->assertStringContainsString('Cierre por cambio de estado', (string) $asignacion->fresh()->observaciones);
        $this->assertStringContainsString('Robo', (string) $asignacion->fresh()->observaciones);
    }

    public function test_pasar_a_sin_asignar_tambien_cierra_la_asignacion(): void
    {
        [$equipo, , $asignacion] = $this->equipoAsignado();

        $this->servicio()->ejecutar($equipo, EstadoEquipo::SinAsignar);

        $this->assertNotNull($asignacion->fresh()->fecha_fin);
        $this->assertSame(EstadoEquipo::SinAsignar, $equipo->fresh()->estado);
    }

    public function test_no_toca_nada_cuando_el_equipo_no_tiene_asignacion_activa(): void
    {
        $equipo = Equipo::factory()->create(['estado' => EstadoEquipo::SinAsignar]);

        $cerrada = $this->servicio()->ejecutar($equipo, EstadoEquipo::Resguardo);

        $this->assertNull($cerrada);
        $this->assertSame(EstadoEquipo::Resguardo, $equipo->fresh()->estado);
    }

    public function test_rechaza_el_estado_asignado_porque_es_derivado(): void
    {
        $equipo = Equipo::factory()->create(['estado' => EstadoEquipo::SinAsignar]);

        $this->expectException(RuntimeException::class);

        $this->servicio()->ejecutar($equipo, EstadoEquipo::Asignado);
    }

    public function test_rechaza_equipos_en_la_papelera(): void
    {
        $equipo = Equipo::factory()->create(['estado' => EstadoEquipo::SinAsignar]);
        $equipo->delete();

        $this->expectException(RuntimeException::class);

        $this->servicio()->ejecutar($equipo, EstadoEquipo::BajaDefinitiva);
    }

    public function test_requiere_confirmacion_solo_cuando_hay_asignacion_abierta(): void
    {
        [$equipoAsignado] = $this->equipoAsignado();
        $equipoLibre = Equipo::factory()->create(['estado' => EstadoEquipo::SinAsignar]);

        $this->assertTrue($this->servicio()->requiereConfirmacion($equipoAsignado, EstadoEquipo::BajaDefinitiva));
        $this->assertFalse($this->servicio()->requiereConfirmacion($equipoLibre, EstadoEquipo::BajaDefinitiva));
        $this->assertFalse($this->servicio()->requiereConfirmacion($equipoAsignado, EstadoEquipo::Asignado));
    }

    public function test_la_descripcion_incluye_el_nombre_del_usuario_y_su_area(): void
    {
        $area = Area::factory()->create(['area' => 'Tecnología de la Información']);
        $usuario = User::factory()->create(['name' => 'Juan Pérez', 'id_area' => $area->id_area]);
        $equipo = Equipo::factory()->create(['estado' => EstadoEquipo::Asignado]);

        AsignacionEquipo::factory()->create([
            'service_tag' => $equipo->service_tag,
            'usuario_id' => $usuario->id,
            'fecha_fin' => null,
        ]);

        $activa = $this->servicio()->asignacionActiva($equipo);

        $this->assertSame(
            'Juan Pérez — Tecnología de la Información',
            CambiarEstadoEquipo::descripcionAsignacion($activa),
        );
    }
}
