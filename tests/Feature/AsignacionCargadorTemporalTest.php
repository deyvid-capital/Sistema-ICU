<?php

namespace Tests\Feature;

use App\Enums\EstadoCargador;
use App\Enums\EstadoEquipo;
use App\Enums\ModoAsignacionCargador;
use App\Exceptions\EquipoOrigenConUsuarioException;
use App\Filament\Resources\AsignacionCargadors\AsignacionCargadorWorkflow;
use App\Filament\Resources\AsignacionEquipos\AsignacionEquipoResource;
use App\Models\AsignacionCargador;
use App\Models\AsignacionEquipo;
use App\Models\Cargador;
use App\Models\Equipo;
use App\Models\User;
use App\Services\MovimientoCargador;
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

    public function test_normal_assignment_sets_estado_asignado(): void
    {
        $cargador = Cargador::factory()->create(['estado' => EstadoCargador::Disponible]);

        AsignacionCargadorWorkflow::actualizarEstadoCargador($cargador->serial);

        $this->assertSame(EstadoCargador::Asignado, $cargador->fresh()->estado);
    }

    public function test_rejects_blocked_charger(): void
    {
        $cargador = Cargador::factory()->create(['estado' => EstadoCargador::Dañado]);

        $this->assertTrue(AsignacionCargadorWorkflow::cargadorEstaBloqueado($cargador));
    }

    public function test_blocks_move_when_origin_has_user_and_notifies_actor(): void
    {
        $actor = User::factory()->create();
        $usuario = User::factory()->create(['name' => 'Ana Ruiz']);
        $this->actingAs($actor);

        $origen = Equipo::factory()->create(['estado' => EstadoEquipo::Asignado]);
        $destino = Equipo::factory()->create(['estado' => EstadoEquipo::SinAsignar]);
        $cargador = Cargador::factory()->create(['estado' => EstadoCargador::Asignado]);

        AsignacionCargador::factory()->create([
            'serial_cargador' => $cargador->serial,
            'service_tag' => $origen->service_tag,
            'fecha_fin' => null,
        ]);

        $asignacionUsuario = AsignacionEquipo::factory()->create([
            'service_tag' => $origen->service_tag,
            'usuario_id' => $usuario->id,
            'fecha_fin' => null,
        ]);

        try {
            app(MovimientoCargador::class)->prestarOCeder(
                $cargador->serial,
                $destino->service_tag,
                ModoAsignacionCargador::Temporal,
                $actor,
            );
            $this->fail('Se esperaba un bloqueo por usuario asignado.');
        } catch (EquipoOrigenConUsuarioException) {
            //
        }

        $this->assertSame(1, AsignacionCargador::query()->whereNull('fecha_fin')->count());

        $notification = $actor->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertStringContainsString('Ana Ruiz', (string) data_get($notification->data, 'body'));
        $this->assertSame(
            AsignacionEquipoResource::getUrl('view', ['record' => $asignacionUsuario]),
            data_get($notification->data, 'actions.0.url'),
        );
    }

    public function test_prestado_returns_to_origin_when_new_charger_is_registered(): void
    {
        $actor = User::factory()->create();
        $origen = Equipo::factory()->create(['estado' => EstadoEquipo::SinAsignar]);
        $destino = Equipo::factory()->create(['estado' => EstadoEquipo::SinAsignar]);
        $prestado = Cargador::factory()->create(['estado' => EstadoCargador::Asignado]);
        $nuevo = Cargador::factory()->create(['estado' => EstadoCargador::Disponible]);

        AsignacionCargador::factory()->create([
            'serial_cargador' => $prestado->serial,
            'service_tag' => $origen->service_tag,
            'fecha_fin' => null,
        ]);

        $movimiento = app(MovimientoCargador::class)->prestarOCeder(
            $prestado->serial,
            $destino->service_tag,
            ModoAsignacionCargador::Temporal,
            $actor,
        );

        $this->assertSame($origen->service_tag, $movimiento->service_tag_origen);
        $this->assertSame(EstadoCargador::Temporal, $prestado->fresh()->estado);

        app(MovimientoCargador::class)->registrarComprado($movimiento, $nuevo->serial);

        $this->assertNotNull($movimiento->fresh()->fecha_fin);
        $this->assertTrue(AsignacionCargador::query()
            ->where('serial_cargador', $prestado->serial)
            ->where('service_tag', $origen->service_tag)
            ->whereNull('fecha_fin')
            ->exists());
        $this->assertTrue(AsignacionCargador::query()
            ->where('serial_cargador', $nuevo->serial)
            ->where('service_tag', $destino->service_tag)
            ->whereNull('fecha_fin')
            ->exists());
        $this->assertSame(EstadoCargador::Asignado, $prestado->fresh()->estado);
        $this->assertSame(EstadoCargador::Asignado, $nuevo->fresh()->estado);
    }

    public function test_cedido_stays_and_new_charger_goes_to_origin(): void
    {
        $actor = User::factory()->create();
        $origen = Equipo::factory()->create(['estado' => EstadoEquipo::SinAsignar]);
        $destino = Equipo::factory()->create(['estado' => EstadoEquipo::SinAsignar]);
        $cedido = Cargador::factory()->create(['estado' => EstadoCargador::Asignado]);
        $nuevo = Cargador::factory()->create(['estado' => EstadoCargador::Disponible]);

        AsignacionCargador::factory()->create([
            'serial_cargador' => $cedido->serial,
            'service_tag' => $origen->service_tag,
            'fecha_fin' => null,
        ]);

        $movimiento = app(MovimientoCargador::class)->prestarOCeder(
            $cedido->serial,
            $destino->service_tag,
            ModoAsignacionCargador::Reasignacion,
            $actor,
        );

        app(MovimientoCargador::class)->registrarComprado($movimiento, $nuevo->serial);

        $this->assertNull($movimiento->fresh()->fecha_fin);
        $this->assertNull($movimiento->fresh()->service_tag_origen);
        $this->assertSame($destino->service_tag, $movimiento->fresh()->service_tag);
        $this->assertSame(EstadoCargador::Asignado, $cedido->fresh()->estado);
        $this->assertTrue(AsignacionCargador::query()
            ->where('serial_cargador', $nuevo->serial)
            ->where('service_tag', $origen->service_tag)
            ->whereNull('fecha_fin')
            ->exists());
        $this->assertSame(EstadoCargador::Asignado, $nuevo->fresh()->estado);
    }
}
