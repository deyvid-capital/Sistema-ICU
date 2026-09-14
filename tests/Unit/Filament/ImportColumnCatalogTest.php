<?php

namespace Tests\Unit\Filament;

use App\Filament\Imports\AreaImporter;
use App\Filament\Imports\CargoImporter;
use App\Filament\Imports\EquipoImporter;
use App\Filament\Imports\ImportColumnCatalog;
use App\Filament\Imports\MemoriaRamImporter;
use App\Filament\Imports\MicroprocesadorImporter;
use App\Filament\Imports\ModeloLaptopImporter;
use App\Filament\Imports\MovimientoImporter;
use App\Filament\Imports\TarjetaVideoImporter;
use App\Filament\Imports\UserImporter;
use Tests\TestCase;

class ImportColumnCatalogTest extends TestCase
{
    public function test_user_importer_guide_reads_required_optional_and_dependencies_from_columns(): void
    {
        $guide = ImportColumnCatalog::for(UserImporter::class);

        $this->assertNotEmpty($guide['summary']);
        $this->assertSame(['nombres', 'apellidos', 'email', 'area', 'cargo'], array_column($guide['required'], 'name'));
        $this->assertSame(['carnet', 'telefono', 'password'], array_column($guide['optional'], 'name'));
        $this->assertSame(['area', 'cargo'], array_column($guide['dependencies'], 'name'));
        $this->assertNull(collect($guide['optional'])->firstWhere('name', 'password')['example']);
        $this->assertStringContainsString('pertenecer al área', collect($guide['required'])->firstWhere('name', 'cargo')['helperText']);
    }

    public function test_area_importer_guide_marks_descripcion_optional(): void
    {
        $guide = ImportColumnCatalog::for(AreaImporter::class);

        $this->assertSame(['area'], array_column($guide['required'], 'name'));
        $this->assertSame(['descripcion'], array_column($guide['optional'], 'name'));
        $this->assertSame([], $guide['dependencies']);
    }

    public function test_cargo_importer_guide_requires_existing_area_relationship(): void
    {
        $guide = ImportColumnCatalog::for(CargoImporter::class);

        $this->assertSame(['area', 'cargo'], array_column($guide['required'], 'name'));
        $this->assertSame(['area'], array_column($guide['dependencies'], 'name'));
    }

    public function test_movimiento_importer_guide_uses_email_example_header(): void
    {
        $guide = ImportColumnCatalog::for(MovimientoImporter::class);

        $usuario = collect($guide['required'])->firstWhere('name', 'usuario');

        $this->assertSame('email', $usuario['header']);
        $this->assertSame(['usuario', 'cargo'], array_column($guide['dependencies'], 'name'));
    }

    public function test_inventario_importers_guide_requires_the_unique_name_column(): void
    {
        $this->assertSame(['nombre'], array_column(ImportColumnCatalog::for(ModeloLaptopImporter::class)['required'], 'name'));
        $this->assertSame(['nombre_micro'], array_column(ImportColumnCatalog::for(MicroprocesadorImporter::class)['required'], 'name'));
        $this->assertSame(['tipo_ram'], array_column(ImportColumnCatalog::for(MemoriaRamImporter::class)['required'], 'name'));
        $this->assertSame(['nombre_gpu'], array_column(ImportColumnCatalog::for(TarjetaVideoImporter::class)['required'], 'name'));

        $modeloGuide = ImportColumnCatalog::for(ModeloLaptopImporter::class);
        $this->assertSame([], $modeloGuide['optional']);
        $this->assertSame([], $modeloGuide['dependencies']);
        $this->assertStringContainsString('no se duplica', $modeloGuide['required'][0]['helperText']);
    }

    public function test_equipo_importer_guide_requires_catalog_and_keeps_observaciones_optional(): void
    {
        $guide = ImportColumnCatalog::for(EquipoImporter::class);

        $this->assertSame(
            ['service_tag', 'modelo', 'microprocesador', 'ram', 'gpu'],
            array_column($guide['required'], 'name'),
        );
        $this->assertContains('observaciones', array_column($guide['optional'], 'name'));
        $this->assertSame(['modelo', 'microprocesador', 'ram', 'gpu'], array_column($guide['dependencies'], 'name'));
        $this->assertNotContains('bloqueo_bios', array_column($guide['columns'], 'name'));
        $this->assertNotContains('etiquetado', array_column($guide['columns'], 'name'));
        $this->assertNotContains('usuario_admin', array_column($guide['columns'], 'name'));
        $this->assertNotContains('litigation_hold', array_column($guide['columns'], 'name'));
        $this->assertNotContains('bitlocker', array_column($guide['columns'], 'name'));
        $this->assertStringContainsString('booleanos', (string) $guide['summary']);
    }
}
