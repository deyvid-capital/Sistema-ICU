<?php

namespace Tests\Feature\Filament;

use App\Filament\Exports\UserExporter;
use App\Models\Area;
use App\Models\Cargo;
use App\Models\Movimiento;
use App\Models\User;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserExporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_solo_exporta_usuarios_con_ultima_alta_vigente(): void
    {
        $area = Area::factory()->create(['area' => 'Tecnología']);
        $cargo = Cargo::factory()->create([
            'area_id' => $area->id_area,
            'cargo' => 'Analista',
        ]);

        $activo = User::factory()->create([
            'nombres' => 'Juan',
            'apellidos' => 'Activo',
            'name' => 'Juan Activo',
            'email' => 'juan.activo@capitalsafi.com',
            'telefono' => '+591 70000001',
            'id_area' => $area->id_area,
            'id_cargo' => $cargo->id_cargo,
        ]);

        Movimiento::factory()->alta()->create([
            'usuario_id' => $activo->id,
            'cargo_id' => $cargo->id_cargo,
            'fecha' => '2026-01-10',
        ]);

        $dadoDeBaja = User::factory()->create([
            'nombres' => 'Pedro',
            'apellidos' => 'Baja',
            'name' => 'Pedro Baja',
            'email' => 'pedro.baja@capitalsafi.com',
            'id_area' => $area->id_area,
            'id_cargo' => $cargo->id_cargo,
        ]);

        Movimiento::factory()->alta()->create([
            'usuario_id' => $dadoDeBaja->id,
            'cargo_id' => $cargo->id_cargo,
            'fecha' => '2026-01-05',
        ]);

        Movimiento::factory()->baja()->create([
            'usuario_id' => $dadoDeBaja->id,
            'cargo_id' => $cargo->id_cargo,
            'fecha' => '2026-02-01',
        ]);

        User::factory()->create([
            'nombres' => 'Sin',
            'apellidos' => 'Movimiento',
            'name' => 'Sin Movimiento',
            'email' => 'sin.movimiento@capitalsafi.com',
            'id_area' => $area->id_area,
            'id_cargo' => $cargo->id_cargo,
        ]);

        $ids = UserExporter::modifyQuery(User::query())->pluck('id');

        $this->assertCount(1, $ids);
        $this->assertTrue($ids->contains($activo->id));
        $this->assertFalse($ids->contains($dadoDeBaja->id));
    }

    public function test_columnas_y_formato_xlsx(): void
    {
        $columnas = collect(UserExporter::getColumns())
            ->map(fn ($columna): ?string => $columna->getLabel())
            ->all();

        $this->assertSame([
            'Nombre completo',
            'Área',
            'Cargo',
            'Correo',
            'Celular',
        ], $columnas);

        $exporter = new UserExporter(new Export, [], []);

        $this->assertSame([ExportFormat::Xlsx], $exporter->getFormats());
    }
}
