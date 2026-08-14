<?php

namespace Tests\Feature\Filament;

use App\Filament\Imports\AreaImporter;
use App\Filament\Imports\CargoImporter;
use App\Filament\Imports\MovimientoImporter;
use App\Filament\Imports\UserImporter;
use App\Models\Area;
use App\Models\Cargo;
use App\Models\Movimiento;
use App\Models\User;
use Filament\Actions\Imports\Jobs\ImportCsv;
use Filament\Actions\Imports\Models\FailedImportRow;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ImportUsuariosClusterTest extends TestCase
{
    use RefreshDatabase;

    private User $importerUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importerUser = User::factory()->create();
        $this->actingAs($this->importerUser);
    }

    public function test_area_import_creates_and_updates_by_name(): void
    {
        Area::factory()->create([
            'area' => 'Tecnología',
            'descripcion' => 'Antigua',
        ]);

        $import = $this->runImport(AreaImporter::class, [
            ['area' => 'Tecnología', 'descripcion' => 'Sistemas'],
            ['area' => 'Operaciones', 'descripcion' => 'Nueva área'],
        ], [
            'area' => 'area',
            'descripcion' => 'descripcion',
        ]);

        $this->assertSame(2, $import->successful_rows);
        $this->assertSame(0, $import->getFailedRowsCount());
        $this->assertDatabaseHas(Area::class, [
            'area' => 'Tecnología',
            'descripcion' => 'Sistemas',
        ]);
        $this->assertDatabaseHas(Area::class, [
            'area' => 'Operaciones',
            'descripcion' => 'Nueva área',
        ]);
        $this->assertSame(1, Area::query()->where('area', 'Tecnología')->count());
    }

    public function test_cargo_import_fails_when_area_does_not_exist(): void
    {
        $import = $this->runImport(CargoImporter::class, [
            ['area' => 'Inexistente', 'cargo' => 'Analista', 'descripcion' => 'N/A'],
        ], [
            'area' => 'area',
            'cargo' => 'cargo',
            'descripcion' => 'descripcion',
        ]);

        $this->assertSame(0, $import->successful_rows);
        $this->assertSame(1, $import->getFailedRowsCount());
        $this->assertDatabaseMissing(Cargo::class, ['cargo' => 'Analista']);
        $this->assertTrue(
            FailedImportRow::query()->where('import_id', $import->id)->exists(),
        );
    }

    public function test_cargo_import_creates_when_area_and_name_match(): void
    {
        Area::factory()->create(['area' => 'Tecnología']);

        $import = $this->runImport(CargoImporter::class, [
            ['area' => 'Tecnología', 'cargo' => 'Analista', 'descripcion' => 'Sistemas'],
        ], [
            'area' => 'area',
            'cargo' => 'cargo',
            'descripcion' => 'descripcion',
        ]);

        $this->assertSame(1, $import->successful_rows);
        $this->assertDatabaseHas(Cargo::class, [
            'cargo' => 'Analista',
            'descripcion' => 'Sistemas',
        ]);
    }

    public function test_user_import_creates_and_updates_by_email_and_concatenates_name(): void
    {
        $area = Area::factory()->create(['area' => 'Tecnología']);
        $cargo = Cargo::factory()->create([
            'area_id' => $area->id_area,
            'cargo' => 'Analista',
        ]);

        $createImport = $this->runImport(UserImporter::class, [
            [
                'nombres' => 'Juan',
                'apellidos' => 'Pérez',
                'email' => 'jperez@empresa.com',
                'carnet' => '1234 LP',
                'telefono' => '7123-4567',
                'area' => 'Tecnología',
                'cargo' => 'Analista',
                'password' => '',
            ],
        ], $this->userColumnMap());

        $this->assertSame(1, $createImport->successful_rows);

        $user = User::query()->where('email', 'jperez@empresa.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('Juan Pérez', $user->name);
        $this->assertSame('Juan', $user->nombres);
        $this->assertSame('Pérez', $user->apellidos);
        $this->assertSame($area->id_area, $user->id_area);
        $this->assertSame($cargo->id_cargo, $user->id_cargo);
        $this->assertNull($user->getAttributes()['password'] ?? null);

        $user->update(['password' => 'password123']);

        $updateImport = $this->runImport(UserImporter::class, [
            [
                'nombres' => 'Juan Carlos',
                'apellidos' => 'Pérez',
                'email' => 'jperez@empresa.com',
                'carnet' => '9999 LP',
                'telefono' => '7000-0000',
                'area' => 'Tecnología',
                'cargo' => 'Analista',
                'password' => '',
            ],
        ], $this->userColumnMap());

        $this->assertSame(1, $updateImport->successful_rows);

        $user->refresh();
        $this->assertSame('Juan Carlos Pérez', $user->name);
        $this->assertSame('9999 LP', $user->carnet);
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertSame(1, User::query()->where('email', 'jperez@empresa.com')->count());
    }

    public function test_user_import_fails_when_cargo_does_not_belong_to_area(): void
    {
        $area = Area::factory()->create(['area' => 'Tecnología']);
        $otherArea = Area::factory()->create(['area' => 'Operaciones']);
        Cargo::factory()->create([
            'area_id' => $otherArea->id_area,
            'cargo' => 'Gerente',
        ]);
        Cargo::factory()->create([
            'area_id' => $area->id_area,
            'cargo' => 'Analista',
        ]);

        $import = $this->runImport(UserImporter::class, [
            [
                'nombres' => 'Ana',
                'apellidos' => 'Lopez',
                'email' => 'alopez@empresa.com',
                'area' => 'Tecnología',
                'cargo' => 'Gerente',
                'password' => 'password123',
            ],
        ], $this->userColumnMap());

        $this->assertSame(0, $import->successful_rows);
        $this->assertSame(1, $import->getFailedRowsCount());
        $this->assertDatabaseMissing(User::class, [
            'email' => 'alopez@empresa.com',
        ]);
    }

    public function test_movimiento_import_creates_alta_and_rejects_second_alta_without_baja(): void
    {
        $area = Area::factory()->create(['area' => 'Tecnología']);
        $cargo = Cargo::factory()->create([
            'area_id' => $area->id_area,
            'cargo' => 'Analista',
        ]);
        $usuario = User::factory()->create([
            'email' => 'jperez@empresa.com',
            'id_area' => $area->id_area,
            'id_cargo' => $cargo->id_cargo,
        ]);

        $import = $this->runImport(MovimientoImporter::class, [
            [
                'usuario' => 'jperez@empresa.com',
                'tipo' => 'Alta',
                'fecha' => '2026-01-15',
                'cargo' => 'Analista',
            ],
            [
                'usuario' => 'jperez@empresa.com',
                'tipo' => 'Alta',
                'fecha' => '2026-02-01',
                'cargo' => 'Analista',
            ],
        ], [
            'usuario' => 'usuario',
            'tipo' => 'tipo',
            'fecha' => 'fecha',
            'cargo' => 'cargo',
        ]);

        $this->assertSame(1, $import->successful_rows);
        $this->assertSame(1, $import->getFailedRowsCount());
        $this->assertSame(1, Movimiento::query()->where('usuario_id', $usuario->id)->count());
        $this->assertDatabaseHas(Movimiento::class, [
            'usuario_id' => $usuario->id,
            'tipo' => 'Alta',
            'cargo_id' => $cargo->id_cargo,
        ]);
        $this->assertStringContainsString(
            'ya está de Alta',
            (string) FailedImportRow::query()->where('import_id', $import->id)->value('validation_error'),
        );
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

    /**
     * @return array<string, string>
     */
    private function userColumnMap(): array
    {
        return [
            'nombres' => 'nombres',
            'apellidos' => 'apellidos',
            'email' => 'email',
            'carnet' => 'carnet',
            'telefono' => 'telefono',
            'area' => 'area',
            'cargo' => 'cargo',
            'password' => 'password',
        ];
    }
}
