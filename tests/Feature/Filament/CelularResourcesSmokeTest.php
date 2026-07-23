<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Celulares\Pages\CreateCelular;
use App\Filament\Resources\Celulares\Pages\ListCelulars;
use App\Filament\Resources\EntregaCelulars\Pages\CreateEntregaCelular;
use App\Filament\Resources\EntregaCelulars\Pages\ListEntregaCelulars;
use App\Models\AsigCelular;
use App\Models\Celular;
use App\Models\EntregaCelular;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class CelularResourcesSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(fn () => true);

        $this->actingAs(User::factory()->create());
    }

    public function test_can_list_and_create_celular(): void
    {
        $celular = Celular::factory()->create([
            'id' => 'CEL-TEST-01',
            'marca' => 'Samsung',
            'modelo' => 'A54',
        ]);

        Livewire::test(ListCelulars::class)
            ->assertCanSeeTableRecords([$celular]);

        Livewire::test(CreateCelular::class)
            ->fillForm([
                'id' => 'CEL-NEW-01',
                'marca' => 'Apple',
                'modelo' => 'iPhone 15',
                'imei' => '356789101234599',
                'procesador' => 'A16',
                'disco' => '128 GB',
                'ram' => '6 GB',
                'bateria' => '3500 mAh',
                'color' => 'Negro',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('celulares', [
            'id' => 'CEL-NEW-01',
            'marca' => 'Apple',
        ]);
    }

    public function test_can_list_and_create_entrega_celular(): void
    {
        $asignacion = AsigCelular::factory()->create();
        $entrega = EntregaCelular::factory()->create([
            'asignacion_cel_id' => $asignacion->id,
            'numero' => '010-2026',
        ]);

        Livewire::test(ListEntregaCelulars::class)
            ->assertCanSeeTableRecords([$entrega]);

        $otraAsignacion = AsigCelular::factory()->create();

        Livewire::test(CreateEntregaCelular::class)
            ->fillForm([
                'asignacion_cel_id' => $otraAsignacion->id,
                'numero' => '011-2026',
                'fecha' => now()->toDateString(),
                'observaciones' => 'OK',
                'nota' => null,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('entrega_celulares', [
            'asignacion_cel_id' => $otraAsignacion->id,
            'numero' => '011-2026',
        ]);
    }
}
