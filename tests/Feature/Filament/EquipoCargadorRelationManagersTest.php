<?php

namespace Tests\Feature\Filament;

use App\Enums\EstadoCargador;
use App\Enums\EstadoEquipo;
use App\Filament\Resources\Cargadors\CargadorResource;
use App\Filament\Resources\Cargadors\Pages\EditCargador;
use App\Filament\Resources\Cargadors\RelationManagers\AsignacionesRelationManager as CargadorAsignacionesRelationManager;
use App\Filament\Resources\Equipos\EquipoResource;
use App\Filament\Resources\Equipos\Pages\EditEquipo;
use App\Filament\Resources\Equipos\RelationManagers\AsignacionesCargadorRelationManager;
use App\Filament\Resources\Equipos\RelationManagers\AsignacionesUsuarioRelationManager;
use App\Filament\Resources\Users\Pages\ManageUserEquipos;
use App\Filament\Resources\Users\RelationManagers\AsignacionesEquipoRelationManager;
use App\Filament\Resources\Users\UserResource;
use App\Models\Cargador;
use App\Models\Equipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class EquipoCargadorRelationManagersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        EquipoResource::skipAuthorization();
        CargadorResource::skipAuthorization();
        UserResource::skipAuthorization();
        config(['app.env' => 'local']);
        Gate::before(fn () => true);

        $this->actingAs(User::factory()->create());
    }

    public function test_creating_an_asignacion_through_equipo_usuario_relation_manager_sets_service_tag_and_syncs_estado(): void
    {
        $equipo = Equipo::factory()->create(['estado' => EstadoEquipo::SinAsignar]);
        $user = User::factory()->create();

        Livewire::test(AsignacionesUsuarioRelationManager::class, [
            'ownerRecord' => $equipo,
            'pageClass' => EditEquipo::class,
        ])
            ->callTableAction('create', data: [
                'usuario_id' => $user->id,
                'fecha_inicio' => now()->toDateString(),
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('asignacion_equipos', [
            'service_tag' => $equipo->service_tag,
            'usuario_id' => $user->id,
        ]);

        $this->assertSame(EstadoEquipo::Asignado, $equipo->fresh()->estado);
    }

    public function test_creating_an_asignacion_through_equipo_cargador_relation_manager_sets_service_tag_and_syncs_estado(): void
    {
        $equipo = Equipo::factory()->create(['estado' => EstadoEquipo::SinAsignar]);
        $cargador = Cargador::factory()->create(['estado' => EstadoCargador::Disponible]);

        Livewire::test(AsignacionesCargadorRelationManager::class, [
            'ownerRecord' => $equipo,
            'pageClass' => EditEquipo::class,
        ])
            ->callTableAction('create', data: [
                'serial_cargador' => $cargador->serial,
                'fecha_inicio' => now()->toDateString(),
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('asignacion_cargadores', [
            'service_tag' => $equipo->service_tag,
            'serial_cargador' => $cargador->serial,
        ]);

        $this->assertSame(EstadoCargador::Asignado, $cargador->fresh()->estado);
    }

    public function test_creating_an_asignacion_through_cargador_relation_manager_sets_serial_cargador_and_syncs_estado(): void
    {
        $cargador = Cargador::factory()->create(['estado' => EstadoCargador::Disponible]);
        $equipo = Equipo::factory()->create(['estado' => EstadoEquipo::SinAsignar]);

        Livewire::test(CargadorAsignacionesRelationManager::class, [
            'ownerRecord' => $cargador,
            'pageClass' => EditCargador::class,
        ])
            ->callTableAction('create', data: [
                'service_tag' => $equipo->service_tag,
                'fecha_inicio' => now()->toDateString(),
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('asignacion_cargadores', [
            'serial_cargador' => $cargador->serial,
            'service_tag' => $equipo->service_tag,
        ]);

        $this->assertSame(EstadoCargador::Asignado, $cargador->fresh()->estado);
    }

    public function test_creating_an_asignacion_through_user_equipo_relation_manager_sets_usuario_id_and_syncs_estado(): void
    {
        $user = User::factory()->create();
        $equipo = Equipo::factory()->create(['estado' => EstadoEquipo::SinAsignar]);

        Livewire::test(AsignacionesEquipoRelationManager::class, [
            'ownerRecord' => $user,
            'pageClass' => ManageUserEquipos::class,
        ])
            ->callTableAction('create', data: [
                'service_tag' => $equipo->service_tag,
                'fecha_inicio' => now()->toDateString(),
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('asignacion_equipos', [
            'usuario_id' => $user->id,
            'service_tag' => $equipo->service_tag,
        ]);

        $this->assertSame(EstadoEquipo::Asignado, $equipo->fresh()->estado);
    }
}
