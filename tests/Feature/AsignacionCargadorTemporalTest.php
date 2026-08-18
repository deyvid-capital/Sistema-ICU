<?php

namespace Tests\Feature;

use App\Enums\EstadoCargador;
use App\Enums\EstadoEquipo;
use App\Enums\ModoAsignacionCargador;
use App\Filament\Resources\AsignacionCargadors\AsignacionCargadorWorkflow;
use App\Models\AsignacionCargador;
use App\Models\AsignacionEquipo;
use App\Models\Cargador;
use App\Models\Equipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AsignacionCargadorTemporalTest extends TestCase
{
    use RefreshDatabase;

    public function test_detects_active_charger_assignment(): void
    {
        $cargador = Cargador::factory()->create(['estado' => EstadoCargador::Asignado]);
        $equipo = Equipo::factory()->create(['estado' => EstadoEquipo::SinAsignar]);

        AsignacionCargador::factory()->create([
            'serial_cargador' => $cargador->serial,
            'service_tag' => $equipo->service_tag,
            'fecha_fin' => null,
        ]);

        $this->assertNotNull(AsignacionCargadorWorkflow::asignacionActivaDelCargador($cargador->serial));
    }

    public function test_equipo_origen_requires_sin_asignar_state(): void
    {
        $equipo = Equipo::factory()->create(['estado' => EstadoEquipo::Asignado]);

        $problemas = AsignacionCargadorWorkflow::problemasEquipoOrigenLibre($equipo);

        $this->assertNotEmpty($problemas);
        $this->assertStringContainsString('Sin asignar', $problemas[0]['titulo']);
    }

    public function test_equipo_origen_requires_closed_user_assignment(): void
    {
        $equipo = Equipo::factory()->create(['estado' => EstadoEquipo::SinAsignar]);

        AsignacionEquipo::factory()->create([
            'service_tag' => $equipo->service_tag,
            'usuario_id' => User::factory(),
            'fecha_fin' => null,
        ]);

        $problemas = AsignacionCargadorWorkflow::problemasEquipoOrigenLibre($equipo);

        $this->assertTrue(
            collect($problemas)->contains(fn (array $p): bool => str_contains($p['titulo'], 'usuario'))
        );
    }

    public function test_closes_previous_assignment_and_appends_notes_on_both(): void
    {
        $equipoA = Equipo::factory()->create(['estado' => EstadoEquipo::SinAsignar]);
        $equipoB = Equipo::factory()->create(['estado' => EstadoEquipo::SinAsignar]);
        $cargador = Cargador::factory()->create(['estado' => EstadoCargador::Asignado]);

        $previa = AsignacionCargador::factory()->create([
            'serial_cargador' => $cargador->serial,
            'service_tag' => $equipoA->service_tag,
            'fecha_fin' => null,
            'observaciones' => '<p>Nota previa</p>',
        ]);

        $nota = AsignacionCargadorWorkflow::cerrarAsignacionesPreviasYAnotar(
            $cargador->serial,
            $equipoB->service_tag,
            ModoAsignacionCargador::Temporal,
        );

        $previa->refresh();

        $this->assertNotNull($previa->fecha_fin);
        $this->assertStringContainsString('Nota previa', (string) $previa->observaciones);
        $this->assertStringContainsString($equipoA->service_tag, $nota);
        $this->assertStringContainsString($equipoB->service_tag, $nota);
        $this->assertStringContainsString('Asignación temporal', $nota);
    }

    public function test_updates_charger_state_to_reasignacion(): void
    {
        $cargador = Cargador::factory()->create(['estado' => EstadoCargador::Asignado]);

        AsignacionCargadorWorkflow::actualizarEstadoCargador(
            $cargador->serial,
            ModoAsignacionCargador::Reasignacion,
        );

        $this->assertSame(EstadoCargador::Reasignacion, $cargador->fresh()->estado);
    }

    public function test_rejects_blocked_charger(): void
    {
        $cargador = Cargador::factory()->create(['estado' => EstadoCargador::Dañado]);

        $this->assertTrue(AsignacionCargadorWorkflow::cargadorEstaBloqueado($cargador));
    }
}
