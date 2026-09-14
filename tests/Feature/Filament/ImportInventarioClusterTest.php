<?php

namespace Tests\Feature\Filament;

use App\Filament\Imports\MemoriaRamImporter;
use App\Filament\Imports\MicroprocesadorImporter;
use App\Filament\Imports\ModeloLaptopImporter;
use App\Filament\Imports\TarjetaVideoImporter;
use App\Filament\Resources\MemoriaRams\Pages\ManageMemoriaRams;
use App\Filament\Resources\Microprocesadors\Pages\ManageMicroprocesadors;
use App\Filament\Resources\ModeloLaptops\Pages\ManageModeloLaptops;
use App\Filament\Resources\TarjetaVideos\Pages\ManageTarjetaVideos;
use App\Models\MemoriaRam;
use App\Models\Microprocesador;
use App\Models\ModeloLaptop;
use App\Models\TarjetaVideo;
use App\Models\User;
use Filament\Actions\ImportAction;
use Filament\Actions\Imports\Jobs\ImportCsv;
use Filament\Actions\Imports\Models\Import;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class ImportInventarioClusterTest extends TestCase
{
    use RefreshDatabase;

    private User $importerUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importerUser = User::factory()->create();
        $this->actingAs($this->importerUser);
    }

    public function test_modelo_laptop_import_creates_and_skips_duplicates_by_name(): void
    {
        ModeloLaptop::factory()->create(['nombre' => 'Latitude 5540']);

        $import = $this->runImport(ModeloLaptopImporter::class, [
            ['nombre' => 'Latitude 5540'],
            ['nombre' => 'ThinkPad T14'],
        ], [
            'nombre' => 'nombre',
        ]);

        $this->assertSame(2, $import->successful_rows);
        $this->assertSame(0, $import->getFailedRowsCount());
        $this->assertDatabaseHas(ModeloLaptop::class, ['nombre' => 'Latitude 5540']);
        $this->assertDatabaseHas(ModeloLaptop::class, ['nombre' => 'ThinkPad T14']);
        $this->assertSame(1, ModeloLaptop::query()->where('nombre', 'Latitude 5540')->count());
    }

    public function test_microprocesador_import_creates_and_skips_duplicates_by_name(): void
    {
        Microprocesador::factory()->create(['nombre_micro' => 'Intel Core i7-1365U']);

        $import = $this->runImport(MicroprocesadorImporter::class, [
            ['nombre_micro' => 'Intel Core i7-1365U'],
            ['nombre_micro' => 'AMD Ryzen 7 7730U'],
        ], [
            'nombre_micro' => 'nombre_micro',
        ]);

        $this->assertSame(2, $import->successful_rows);
        $this->assertSame(0, $import->getFailedRowsCount());
        $this->assertDatabaseHas(Microprocesador::class, ['nombre_micro' => 'AMD Ryzen 7 7730U']);
        $this->assertSame(1, Microprocesador::query()->where('nombre_micro', 'Intel Core i7-1365U')->count());
    }

    public function test_memoria_ram_import_creates_and_skips_duplicates_by_tipo(): void
    {
        MemoriaRam::factory()->create(['tipo_ram' => '16GB DDR4']);

        $import = $this->runImport(MemoriaRamImporter::class, [
            ['tipo_ram' => '16GB DDR4'],
            ['tipo_ram' => '32GB DDR5'],
        ], [
            'tipo_ram' => 'tipo_ram',
        ]);

        $this->assertSame(2, $import->successful_rows);
        $this->assertSame(0, $import->getFailedRowsCount());
        $this->assertDatabaseHas(MemoriaRam::class, ['tipo_ram' => '32GB DDR5']);
        $this->assertSame(1, MemoriaRam::query()->where('tipo_ram', '16GB DDR4')->count());
    }

    public function test_tarjeta_video_import_creates_and_skips_duplicates_by_name(): void
    {
        TarjetaVideo::factory()->create(['nombre_gpu' => 'NVIDIA RTX 4050']);

        $import = $this->runImport(TarjetaVideoImporter::class, [
            ['nombre_gpu' => 'NVIDIA RTX 4050'],
            ['nombre_gpu' => 'Intel Iris Xe'],
        ], [
            'nombre_gpu' => 'nombre_gpu',
        ]);

        $this->assertSame(2, $import->successful_rows);
        $this->assertSame(0, $import->getFailedRowsCount());
        $this->assertDatabaseHas(TarjetaVideo::class, ['nombre_gpu' => 'Intel Iris Xe']);
        $this->assertSame(1, TarjetaVideo::query()->where('nombre_gpu', 'NVIDIA RTX 4050')->count());
    }

    public function test_modelo_laptop_import_fails_when_nombre_is_too_short(): void
    {
        $import = $this->runImport(ModeloLaptopImporter::class, [
            ['nombre' => 'A'],
        ], [
            'nombre' => 'nombre',
        ]);

        $this->assertSame(0, $import->successful_rows);
        $this->assertSame(1, $import->getFailedRowsCount());
        $this->assertDatabaseMissing(ModeloLaptop::class, ['nombre' => 'A']);
    }

    public function test_listados_del_cluster_inventario_muestran_accion_importar(): void
    {
        Gate::before(fn () => true);

        $action = TestAction::make(ImportAction::class)->table();

        Livewire::test(ManageModeloLaptops::class)
            ->assertOk()
            ->assertActionExists($action);

        Livewire::test(ManageMicroprocesadors::class)
            ->assertOk()
            ->assertActionExists($action);

        Livewire::test(ManageMemoriaRams::class)
            ->assertOk()
            ->assertActionExists($action);

        Livewire::test(ManageTarjetaVideos::class)
            ->assertOk()
            ->assertActionExists($action);
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
