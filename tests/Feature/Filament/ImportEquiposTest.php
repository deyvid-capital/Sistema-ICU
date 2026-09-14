<?php

namespace Tests\Feature\Filament;

use App\Enums\EstadoEquipo;
use App\Filament\Imports\EquipoImporter;
use App\Filament\Resources\Equipos\Pages\ListEquipos;
use App\Models\Equipo;
use App\Models\MemoriaRam;
use App\Models\Microprocesador;
use App\Models\ModeloLaptop;
use App\Models\TarjetaVideo;
use App\Models\User;
use Filament\Actions\ImportAction;
use Filament\Actions\Imports\Jobs\ImportCsv;
use Filament\Actions\Imports\Models\FailedImportRow;
use Filament\Actions\Imports\Models\Import;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class ImportEquiposTest extends TestCase
{
    use RefreshDatabase;

    private User $importerUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importerUser = User::factory()->create();
        $this->actingAs($this->importerUser);
    }

    public function test_equipo_import_creates_with_catalog_relations_and_skips_booleans(): void
    {
        $this->seedCatalog();

        $import = $this->runImport(EquipoImporter::class, [
            [
                'service_tag' => 'ABC12345',
                'modelo' => 'Latitude 5540',
                'microprocesador' => 'Intel Core i7-1365U',
                'ram' => '16GB DDR4',
                'gpu' => 'NVIDIA RTX 4050',
            ],
        ], $this->requiredColumnMap());

        $this->assertSame(1, $import->successful_rows);
        $this->assertSame(0, $import->getFailedRowsCount());

        $equipo = Equipo::query()->find('ABC12345');
        $this->assertNotNull($equipo);
        $this->assertSame('Latitude 5540', $equipo->modelo->nombre);
        $this->assertSame('Intel Core i7-1365U', $equipo->microprocesador->nombre_micro);
        $this->assertSame('16GB DDR4', $equipo->ram->tipo_ram);
        $this->assertSame('NVIDIA RTX 4050', $equipo->gpu->nombre_gpu);
        $this->assertSame(EstadoEquipo::SinAsignar, $equipo->estado);
        $this->assertTrue($equipo->bloqueo_bios);
        $this->assertFalse($equipo->etiquetado);
        $this->assertNull($equipo->usuario_admin);
        $this->assertFalse($equipo->litigation_hold);
        $this->assertFalse($equipo->bitlocker);
        $this->assertNull($equipo->observaciones);
    }

    public function test_equipo_import_updates_by_service_tag_and_keeps_optional_observaciones(): void
    {
        $this->seedCatalog();

        Equipo::factory()->create([
            'service_tag' => 'ABC12345',
            'nombre_equipo' => 'BOCS-OLD',
            'observaciones' => '<p>Nota original</p>',
            'modelo_id' => ModeloLaptop::query()->where('nombre', 'Latitude 5540')->value('id'),
            'microprocesador_id' => Microprocesador::query()->where('nombre_micro', 'Intel Core i7-1365U')->value('id'),
            'ram_id' => MemoriaRam::query()->where('tipo_ram', '16GB DDR4')->value('id'),
            'gpu_id' => TarjetaVideo::query()->where('nombre_gpu', 'NVIDIA RTX 4050')->value('id'),
        ]);

        $import = $this->runImport(EquipoImporter::class, [
            [
                'service_tag' => 'ABC12345',
                'nombre_equipo' => 'BOCS-NEW',
                'modelo' => 'Latitude 5540',
                'microprocesador' => 'Intel Core i7-1365U',
                'ram' => '16GB DDR4',
                'gpu' => 'NVIDIA RTX 4050',
            ],
        ], [
            ...$this->requiredColumnMap(),
            'nombre_equipo' => 'nombre_equipo',
        ]);

        $this->assertSame(1, $import->successful_rows);

        $equipo = Equipo::query()->find('ABC12345');
        $this->assertSame('BOCS-NEW', $equipo?->nombre_equipo);
        $this->assertSame('<p>Nota original</p>', $equipo?->observaciones);
        $this->assertSame(1, Equipo::query()->where('service_tag', 'ABC12345')->count());
    }

    public function test_equipo_import_fails_when_modelo_does_not_exist(): void
    {
        $this->seedCatalog();

        $import = $this->runImport(EquipoImporter::class, [
            [
                'service_tag' => 'XYZ99999',
                'modelo' => 'Inexistente',
                'microprocesador' => 'Intel Core i7-1365U',
                'ram' => '16GB DDR4',
                'gpu' => 'NVIDIA RTX 4050',
            ],
        ], $this->requiredColumnMap());

        $this->assertSame(0, $import->successful_rows);
        $this->assertSame(1, $import->getFailedRowsCount());
        $this->assertDatabaseMissing(Equipo::class, ['service_tag' => 'XYZ99999']);
        $this->assertTrue(
            FailedImportRow::query()->where('import_id', $import->id)->exists(),
        );
    }

    public function test_equipo_import_fails_when_codigo_activo_is_duplicated(): void
    {
        $this->seedCatalog();

        Equipo::factory()->create([
            'service_tag' => 'AAA11111',
            'codigo_activo' => 'ACT-0001',
            'modelo_id' => ModeloLaptop::query()->where('nombre', 'Latitude 5540')->value('id'),
            'microprocesador_id' => Microprocesador::query()->where('nombre_micro', 'Intel Core i7-1365U')->value('id'),
            'ram_id' => MemoriaRam::query()->where('tipo_ram', '16GB DDR4')->value('id'),
            'gpu_id' => TarjetaVideo::query()->where('nombre_gpu', 'NVIDIA RTX 4050')->value('id'),
        ]);

        $import = $this->runImport(EquipoImporter::class, [
            [
                'service_tag' => 'BBB22222',
                'codigo_activo' => 'ACT-0001',
                'modelo' => 'Latitude 5540',
                'microprocesador' => 'Intel Core i7-1365U',
                'ram' => '16GB DDR4',
                'gpu' => 'NVIDIA RTX 4050',
            ],
        ], [
            ...$this->requiredColumnMap(),
            'codigo_activo' => 'codigo_activo',
        ]);

        $this->assertSame(0, $import->successful_rows);
        $this->assertSame(1, $import->getFailedRowsCount());
        $this->assertDatabaseMissing(Equipo::class, ['service_tag' => 'BBB22222']);
        $this->assertStringContainsString(
            'código de activo',
            (string) FailedImportRow::query()->where('import_id', $import->id)->value('validation_error'),
        );
    }

    public function test_listado_de_equipos_muestra_accion_importar(): void
    {
        Gate::before(fn () => true);

        Livewire::test(ListEquipos::class)
            ->assertOk()
            ->assertActionExists(TestAction::make(ImportAction::class)->table());
    }

    /**
     * @return array<string, string>
     */
    private function requiredColumnMap(): array
    {
        return [
            'service_tag' => 'service_tag',
            'modelo' => 'modelo',
            'microprocesador' => 'microprocesador',
            'ram' => 'ram',
            'gpu' => 'gpu',
        ];
    }

    private function seedCatalog(): void
    {
        ModeloLaptop::factory()->create(['nombre' => 'Latitude 5540']);
        Microprocesador::factory()->create(['nombre_micro' => 'Intel Core i7-1365U']);
        MemoriaRam::factory()->create(['tipo_ram' => '16GB DDR4']);
        TarjetaVideo::factory()->create(['nombre_gpu' => 'NVIDIA RTX 4050']);
    }

    /**
     * @param  class-string  $importer
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, string>  $columnMap
     */
    private function runImport(string $importer, array $rows, array $columnMap): Import
    {
        $import = Import::query()->create([
            'file_name' => 'test.csv',
            'file_path' => 'imports/test.csv',
            'importer' => $importer,
            'processed_rows' => 0,
            'total_rows' => count($rows),
            'successful_rows' => 0,
            'user_id' => $this->importerUser->id,
        ]);

        (new ImportCsv($import, $rows, $columnMap))->handle();

        $this->actingAs($this->importerUser);

        return $import->refresh();
    }
}
