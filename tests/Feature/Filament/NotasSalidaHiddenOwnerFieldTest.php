<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Equipos\Pages\EditEquipo;
use App\Filament\Resources\Equipos\RelationManagers\NotasSalidaRelationManager;
use App\Models\Equipo;
use App\Models\User;
use App\Models\UsuarioExterno;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class NotasSalidaHiddenOwnerFieldTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.env' => 'local']);
        Gate::before(fn () => true);

        $this->actingAs(User::factory()->create());
    }

    public function test_creating_nota_salida_through_equipo_relation_manager_succeeds(): void
    {
        $equipo = Equipo::factory()->create();
        $usuarioExterno = UsuarioExterno::factory()->create();

        Livewire::test(NotasSalidaRelationManager::class, [
            'ownerRecord' => $equipo,
            'pageClass' => EditEquipo::class,
        ])
            ->callTableAction('create', data: [
                'usuario_externo_id' => $usuarioExterno->id,
                'numero' => '901-2026',
                'fecha' => now()->toDateString(),
                'nota' => 'Salida de prueba',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('nota_salida_equipos', [
            'equipo_id' => $equipo->service_tag,
            'usuario_externo_id' => $usuarioExterno->id,
            'numero' => '901-2026',
        ]);
    }
}
