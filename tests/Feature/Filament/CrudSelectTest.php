<?php

namespace Tests\Feature\Filament;

use App\Models\CategoriaSoftware;
use App\Models\Software;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\Feature\Filament\Fixtures\CrudSelectFormFixture;
use Tests\TestCase;

class CrudSelectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(fn (): bool => true);
        CrudSelectFormFixture::$permitirEliminar = true;

        $this->actingAs(User::factory()->create());
    }

    public function test_renderiza_con_las_clases_del_select_de_filament_y_sus_opciones(): void
    {
        CategoriaSoftware::factory()->create(['nombre' => 'Navegadores']);

        Livewire::test(CrudSelectFormFixture::class)
            ->assertSeeHtml('fi-select-input')
            ->assertSeeHtml('fi-crud-select')
            ->assertSee('Navegadores')
            ->assertSee('crudSelectFormComponent')
            ->assertSeeHtml('fi-crud-select-option-icon');
    }

    public function test_guarda_seleccion_unica_y_multiple(): void
    {
        [$a, $b] = CategoriaSoftware::factory()->count(2)->create();

        Livewire::test(CrudSelectFormFixture::class)
            ->fillForm([
                'categoria' => (string) $a->getKey(),
                'categorias' => [(string) $a->getKey(), (string) $b->getKey()],
                'prioridad' => 'alta',
            ])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    public function test_rechaza_valores_que_no_estan_entre_las_opciones(): void
    {
        Livewire::test(CrudSelectFormFixture::class)
            ->fillForm([
                'categoria' => '999',
                'categorias' => ['999'],
                'prioridad' => 'urgente',
            ])
            ->call('save')
            ->assertHasFormErrors(['categoria', 'categorias', 'prioridad']);
    }

    public function test_respeta_required(): void
    {
        Livewire::test(CrudSelectFormFixture::class)
            ->fillForm(['prioridad' => null])
            ->call('save')
            ->assertHasFormErrors(['prioridad' => 'required']);
    }

    public function test_crear_guarda_el_registro_y_lo_deja_seleccionado(): void
    {
        $existente = CategoriaSoftware::factory()->create();

        Livewire::test(CrudSelectFormFixture::class)
            ->callAction(TestAction::make('createOption')->schemaComponent('categoria'), data: [
                'nombre' => 'Diseño',
                'codigo' => 'diseno',
            ])
            ->assertHasNoFormErrors()
            ->assertNotified('Opción creada')
            ->assertSchemaStateSet(['categoria' => (string) CategoriaSoftware::query()->where('codigo', 'diseno')->value('id_categoria')])
            ->fillForm(['categorias' => [(string) $existente->getKey()]])
            ->callAction(TestAction::make('createOption')->schemaComponent('categorias'), data: [
                'nombre' => 'Seguridad',
                'codigo' => 'seguridad',
            ])
            ->assertSchemaStateSet(['categorias' => [
                (string) $existente->getKey(),
                (string) CategoriaSoftware::query()->where('codigo', 'seguridad')->value('id_categoria'),
            ]]);
    }

    public function test_crear_valida_su_formulario(): void
    {
        Livewire::test(CrudSelectFormFixture::class)
            ->callAction(TestAction::make('createOption')->schemaComponent('categoria'), data: [
                'nombre' => null,
                'codigo' => 'x',
            ])
            ->assertHasFormErrors(['nombre' => 'required']);

        $this->assertDatabaseMissing(CategoriaSoftware::class, ['codigo' => 'x']);
    }

    public function test_editar_actualiza_el_registro(): void
    {
        $categoria = CategoriaSoftware::factory()->create(['nombre' => 'Viejo', 'codigo' => 'viejo']);

        Livewire::test(CrudSelectFormFixture::class)
            ->fillForm(['categoria' => (string) $categoria->getKey()])
            ->mountAction(TestAction::make('editOption')->schemaComponent('categoria')->arguments(['value' => (string) $categoria->getKey()]))
            ->assertActionDataSet(['nombre' => 'Viejo', 'codigo' => 'viejo'])
            ->setActionData(['nombre' => 'Nuevo'])
            ->callMountedAction()
            ->assertNotified('Opción actualizada')
            ->assertSchemaStateSet(['categoria' => (string) $categoria->getKey()]);

        $this->assertSame('Nuevo', $categoria->fresh()->nombre);
    }

    public function test_eliminar_pide_confirmacion_borra_y_quita_de_la_seleccion(): void
    {
        [$a, $b] = CategoriaSoftware::factory()->count(2)->create();

        Livewire::test(CrudSelectFormFixture::class)
            ->fillForm([
                'categoria' => (string) $a->getKey(),
                'categorias' => [(string) $a->getKey(), (string) $b->getKey()],
            ])
            ->mountAction(TestAction::make('deleteOption')->schemaComponent('categorias')->arguments(['value' => (string) $a->getKey()]))
            ->assertActionMounted(TestAction::make('deleteOption')->schemaComponent('categorias'))
            ->callMountedAction()
            ->assertNotified('Opción eliminada')
            ->assertSchemaStateSet(['categorias' => [(string) $b->getKey()]]);

        $this->assertModelMissing($a);
    }

    public function test_no_elimina_una_opcion_en_uso(): void
    {
        $categoria = CategoriaSoftware::factory()->create();
        Software::factory()->for($categoria, 'categoria')->create();

        Livewire::test(CrudSelectFormFixture::class)
            ->fillForm(['categoria' => (string) $categoria->getKey()])
            ->callAction(TestAction::make('deleteOption')->schemaComponent('categoria')->arguments(['value' => (string) $categoria->getKey()]))
            ->assertSchemaStateSet(['categoria' => (string) $categoria->getKey()]);

        $this->assertModelExists($categoria);
    }

    public function test_sin_permiso_no_se_puede_eliminar_aunque_se_llame_la_accion(): void
    {
        CrudSelectFormFixture::$permitirEliminar = false;
        $categoria = CategoriaSoftware::factory()->create();

        Livewire::test(CrudSelectFormFixture::class)
            ->callAction(TestAction::make('deleteOption')->schemaComponent('categoria')->arguments(['value' => (string) $categoria->getKey()]))
            ->assertStatus(403);

        $this->assertModelExists($categoria);
    }

    public function test_no_acepta_valores_inventados_en_las_acciones(): void
    {
        Livewire::test(CrudSelectFormFixture::class)
            ->callAction(TestAction::make('deleteOption')->schemaComponent('categoria')->arguments(['value' => '999']))
            ->assertStatus(404);
    }

    public function test_la_policy_del_modelo_controla_crear(): void
    {
        Gate::before(fn (): ?bool => null);

        $html = Livewire::test(CrudSelectFormFixture::class)->html();

        $this->assertStringContainsString('canCreate: false', $html);
    }
}
