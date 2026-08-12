<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Movimientos\MovimientoResource;
use App\Filament\Resources\Movimientos\Pages\CreateMovimiento;
use App\Filament\Resources\Movimientos\Pages\EditMovimiento;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\UserResource;
use App\Models\Area;
use App\Models\AsignacionEquipo;
use App\Models\Cargo;
use App\Models\Movimiento;
use App\Models\SoftwareUsuario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BajaDesdeUsuarioDesactivaAccesosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        UserResource::skipAuthorization();
        MovimientoResource::skipAuthorization();

        $this->actingAs(User::factory()->create());
    }

    /**
     * Se lee la sesión en vez de usar assertNotified() porque ese helper hace
     * `session()->pull('filament.claimed_notifications')` primero, y una clave
     * residual de otro test del suite deja la aserción ciega.
     */
    private function notificacionesConTitulo(string $titulo): int
    {
        $notificaciones = session('filament.notifications') ?? [];

        return count(array_filter(
            $notificaciones,
            fn (array $notificacion): bool => ($notificacion['title'] ?? null) === $titulo,
        ));
    }

    /**
     * @return array{0: User, 1: Cargo, 2: SoftwareUsuario}
     */
    private function colaboradorDeAlta(): array
    {
        $area = Area::factory()->create();
        $cargo = Cargo::factory()->create(['area_id' => $area->id_area]);
        $colaborador = User::factory()->create([
            'nombres' => 'Ana',
            'apellidos' => 'Pérez',
            'id_area' => $area->id_area,
            'id_cargo' => $cargo->id_cargo,
        ]);

        Movimiento::factory()->alta()->create([
            'usuario_id' => $colaborador->id,
            'cargo_id' => $cargo->id_cargo,
            'fecha' => '2026-01-10',
        ]);

        $acceso = SoftwareUsuario::factory()->create([
            'usuario_id' => $colaborador->id,
            'estado' => true,
        ]);

        return [$colaborador, $cargo, $acceso];
    }

    public function test_bajar_el_toggle_en_edit_user_desactiva_los_accesos(): void
    {
        [$colaborador, , $acceso] = $this->colaboradorDeAlta();

        $asignacion = AsignacionEquipo::factory()->create([
            'usuario_id' => $colaborador->id,
            'fecha_inicio' => '2026-01-10',
            'fecha_fin' => null,
        ]);

        Livewire::test(EditUser::class, ['record' => $colaborador->id])
            ->fillForm(['movimientoActual' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse($acceso->refresh()->estado);
        $this->assertNotNull($asignacion->refresh()->fecha_fin);
    }

    public function test_crear_un_movimiento_de_baja_desactiva_los_accesos(): void
    {
        [$colaborador, $cargo, $acceso] = $this->colaboradorDeAlta();

        Livewire::test(CreateMovimiento::class)
            ->fillForm([
                'usuario_id' => $colaborador->id,
                'tipo' => 'Baja',
                'fecha' => '2026-04-30',
                'cargo_id' => $cargo->id_cargo,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertFalse($acceso->refresh()->estado);
        $this->assertSame(
            1,
            $this->notificacionesConTitulo('Baja procesada'),
            'Se esperaba una única notificación resumen de la baja.',
        );
    }

    public function test_editar_un_movimiento_de_alta_a_baja_desactiva_los_accesos(): void
    {
        [$colaborador, $cargo, $acceso] = $this->colaboradorDeAlta();

        $movimiento = Movimiento::factory()->alta()->create([
            'usuario_id' => $colaborador->id,
            'cargo_id' => $cargo->id_cargo,
            'fecha' => '2026-04-30',
        ]);

        Livewire::test(EditMovimiento::class, ['record' => $movimiento->id])
            ->fillForm(['tipo' => 'Baja'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse($acceso->refresh()->estado);
    }

    public function test_un_alta_no_desactiva_nada(): void
    {
        $area = Area::factory()->create();
        $cargo = Cargo::factory()->create(['area_id' => $area->id_area]);
        $colaborador = User::factory()->create([
            'id_area' => $area->id_area,
            'id_cargo' => $cargo->id_cargo,
        ]);
        $acceso = SoftwareUsuario::factory()->create([
            'usuario_id' => $colaborador->id,
            'estado' => true,
        ]);

        Livewire::test(CreateMovimiento::class)
            ->fillForm([
                'usuario_id' => $colaborador->id,
                'tipo' => 'Alta',
                'fecha' => '2026-01-10',
                'cargo_id' => $cargo->id_cargo,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertTrue($acceso->refresh()->estado);
    }
}
