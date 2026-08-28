<?php

namespace Tests\Feature\Filament;

use App\Enums\EstadoEquipo;
use App\Filament\Resources\Equipos\EquipoResource;
use App\Filament\Resources\Equipos\Pages\EditEquipo;
use App\Filament\Resources\Equipos\Pages\ListEquipos;
use App\Models\Area;
use App\Models\AsignacionEquipo;
use App\Models\Equipo;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class CambiarEstadoEquipoActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        EquipoResource::skipAuthorization();
        config(['app.env' => 'local']);
        Gate::before(fn () => true);

        $this->actingAs(User::factory()->create());
    }

    /**
     * @return array{0: Equipo, 1: User, 2: AsignacionEquipo}
     */
    private function equipoAsignado(): array
    {
        $area = Area::factory()->create(['area' => 'Tecnología de la Información']);
        $usuario = User::factory()->create(['name' => 'Juan Pérez', 'id_area' => $area->id_area]);
        $equipo = Equipo::factory()->create(['estado' => EstadoEquipo::Asignado]);

        $asignacion = AsignacionEquipo::factory()->create([
            'service_tag' => $equipo->service_tag,
            'usuario_id' => $usuario->id,
            'fecha_inicio' => now()->subMonth()->toDateString(),
            'fecha_fin' => null,
        ]);

        return [$equipo, $usuario, $asignacion];
    }

    public function test_la_accion_cambia_el_estado_y_cierra_la_asignacion(): void
    {
        [$equipo, , $asignacion] = $this->equipoAsignado();

        Livewire::test(ListEquipos::class)
            ->callAction(
                TestAction::make('cambiarEstado')->table($equipo),
                ['estado' => EstadoEquipo::BajaDefinitiva->value],
            )
            ->assertNotified();

        $this->assertDatabaseHas('equipos', [
            'service_tag' => $equipo->service_tag,
            'estado' => EstadoEquipo::BajaDefinitiva->value,
        ]);

        $this->assertSame(now()->toDateString(), $asignacion->fresh()->fecha_fin->toDateString());
    }

    public function test_la_accion_funciona_sin_asignacion_activa(): void
    {
        $equipo = Equipo::factory()->create(['estado' => EstadoEquipo::SinAsignar]);

        Livewire::test(ListEquipos::class)
            ->callAction(
                TestAction::make('cambiarEstado')->table($equipo),
                ['estado' => EstadoEquipo::Resguardo->value],
            )
            ->assertNotified();

        $this->assertDatabaseHas('equipos', [
            'service_tag' => $equipo->service_tag,
            'estado' => EstadoEquipo::Resguardo->value,
        ]);
    }

    public function test_la_accion_no_ofrece_el_estado_asignado(): void
    {
        $equipo = Equipo::factory()->create(['estado' => EstadoEquipo::SinAsignar]);

        Livewire::test(ListEquipos::class)
            ->callAction(
                TestAction::make('cambiarEstado')->table($equipo),
                ['estado' => EstadoEquipo::Asignado->value],
            )
            ->assertHasActionErrors(['estado']);

        $this->assertSame(EstadoEquipo::SinAsignar, $equipo->fresh()->estado);
    }

    public function test_el_formulario_de_edicion_revierte_el_estado_hasta_que_se_confirma(): void
    {
        [$equipo, , $asignacion] = $this->equipoAsignado();

        Livewire::test(EditEquipo::class, ['record' => $equipo->getKey()])
            ->fillForm(['estado' => EstadoEquipo::BajaDefinitiva->value])
            ->assertActionMounted('confirmarCierreAsignacionEquipo')
            ->call('save');

        $this->assertNull($asignacion->fresh()->fecha_fin);
        $this->assertSame(EstadoEquipo::Asignado, $equipo->fresh()->estado);
    }

    public function test_el_formulario_de_edicion_aplica_el_cambio_tras_confirmar(): void
    {
        [$equipo, , $asignacion] = $this->equipoAsignado();

        Livewire::test(EditEquipo::class, ['record' => $equipo->getKey()])
            ->fillForm(['estado' => EstadoEquipo::Robo->value])
            ->callMountedAction()
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(EstadoEquipo::Robo, $equipo->fresh()->estado);
        $this->assertSame(now()->toDateString(), $asignacion->fresh()->fecha_fin->toDateString());
    }
}
