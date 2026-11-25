<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Software\Pages\EditSoftware;
use App\Filament\Resources\Software\RelationManagers\UsuariosRelationManager;
use App\Filament\Resources\Software\SoftwareResource;
use App\Filament\Resources\Users\Pages\ManageUserSoftware;
use App\Filament\Resources\Users\RelationManagers\SoftwareUsuariosRelationManager;
use App\Filament\Resources\Users\UserResource;
use App\Models\Cargo;
use App\Models\CategoriaSoftware;
use App\Models\Movimiento;
use App\Models\Software;
use App\Models\SoftwareCargo;
use App\Models\SoftwareUsuario;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class AsignarSoftwareEnBloqueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        SoftwareResource::skipAuthorization();
        UserResource::skipAuthorization();
        Gate::before(fn () => true);

        $this->actingAs(User::factory()->create());
    }

    public function test_el_usuario_tiene_las_acciones_ordenadas_junto_a_asignar_software(): void
    {
        $usuario = User::factory()->create();

        $acciones = collect(
            Livewire::test(SoftwareUsuariosRelationManager::class, $this->paginaDelUsuario($usuario))
                ->instance()
                ->getTable()
                ->getHeaderActions(),
        )->map(fn ($accion): string => $accion->getName())->values()->all();

        $this->assertSame(['create', 'asignarPorCargo', 'asignarPorCategoria'], $acciones);
    }

    public function test_asigna_al_usuario_el_software_activo_de_una_categoria(): void
    {
        $usuario = User::factory()->create();
        $categoria = CategoriaSoftware::factory()->create();
        $activo = Software::factory()->for($categoria, 'categoria')->create(['estado' => true]);
        $inactivo = Software::factory()->for($categoria, 'categoria')->create(['estado' => false]);
        $deOtraCategoria = Software::factory()->create(['estado' => true]);

        Livewire::test(SoftwareUsuariosRelationManager::class, $this->paginaDelUsuario($usuario))
            ->callAction(TestAction::make('asignarPorCategoria')->table(), [
                'categoria_id' => $categoria->id_categoria,
            ])
            ->assertHasNoFormErrors()
            ->assertNotified('Asignación por categoría completada');

        $this->assertDatabaseHas(SoftwareUsuario::class, ['usuario_id' => $usuario->id, 'id_software' => $activo->id_software, 'estado' => true]);
        $this->assertDatabaseMissing(SoftwareUsuario::class, ['usuario_id' => $usuario->id, 'id_software' => $inactivo->id_software]);
        $this->assertDatabaseMissing(SoftwareUsuario::class, ['usuario_id' => $usuario->id, 'id_software' => $deOtraCategoria->id_software]);
    }

    public function test_la_categoria_es_obligatoria(): void
    {
        $usuario = User::factory()->create();

        Livewire::test(SoftwareUsuariosRelationManager::class, $this->paginaDelUsuario($usuario))
            ->callAction(TestAction::make('asignarPorCategoria')->table(), [
                'categoria_id' => null,
            ])
            ->assertHasFormErrors(['categoria_id' => 'required']);
    }

    public function test_asigna_al_usuario_el_software_de_su_cargo(): void
    {
        $cargo = Cargo::factory()->create();
        $usuario = User::factory()->create(['id_cargo' => $cargo->id_cargo]);
        $delCargo = Software::factory()->create(['estado' => true]);
        $inactivo = Software::factory()->create(['estado' => false]);
        $deOtroCargo = Software::factory()->create(['estado' => true]);

        SoftwareCargo::factory()->create(['cargo_id' => $cargo->id_cargo, 'software_id' => $delCargo->id_software]);
        SoftwareCargo::factory()->create(['cargo_id' => $cargo->id_cargo, 'software_id' => $inactivo->id_software]);
        SoftwareCargo::factory()->create(['cargo_id' => Cargo::factory()->create()->id_cargo, 'software_id' => $deOtroCargo->id_software]);

        Livewire::test(SoftwareUsuariosRelationManager::class, $this->paginaDelUsuario($usuario))
            ->callAction(TestAction::make('asignarPorCargo')->table())
            ->assertNotified('Asignación por cargo completada');

        $this->assertSame(
            [$delCargo->id_software],
            SoftwareUsuario::query()->where('usuario_id', $usuario->id)->pluck('id_software')->all(),
        );
    }

    public function test_la_asignacion_por_cargo_se_deshabilita_si_el_usuario_no_tiene_cargo(): void
    {
        $usuario = User::factory()->create(['id_cargo' => null]);

        Livewire::test(SoftwareUsuariosRelationManager::class, $this->paginaDelUsuario($usuario))
            ->assertActionDisabled(TestAction::make('asignarPorCargo')->table());
    }

    public function test_restaura_y_activa_asignaciones_eliminadas_o_inactivas_sin_duplicarlas(): void
    {
        $cargo = Cargo::factory()->create();
        $usuario = User::factory()->create(['id_cargo' => $cargo->id_cargo]);
        $eliminado = Software::factory()->create(['estado' => true]);
        $inactivo = Software::factory()->create(['estado' => true]);
        $yaActivo = Software::factory()->create(['estado' => true]);

        foreach ([$eliminado, $inactivo, $yaActivo] as $software) {
            SoftwareCargo::factory()->create(['cargo_id' => $cargo->id_cargo, 'software_id' => $software->id_software]);
        }

        SoftwareUsuario::factory()->create(['usuario_id' => $usuario->id, 'id_software' => $eliminado->id_software, 'estado' => true])->delete();
        SoftwareUsuario::factory()->create(['usuario_id' => $usuario->id, 'id_software' => $inactivo->id_software, 'estado' => false]);
        SoftwareUsuario::factory()->create(['usuario_id' => $usuario->id, 'id_software' => $yaActivo->id_software, 'estado' => true]);

        Livewire::test(SoftwareUsuariosRelationManager::class, $this->paginaDelUsuario($usuario))
            ->callAction(TestAction::make('asignarPorCargo')->table())
            ->assertNotified('Asignación por cargo completada');

        $asignaciones = SoftwareUsuario::withTrashed()->where('usuario_id', $usuario->id)->get();

        $this->assertCount(3, $asignaciones);
        $this->assertTrue($asignaciones->every(fn (SoftwareUsuario $asignacion): bool => $asignacion->estado && ! $asignacion->trashed()));
    }

    public function test_asigna_el_software_a_las_personas_del_cargo_que_no_estan_de_baja(): void
    {
        $cargo = Cargo::factory()->create();
        $software = Software::factory()->create(['estado' => true]);
        $activo = User::factory()->create(['id_cargo' => $cargo->id_cargo]);
        $deBaja = User::factory()->create(['id_cargo' => $cargo->id_cargo]);
        $deOtroCargo = User::factory()->create(['id_cargo' => Cargo::factory()->create()->id_cargo]);

        Movimiento::factory()->baja()->create(['usuario_id' => $deBaja->id, 'cargo_id' => $cargo->id_cargo]);

        Livewire::test(UsuariosRelationManager::class, [
            'ownerRecord' => $software,
            'pageClass' => EditSoftware::class,
        ])
            ->callAction(TestAction::make('asignarPorCargo')->table(), [
                'cargo_id' => $cargo->id_cargo,
            ])
            ->assertHasNoFormErrors()
            ->assertNotified('Asignación por cargo completada');

        $this->assertDatabaseHas(SoftwareUsuario::class, ['usuario_id' => $activo->id, 'id_software' => $software->id_software, 'estado' => true]);
        $this->assertDatabaseMissing(SoftwareUsuario::class, ['usuario_id' => $deBaja->id, 'id_software' => $software->id_software]);
        $this->assertDatabaseMissing(SoftwareUsuario::class, ['usuario_id' => $deOtroCargo->id, 'id_software' => $software->id_software]);
    }

    public function test_la_asignacion_por_cargo_se_deshabilita_si_el_software_esta_inactivo(): void
    {
        $software = Software::factory()->create(['estado' => false]);

        Livewire::test(UsuariosRelationManager::class, [
            'ownerRecord' => $software,
            'pageClass' => EditSoftware::class,
        ])
            ->assertActionDisabled(TestAction::make('asignarPorCargo')->table());
    }

    /**
     * @return array{ownerRecord: User, pageClass: class-string}
     */
    private function paginaDelUsuario(User $usuario): array
    {
        return [
            'ownerRecord' => $usuario,
            'pageClass' => ManageUserSoftware::class,
        ];
    }
}
