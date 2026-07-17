<?php

namespace Tests\Feature;

use App\Enums\EstadoEquipo;
use App\Filament\Resources\AsignacionEquipos\AsignacionEquipoWorkflow;
use App\Models\AsignacionEquipo;
use App\Models\Equipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AsignacionEquipoWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_detects_active_equipo_assignment(): void
    {
        $equipo = Equipo::factory()->create(['estado' => EstadoEquipo::Asignado]);
        $usuario = User::factory()->create();

        AsignacionEquipo::factory()->create([
            'service_tag' => $equipo->service_tag,
            'usuario_id' => $usuario->id,
            'fecha_fin' => null,
        ]);

        $this->assertNotNull(AsignacionEquipoWorkflow::asignacionActivaDelEquipo($equipo->service_tag));
    }

    public function test_rejects_blocked_equipo(): void
    {
        $equipo = Equipo::factory()->create(['estado' => EstadoEquipo::EnMantenimiento]);

        $this->assertTrue(AsignacionEquipoWorkflow::equipoEstaBloqueado($equipo));
    }

    public function test_closes_previous_assignment_and_appends_notes(): void
    {
        $equipo = Equipo::factory()->create(['estado' => EstadoEquipo::Asignado]);
        $usuarioA = User::factory()->create(['name' => 'Usuario Origen']);
        $usuarioB = User::factory()->create(['name' => 'Usuario Destino']);

        $previa = AsignacionEquipo::factory()->create([
            'service_tag' => $equipo->service_tag,
            'usuario_id' => $usuarioA->id,
            'fecha_fin' => null,
            'observaciones' => '<p>Nota previa</p>',
        ]);

        $nota = AsignacionEquipoWorkflow::cerrarAsignacionesPreviasYAnotar(
            $equipo->service_tag,
            $usuarioB->id,
        );

        $previa->refresh();

        $this->assertNotNull($previa->fecha_fin);
        $this->assertStringContainsString('Nota previa', (string) $previa->observaciones);
        $this->assertStringContainsString('Usuario Origen', $nota);
        $this->assertStringContainsString('Usuario Destino', $nota);
    }

    public function test_does_not_close_assignment_for_same_usuario(): void
    {
        $equipo = Equipo::factory()->create(['estado' => EstadoEquipo::Asignado]);
        $usuario = User::factory()->create();

        $previa = AsignacionEquipo::factory()->create([
            'service_tag' => $equipo->service_tag,
            'usuario_id' => $usuario->id,
            'fecha_fin' => null,
        ]);

        $nota = AsignacionEquipoWorkflow::cerrarAsignacionesPreviasYAnotar(
            $equipo->service_tag,
            $usuario->id,
        );

        $this->assertSame('', $nota);
        $this->assertNull($previa->fresh()->fecha_fin);
    }

    public function test_updates_estado_to_asignado(): void
    {
        $equipo = Equipo::factory()->create(['estado' => EstadoEquipo::SinAsignar]);

        AsignacionEquipoWorkflow::actualizarEstadoEquipo($equipo->service_tag);

        $this->assertSame(EstadoEquipo::Asignado, $equipo->fresh()->estado);
    }

    public function test_updates_estado_to_sin_asignar_when_closed_and_no_other_active(): void
    {
        $equipo = Equipo::factory()->create(['estado' => EstadoEquipo::Asignado]);

        AsignacionEquipoWorkflow::actualizarEstadoEquipo($equipo->service_tag, cerrada: true);

        $this->assertSame(EstadoEquipo::SinAsignar, $equipo->fresh()->estado);
    }

    public function test_does_not_change_estado_when_blocked(): void
    {
        $equipo = Equipo::factory()->create(['estado' => EstadoEquipo::Resguardo]);

        AsignacionEquipoWorkflow::actualizarEstadoEquipo($equipo->service_tag);

        $this->assertSame(EstadoEquipo::Resguardo, $equipo->fresh()->estado);
    }
}
