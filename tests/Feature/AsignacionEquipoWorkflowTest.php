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
