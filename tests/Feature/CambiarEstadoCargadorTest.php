<?php

namespace Tests\Feature;

use App\Enums\EstadoCargador;
use App\Models\AsignacionCargador;
use App\Models\Cargador;
use App\Models\Equipo;
use App\Services\CambiarEstadoCargador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class CambiarEstadoCargadorTest extends TestCase
{
    use RefreshDatabase;

    public function test_cierra_la_asignacion_al_pasar_a_disponible(): void
    {
        $cargador = Cargador::factory()->create(['estado' => EstadoCargador::Asignado]);
        $equipo = Equipo::factory()->create();
        $asignacion = AsignacionCargador::factory()->create([
            'serial_cargador' => $cargador->serial,
            'service_tag' => $equipo->service_tag,
            'fecha_inicio' => now()->subWeek()->toDateString(),
            'fecha_fin' => null,
        ]);

        $cerrada = app(CambiarEstadoCargador::class)->ejecutar($cargador, EstadoCargador::Disponible);

        $this->assertNotNull($cerrada);
        $this->assertSame(now()->toDateString(), $asignacion->fresh()->fecha_fin->toDateString());
        $this->assertSame(EstadoCargador::Disponible, $cargador->fresh()->estado);
    }

    public function test_cierra_la_asignacion_al_pasar_a_danado(): void
    {
        $cargador = Cargador::factory()->create(['estado' => EstadoCargador::Temporal]);
        $equipo = Equipo::factory()->create();
        $asignacion = AsignacionCargador::factory()->create([
            'serial_cargador' => $cargador->serial,
            'service_tag' => $equipo->service_tag,
            'fecha_fin' => null,
        ]);

        app(CambiarEstadoCargador::class)->ejecutar($cargador, EstadoCargador::Dañado);

        $this->assertNotNull($asignacion->fresh()->fecha_fin);
        $this->assertSame(EstadoCargador::Dañado, $cargador->fresh()->estado);
    }

    public function test_no_permite_elegir_asignado_a_mano(): void
    {
        $cargador = Cargador::factory()->create(['estado' => EstadoCargador::Disponible]);

        $this->expectException(RuntimeException::class);

        app(CambiarEstadoCargador::class)->ejecutar($cargador, EstadoCargador::Asignado);
    }

    public function test_pide_confirmacion_si_hay_asignacion_abierta(): void
    {
        $cargador = Cargador::factory()->create(['estado' => EstadoCargador::Asignado]);
        $equipo = Equipo::factory()->create();
        AsignacionCargador::factory()->create([
            'serial_cargador' => $cargador->serial,
            'service_tag' => $equipo->service_tag,
            'fecha_fin' => null,
        ]);

        $this->assertTrue(
            app(CambiarEstadoCargador::class)->requiereConfirmacion($cargador, EstadoCargador::Disponible),
        );
    }
}
