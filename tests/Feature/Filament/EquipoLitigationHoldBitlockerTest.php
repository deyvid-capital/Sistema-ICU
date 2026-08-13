<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Equipos\Pages\CreateEquipo;
use App\Filament\Resources\Equipos\Pages\ListEquipos;
use App\Filament\Resources\Equipos\Pages\ViewEquipo;
use App\Models\Equipo;
use App\Models\MemoriaRam;
use App\Models\Microprocesador;
use App\Models\ModeloLaptop;
use App\Models\TarjetaVideo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class EquipoLitigationHoldBitlockerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(fn () => true);

        $this->actingAs(User::factory()->create());
    }

    public function test_new_equipo_defaults_litigation_hold_and_bitlocker_to_false(): void
    {
        $equipo = Equipo::factory()->create();

        $this->assertFalse($equipo->fresh()->litigation_hold);
        $this->assertFalse($equipo->fresh()->bitlocker);
    }

    public function test_can_create_equipo_with_litigation_hold_and_bitlocker(): void
    {
        $modelo = ModeloLaptop::factory()->create();
        $microprocesador = Microprocesador::factory()->create();
        $ram = MemoriaRam::factory()->create();
        $gpu = TarjetaVideo::factory()->create();

        Livewire::test(CreateEquipo::class)
            ->fillForm([
                'service_tag' => 'ST-LH-001',
                'nombre_equipo' => 'BOCS-LH',
                'modelo_id' => $modelo->id,
                'microprocesador_id' => $microprocesador->id,
                'ram_id' => $ram->id,
                'gpu_id' => $gpu->id,
                'so' => 'Windows 11 PRO',
                'estado' => 'sin asignar',
                'bloqueo_bios' => true,
                'etiquetado' => false,
                'litigation_hold' => true,
                'bitlocker' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('equipos', [
            'service_tag' => 'ST-LH-001',
            'litigation_hold' => true,
            'bitlocker' => true,
        ]);
    }

    public function test_list_and_view_show_boolean_icon_columns_for_new_flags(): void
    {
        $equipo = Equipo::factory()->create([
            'litigation_hold' => true,
            'bitlocker' => false,
        ]);

        Livewire::test(ListEquipos::class)
            ->assertCanSeeTableRecords([$equipo])
            ->assertSuccessful();

        Livewire::test(ViewEquipo::class, ['record' => $equipo->getKey()])
            ->assertSuccessful();
    }
}
