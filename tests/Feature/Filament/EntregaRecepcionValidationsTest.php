<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\EntregaEquipos\EntregaEquipoResource;
use App\Filament\Resources\EntregaEquipos\Pages\CreateEntregaEquipo;
use App\Models\AsignacionEquipo;
use App\Models\EntregaEquipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class EntregaRecepcionValidationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        EntregaEquipoResource::skipAuthorization();
        config(['app.env' => 'local']);
        Gate::before(fn () => true);

        $this->actingAs(User::factory()->create());
    }

    public function test_it_rejects_entrega_for_trashed_asignacion(): void
    {
        $asignacion = AsignacionEquipo::factory()->create();
        $asignacion->delete();

        Livewire::test(CreateEntregaEquipo::class)
            ->fillForm([
                'asignacion_id' => $asignacion->id,
                'numero' => 'ENT-1001',
                'fecha' => now()->toDateString(),
                'mouse' => false,
                'cooler' => false,
                'audifonos' => false,
            ])
            ->call('create')
            ->assertHasFormErrors(['asignacion_id']);
    }

    public function test_it_rejects_second_entrega_for_same_asignacion(): void
    {
        $asignacion = AsignacionEquipo::factory()->create();
        EntregaEquipo::factory()->create(['asignacion_id' => $asignacion->id]);

        Livewire::test(CreateEntregaEquipo::class)
            ->fillForm([
                'asignacion_id' => $asignacion->id,
                'numero' => 'ENT-1002',
                'fecha' => now()->toDateString(),
                'mouse' => false,
                'cooler' => false,
                'audifonos' => false,
            ])
            ->call('create')
            ->assertHasFormErrors(['asignacion_id']);
    }

    public function test_it_allows_creating_entrega_for_active_asignacion(): void
    {
        $asignacion = AsignacionEquipo::factory()->create();

        Livewire::test(CreateEntregaEquipo::class)
            ->fillForm([
                'asignacion_id' => $asignacion->id,
                'numero' => 'ENT-1003',
                'fecha' => now()->toDateString(),
                'mouse' => true,
                'cooler' => false,
                'audifonos' => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('entrega_equipos', [
            'asignacion_id' => $asignacion->id,
            'numero' => 'ENT-1003',
        ]);
    }
}
