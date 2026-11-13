<?php

namespace Tests\Feature\Filament;

use App\Enums\EstadoCargador;
use App\Enums\EstadoEquipo;
use App\Enums\ModoAsignacionCargador;
use App\Filament\Resources\AsignacionCargadors\AsignacionCargadorResource;
use App\Filament\Resources\AsignacionCargadors\Pages\EditAsignacionCargador;
use App\Filament\Resources\AsignacionCargadors\Pages\ListAsignacionCargadors;
use App\Filament\Resources\AsignacionCargadors\Pages\ViewAsignacionCargador;
use App\Models\AsignacionCargador;
use App\Models\Cargador;
use App\Models\Equipo;
use App\Models\User;
use App\Services\MovimientoCargador;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * «Usar cargador de otro equipo» exige poder crear asignaciones de cargador y
 * «Registrar cargador comprado», poder editarlas. Ver la asignación no alcanza.
 */
class CargadorAccionesPermisosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Otros tests desactivan la autorización de Filament para todo el proceso.
        AsignacionCargadorResource::skipAuthorization(false);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_usar_cargador_de_otro_equipo_exige_permiso_de_crear(): void
    {
        $this->actingAs($this->usuarioCon(['ViewAny:AsignacionCargador', 'View:AsignacionCargador']));

        Livewire::test(ListAsignacionCargadors::class)
            ->assertActionHidden('usarCargadorDeOtroEquipo');

        $this->actingAs($this->usuarioCon(['ViewAny:AsignacionCargador', 'Create:AsignacionCargador']));

        Livewire::test(ListAsignacionCargadors::class)
            ->assertActionVisible('usarCargadorDeOtroEquipo');
    }

    public function test_sin_permiso_de_crear_no_se_puede_ejecutar_el_movimiento(): void
    {
        [$destino, $prestado] = $this->cargadorEnOtroEquipo();

        $this->actingAs($this->usuarioCon(['ViewAny:AsignacionCargador', 'View:AsignacionCargador']));

        // Aunque se intente abrir la acción a la fuerza, Filament no la monta.
        Livewire::test(ListAsignacionCargadors::class)
            ->call('mountAction', 'usarCargadorDeOtroEquipo')
            ->assertActionNotMounted('usarCargadorDeOtroEquipo');

        $this->assertFalse(AsignacionCargador::query()->where('service_tag', $destino->service_tag)->exists());
        $this->assertSame(EstadoCargador::Asignado, $prestado->fresh()->estado);
    }

    public function test_con_permiso_de_crear_el_movimiento_se_registra(): void
    {
        [$destino, $prestado] = $this->cargadorEnOtroEquipo();

        $this->actingAs($this->usuarioCon(['ViewAny:AsignacionCargador', 'Create:AsignacionCargador']));

        Livewire::test(ListAsignacionCargadors::class)
            ->callAction('usarCargadorDeOtroEquipo', [
                'service_tag' => $destino->service_tag,
                'serial_cargador' => $prestado->serial,
                'modo' => ModoAsignacionCargador::Temporal->value,
            ])
            ->assertHasNoActionErrors();

        $this->assertTrue(AsignacionCargador::query()
            ->where('service_tag', $destino->service_tag)
            ->where('serial_cargador', $prestado->serial)
            ->whereNull('fecha_fin')
            ->exists());
    }

    public function test_registrar_cargador_comprado_exige_permiso_de_editar(): void
    {
        $movimiento = $this->asignacionPrestada();

        $this->actingAs($this->usuarioCon(['ViewAny:AsignacionCargador', 'View:AsignacionCargador']));

        Livewire::test(ViewAsignacionCargador::class, ['record' => $movimiento->getKey()])
            ->assertActionHidden('registrarCargadorComprado');

        $this->actingAs($this->usuarioCon(['ViewAny:AsignacionCargador', 'View:AsignacionCargador', 'Update:AsignacionCargador']));

        Livewire::test(ViewAsignacionCargador::class, ['record' => $movimiento->getKey()])
            ->assertActionVisible('registrarCargadorComprado');

        Livewire::test(EditAsignacionCargador::class, ['record' => $movimiento->getKey()])
            ->assertActionVisible('registrarCargadorComprado');
    }

    public function test_sin_permiso_de_editar_no_se_puede_registrar_el_cargador_comprado(): void
    {
        $movimiento = $this->asignacionPrestada();
        $nuevo = Cargador::factory()->create(['estado' => EstadoCargador::Disponible]);

        $this->actingAs($this->usuarioCon(['ViewAny:AsignacionCargador', 'View:AsignacionCargador']));

        // Aunque se intente abrir la acción a la fuerza, Filament no la monta.
        Livewire::test(ViewAsignacionCargador::class, ['record' => $movimiento->getKey()])
            ->call('mountAction', 'registrarCargadorComprado')
            ->assertActionNotMounted('registrarCargadorComprado');

        $this->assertNull($movimiento->fresh()->fecha_fin);
        $this->assertSame(EstadoCargador::Disponible, $nuevo->fresh()->estado);
    }

    public function test_con_permiso_de_editar_el_cargador_comprado_se_registra(): void
    {
        $movimiento = $this->asignacionPrestada();
        $nuevo = Cargador::factory()->create(['estado' => EstadoCargador::Disponible]);

        $this->actingAs($this->usuarioCon(['ViewAny:AsignacionCargador', 'View:AsignacionCargador', 'Update:AsignacionCargador']));

        Livewire::test(ViewAsignacionCargador::class, ['record' => $movimiento->getKey()])
            ->callAction('registrarCargadorComprado', ['serial_nuevo' => $nuevo->serial])
            ->assertHasNoActionErrors();

        $this->assertNotNull($movimiento->fresh()->fecha_fin);
        $this->assertSame(EstadoCargador::Asignado, $nuevo->fresh()->estado);
    }

    /**
     * Un equipo sin cargador y un cargador que hoy tiene otro equipo sin usuario.
     *
     * @return array{0: Equipo, 1: Cargador}
     */
    private function cargadorEnOtroEquipo(): array
    {
        $origen = Equipo::factory()->create(['estado' => EstadoEquipo::SinAsignar]);
        $destino = Equipo::factory()->create(['estado' => EstadoEquipo::SinAsignar]);
        $prestado = Cargador::factory()->create(['estado' => EstadoCargador::Asignado]);

        AsignacionCargador::factory()->create([
            'serial_cargador' => $prestado->serial,
            'service_tag' => $origen->service_tag,
            'fecha_fin' => null,
        ]);

        return [$destino, $prestado];
    }

    /**
     * Asignación activa con equipo de origen: la que admite «Registrar cargador comprado».
     */
    private function asignacionPrestada(): AsignacionCargador
    {
        [$destino, $prestado] = $this->cargadorEnOtroEquipo();

        return app(MovimientoCargador::class)->prestarOCeder(
            $prestado->serial,
            $destino->service_tag,
            ModoAsignacionCargador::Temporal,
            User::factory()->create(),
        );
    }

    /**
     * @param  list<string>  $permisos
     */
    private function usuarioCon(array $permisos): User
    {
        $rol = Role::create(['name' => 'rol_'.Str::random(8), 'guard_name' => 'web']);

        foreach ($permisos as $permiso) {
            $rol->givePermissionTo(Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']));
        }

        $usuario = User::factory()->create();
        $usuario->assignRole($rol);

        return $usuario;
    }
}
