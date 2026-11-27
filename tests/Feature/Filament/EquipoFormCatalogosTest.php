<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Equipos\Pages\CreateEquipo;
use App\Filament\Resources\Equipos\Pages\EditEquipo;
use App\Models\Equipo;
use App\Models\MemoriaRam;
use App\Models\Microprocesador;
use App\Models\ModeloLaptop;
use App\Models\TarjetaVideo;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class EquipoFormCatalogosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(fn (): bool => true);

        $this->actingAs(User::factory()->create());
    }

    public function test_el_modulo_de_inventario_ya_no_existe(): void
    {
        $this->assertFalse(class_exists('App\Filament\Clusters\Inventario\InventarioCluster'));
        $this->assertFalse(class_exists('App\Filament\Resources\ModeloLaptops\ModeloLaptopResource'));

        $this->get('/admin/inventario-componentes/modelo-laptops')->assertNotFound();
    }

    public function test_los_cuatro_catalogos_se_muestran_como_crud_select(): void
    {
        ModeloLaptop::factory()->create(['nombre' => 'Latitude 5540']);
        TarjetaVideo::factory()->create(['nombre_gpu' => 'NVIDIA RTX 4050']);

        Livewire::test(CreateEquipo::class)
            ->assertOk()
            ->assertSeeHtml('fi-crud-select')
            ->assertSee('Latitude 5540')
            ->assertSee('NVIDIA RTX 4050')
            ->assertSee('Elige un modelo de laptop')
            ->assertSee('Elige un microprocesador')
            ->assertSee('Elige una memoria RAM')
            ->assertSee('Elige una tarjeta de video');
    }

    public function test_crea_un_modelo_desde_el_formulario_y_queda_seleccionado(): void
    {
        Livewire::test(CreateEquipo::class)
            ->callAction(TestAction::make('createOption')->schemaComponent('modelo_id'), data: [
                'nombre' => '  Precision 3581  ',
            ])
            ->assertHasNoFormErrors()
            ->assertNotified('Opción creada')
            ->assertSchemaStateSet(['modelo_id' => (string) ModeloLaptop::query()->where('nombre', 'Precision 3581')->value('id')]);

        $this->assertDatabaseHas(ModeloLaptop::class, ['nombre' => 'Precision 3581']);
    }

    public function test_crear_valida_requerido_y_nombre_unico(): void
    {
        Microprocesador::factory()->create(['nombre_micro' => 'Intel Core i7-1365U']);

        Livewire::test(CreateEquipo::class)
            ->callAction(TestAction::make('createOption')->schemaComponent('microprocesador_id'), data: [
                'nombre_micro' => null,
            ])
            ->assertHasFormErrors(['nombre_micro' => 'required']);

        Livewire::test(CreateEquipo::class)
            ->callAction(TestAction::make('createOption')->schemaComponent('microprocesador_id'), data: [
                'nombre_micro' => 'Intel Core i7-1365U',
            ])
            ->assertHasFormErrors(['nombre_micro' => 'unique']);

        $this->assertSame(1, Microprocesador::query()->count());
    }

    public function test_edita_una_ram_y_permite_conservar_su_propio_nombre(): void
    {
        $ram = MemoriaRam::factory()->create(['tipo_ram' => '16GB DDR4']);

        Livewire::test(CreateEquipo::class)
            ->mountAction(TestAction::make('editOption')->schemaComponent('ram_id')->arguments(['value' => (string) $ram->getKey()]))
            ->assertActionDataSet(['tipo_ram' => '16GB DDR4'])
            ->callMountedAction()
            ->assertHasNoFormErrors()
            ->mountAction(TestAction::make('editOption')->schemaComponent('ram_id')->arguments(['value' => (string) $ram->getKey()]))
            ->setActionData(['tipo_ram' => '32GB DDR5'])
            ->callMountedAction()
            ->assertNotified('Opción actualizada');

        $this->assertSame('32GB DDR5', $ram->fresh()->tipo_ram);
    }

    public function test_elimina_una_gpu_sin_equipos(): void
    {
        $gpu = TarjetaVideo::factory()->create();

        Livewire::test(CreateEquipo::class)
            ->callAction(TestAction::make('deleteOption')->schemaComponent('gpu_id')->arguments(['value' => (string) $gpu->getKey()]))
            ->assertNotified('Opción eliminada');

        $this->assertModelMissing($gpu);
    }

    public function test_no_permite_eliminar_un_modelo_usado_por_un_equipo_aunque_este_eliminado(): void
    {
        $equipo = Equipo::factory()->create();
        $modelo = $equipo->modelo;
        $equipo->delete();

        Livewire::test(CreateEquipo::class)
            ->callAction(TestAction::make('deleteOption')->schemaComponent('modelo_id')->arguments(['value' => (string) $modelo->getKey()]))
            ->assertStatus(403);

        $this->assertModelExists($modelo);
    }

    public function test_editar_un_equipo_conserva_sus_componentes(): void
    {
        $equipo = Equipo::factory()->create();

        Livewire::test(EditEquipo::class, ['record' => $equipo->getKey()])
            ->assertSchemaStateSet([
                'modelo_id' => $equipo->modelo_id,
                'gpu_id' => $equipo->gpu_id,
            ])
            ->call('save')
            ->assertHasNoFormErrors();
    }
}
