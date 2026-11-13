<?php

namespace Tests\Feature\Filament;

use App\Enums\EstadoEquipo;
use App\Filament\Resources\Movimientos\MovimientoResource;
use App\Filament\Resources\Movimientos\Pages\CreateMovimiento;
use App\Filament\Resources\Users\Actions\ReasignarTrasReingresoAction;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\UserResource;
use App\Models\Area;
use App\Models\AsigCelular;
use App\Models\AsignacionEquipo;
use App\Models\Cargo;
use App\Models\Equipo;
use App\Models\Movimiento;
use App\Models\Software;
use App\Models\SoftwareUsuario;
use App\Models\User;
use App\Services\Movimientos\ReasignarTrasReingreso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * «Reasignar todo»: al reingresar un colaborador se le puede devolver, a
 * pedido, lo que su Baja le quitó. El Alta por sí sola no devuelve nada.
 */
class ReasignarTrasReingresoTest extends TestCase
{
    use RefreshDatabase;

    private Cargo $cargo;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        UserResource::skipAuthorization();
        MovimientoResource::skipAuthorization();

        $this->actingAs(User::factory()->create());
    }

    protected function tearDown(): void
    {
        UserResource::skipAuthorization(false);

        parent::tearDown();
    }

    public function test_el_alta_sola_no_devuelve_nada_pero_avisa_del_reingreso(): void
    {
        [$colaborador, $acceso, $equipo] = $this->colaboradorConBaja();

        Livewire::test(CreateMovimiento::class)
            ->fillForm([
                'usuario_id' => $colaborador->id,
                'tipo' => 'Alta',
                'fecha' => '2026-06-01',
                'cargo_id' => $this->cargo->id_cargo,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified('Reingreso detectado');

        $this->assertFalse($acceso->refresh()->estado);
        $this->assertNotNull($equipo->refresh()->fecha_fin);
    }

    public function test_reasignar_todo_devuelve_accesos_equipo_y_celular(): void
    {
        [$colaborador, $acceso, $equipo, $celular] = $this->colaboradorConBaja();
        $inactivoDeAntes = SoftwareUsuario::factory()->create([
            'usuario_id' => $colaborador->id,
            'id_software' => Software::factory(),
            'estado' => false,
        ]);
        $this->reingresar($colaborador);

        Livewire::test(EditUser::class, ['record' => $colaborador->id])
            ->assertActionVisible('reasignarTrasReingreso')
            ->callAction('reasignarTrasReingreso')
            ->assertNotified('Reasignación completa');

        $this->assertTrue($acceso->refresh()->estado);
        $this->assertTrue($inactivoDeAntes->refresh()->estado, 'Se reactiva todo, sin filtrar.');
        $this->assertNull($equipo->refresh()->fecha_fin);
        $this->assertSame(EstadoEquipo::Asignado, $equipo->equipo->refresh()->estado);
        $this->assertNull($celular->refresh()->fecha_fin);

        // Ya no queda nada por devolver: la acción desaparece.
        Livewire::test(EditUser::class, ['record' => $colaborador->id])
            ->assertActionHidden('reasignarTrasReingreso');
    }

    public function test_lo_que_hoy_tiene_otra_persona_no_se_toca_y_se_informa(): void
    {
        [$colaborador, $acceso, $equipo, $celular] = $this->colaboradorConBaja();

        $nuevaAsignacion = AsignacionEquipo::factory()->create([
            'service_tag' => $equipo->service_tag,
            'fecha_inicio' => '2026-05-10',
            'fecha_fin' => null,
        ]);
        $equipo->equipo->update(['estado' => EstadoEquipo::Asignado]);

        $this->reingresar($colaborador);

        $resumen = app(ReasignarTrasReingreso::class)->ejecutar($colaborador);

        $this->assertSame(0, $resumen->equipos);
        $this->assertSame(1, $resumen->celulares);
        $this->assertSame(["equipo {$equipo->service_tag}"], $resumen->omitidos);
        $this->assertNotNull($equipo->refresh()->fecha_fin);
        $this->assertNull($nuevaAsignacion->refresh()->fecha_fin);
        $this->assertTrue($acceso->refresh()->estado);
        $this->assertNull($celular->refresh()->fecha_fin);
    }

    public function test_solo_se_reabre_lo_que_cerro_la_ultima_baja(): void
    {
        [$colaborador, , $equipo] = $this->colaboradorConBaja();

        $historica = AsignacionEquipo::factory()->create([
            'usuario_id' => $colaborador->id,
            'fecha_inicio' => '2025-01-10',
            'fecha_fin' => '2025-06-30',
        ]);

        $this->reingresar($colaborador);
        app(ReasignarTrasReingreso::class)->ejecutar($colaborador);

        $this->assertNull($equipo->refresh()->fecha_fin);
        $this->assertNotNull($historica->refresh()->fecha_fin);
    }

    public function test_la_accion_no_aparece_si_no_hay_reingreso(): void
    {
        // Solo Alta, sin Baja previa.
        $soloAlta = $this->colaboradorDeAlta();
        SoftwareUsuario::factory()->create([
            'usuario_id' => $soloAlta->id,
            'id_software' => Software::factory(),
            'estado' => false,
        ]);

        Livewire::test(EditUser::class, ['record' => $soloAlta->id])
            ->assertActionHidden('reasignarTrasReingreso');

        // De Baja todavía: no se devuelve nada hasta que haya un Alta.
        [$deBaja] = $this->colaboradorConBaja();

        Livewire::test(EditUser::class, ['record' => $deBaja->id])
            ->assertActionHidden('reasignarTrasReingreso');

        $this->assertFalse(app(ReasignarTrasReingreso::class)->ejecutar($deBaja)->hayAlgo());
    }

    public function test_reasignar_todo_exige_permiso_de_editar_usuarios(): void
    {
        [$colaborador] = $this->colaboradorConBaja();
        $this->reingresar($colaborador);

        UserResource::skipAuthorization(false);

        $this->actingAs($this->usuarioCon(['ViewAny:User', 'View:User']));

        Livewire::test(ViewUser::class, ['record' => $colaborador->id])
            ->assertActionHidden('reasignarTrasReingreso');

        $this->actingAs($this->usuarioCon(['ViewAny:User', 'View:User', 'Update:User']));

        Livewire::test(ViewUser::class, ['record' => $colaborador->id])
            ->assertActionVisible('reasignarTrasReingreso');
    }

    public function test_el_aviso_dice_que_se_devuelve_todo_sin_excepcion(): void
    {
        [$colaborador] = $this->colaboradorConBaja();
        $this->reingresar($colaborador);

        $aviso = ReasignarTrasReingresoAction::aviso($colaborador);

        $this->assertStringContainsString('sin excepción', $aviso);
        $this->assertStringContainsString('1 acceso, 1 equipo y 1 celular', $aviso);
        $this->assertStringContainsString('30/04/2026', $aviso);
        $this->assertStringContainsString('Lo que hoy tenga otra persona no se toca', $aviso);
    }

    private function colaboradorDeAlta(): User
    {
        $area = Area::factory()->create();
        $this->cargo = Cargo::factory()->create(['area_id' => $area->id_area]);

        $colaborador = User::factory()->create([
            'id_area' => $area->id_area,
            'id_cargo' => $this->cargo->id_cargo,
        ]);

        Movimiento::factory()->alta()->create([
            'usuario_id' => $colaborador->id,
            'cargo_id' => $this->cargo->id_cargo,
            'fecha' => '2026-01-10',
        ]);

        return $colaborador;
    }

    /**
     * Un colaborador con un acceso, un equipo y un celular, ya dado de Baja.
     *
     * @return array{0: User, 1: SoftwareUsuario, 2: AsignacionEquipo, 3: AsigCelular}
     */
    private function colaboradorConBaja(): array
    {
        $colaborador = $this->colaboradorDeAlta();

        $acceso = SoftwareUsuario::factory()->create([
            'usuario_id' => $colaborador->id,
            'id_software' => Software::factory(),
            'estado' => true,
        ]);

        $equipo = AsignacionEquipo::factory()->create([
            'service_tag' => Equipo::factory()->create(['estado' => EstadoEquipo::Asignado])->service_tag,
            'usuario_id' => $colaborador->id,
            'fecha_inicio' => '2026-01-10',
            'fecha_fin' => null,
        ]);

        $celular = AsigCelular::factory()->create([
            'usuario_id' => $colaborador->id,
            'fecha_ini' => '2026-01-10',
            'fecha_fin' => null,
        ]);

        Movimiento::factory()->baja()->create([
            'usuario_id' => $colaborador->id,
            'cargo_id' => $this->cargo->id_cargo,
            'fecha' => '2026-04-30',
        ]);

        return [$colaborador, $acceso, $equipo, $celular];
    }

    private function reingresar(User $colaborador): void
    {
        Movimiento::factory()->alta()->create([
            'usuario_id' => $colaborador->id,
            'cargo_id' => $this->cargo->id_cargo,
            'fecha' => '2026-06-01',
        ]);
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
