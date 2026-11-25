<?php

namespace Tests\Feature\Filament;

use App\Enums\CategoriasSoftware;
use App\Filament\Resources\CategoriasSoftware\CategoriaSoftwareResource;
use App\Filament\Resources\CategoriasSoftware\Pages\CreateCategoriaSoftware;
use App\Filament\Resources\CategoriasSoftware\Pages\EditCategoriaSoftware;
use App\Filament\Resources\CategoriasSoftware\Pages\ListCategoriasSoftware;
use App\Filament\Resources\Software\Pages\CreateSoftware;
use App\Filament\Resources\Software\SoftwareResource;
use App\Models\CategoriaSoftware;
use App\Models\Software;
use App\Models\User;
use Database\Seeders\CategoriaSoftwareSeeder;
use Filament\Support\Exceptions\Halt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoriaSoftwareResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CategoriaSoftwareResource::skipAuthorization();
        SoftwareResource::skipAuthorization();

        $this->actingAs(User::factory()->conRol()->create());
    }

    public function test_el_seeder_carga_una_categoria_por_caso_del_enum_sin_duplicar(): void
    {
        $this->seed(CategoriaSoftwareSeeder::class);
        $this->seed(CategoriaSoftwareSeeder::class);

        $this->assertSame(count(CategoriasSoftware::cases()), CategoriaSoftware::query()->count());
        $this->assertDatabaseHas(CategoriaSoftware::class, ['codigo' => 'soporte_remoto', 'nombre' => 'Soporte remoto']);
    }

    public function test_lista_las_categorias(): void
    {
        $categorias = CategoriaSoftware::factory()->count(3)->create();

        Livewire::test(ListCategoriasSoftware::class)
            ->assertCanSeeTableRecords($categorias);
    }

    public function test_crea_una_categoria_con_codigo_a_partir_del_nombre(): void
    {
        Livewire::test(CreateCategoriaSoftware::class)
            ->fillForm(['nombre' => 'Diseño gráfico'])
            ->assertSchemaStateSet(['codigo' => 'diseno_grafico'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(CategoriaSoftware::class, ['nombre' => 'Diseño gráfico', 'codigo' => 'diseno_grafico']);
    }

    public function test_el_nombre_y_el_codigo_son_unicos(): void
    {
        CategoriaSoftware::factory()->create(['nombre' => 'Navegadores', 'codigo' => 'navegadores']);

        Livewire::test(CreateCategoriaSoftware::class)
            ->fillForm(['nombre' => 'Navegadores', 'codigo' => 'navegadores'])
            ->call('create')
            ->assertHasFormErrors(['nombre' => 'unique', 'codigo' => 'unique']);
    }

    public function test_el_codigo_no_cambia_al_editar(): void
    {
        $categoria = CategoriaSoftware::factory()->create(['nombre' => 'PDF', 'codigo' => 'pdf']);

        Livewire::test(EditCategoriaSoftware::class, ['record' => $categoria->getKey()])
            ->fillForm(['nombre' => 'Lectores PDF', 'codigo' => 'otro_codigo'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(CategoriaSoftware::class, ['id_categoria' => $categoria->getKey(), 'nombre' => 'Lectores PDF', 'codigo' => 'pdf']);
    }

    public function test_el_software_se_registra_con_su_categoria(): void
    {
        $categoria = CategoriaSoftware::deEnum(CategoriasSoftware::Navegadores);

        Livewire::test(CreateSoftware::class)
            ->fillForm([
                'nombre_software' => 'Chrome',
                'categoria_id' => $categoria->getKey(),
                'estado' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame($categoria->getKey(), Software::query()->where('nombre_software', 'Chrome')->value('categoria_id'));
    }

    public function test_no_se_puede_borrar_una_categoria_con_software(): void
    {
        $categoria = CategoriaSoftware::factory()->create();
        Software::factory()->for($categoria, 'categoria')->create();

        try {
            $categoria->delete();
            $this->fail('Se esperaba que el borrado se detuviera.');
        } catch (Halt) {
            $this->assertModelExists($categoria);
        }
    }
}
