<?php

namespace Tests\Feature\Pdf;

use App\Enums\EquipoFormularioPdfTipo;
use App\Models\AsignacionEquipo;
use App\Models\Cargo;
use App\Models\EntregaEquipo;
use App\Models\Equipo;
use App\Models\NotaRecepcionEquipo;
use App\Models\NotaSalidaEquipo;
use App\Models\PdfEquipo;
use App\Models\User;
use App\Models\UsuarioExterno;
use App\Services\Pdf\BrowsershotPdfFactory;
use App\Services\Pdf\EquipoFormularioPdfData;
use App\Services\Pdf\GenerateEquipoFormularioPdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EquipoFormularioPdfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->mock(BrowsershotPdfFactory::class, function ($mock): void {
            $mock->shouldReceive('fromView')
                ->andReturn('%PDF-1.4 mock-content');
        });
    }

    public function test_it_generates_and_stores_oficial_pdf_for_entrega(): void
    {
        $asignacion = AsignacionEquipo::factory()->create([
            'service_tag' => Equipo::factory(),
            'usuario_id' => User::factory(),
            'fecha_fin' => null,
        ]);

        $entrega = EntregaEquipo::factory()->create([
            'asignacion_id' => $asignacion->id,
            'numero' => '001-2024',
            'fecha' => '2024-04-18',
        ]);

        $pdf = app(GenerateEquipoFormularioPdf::class)->execute($entrega, EquipoFormularioPdfTipo::Entrega);

        $this->assertInstanceOf(PdfEquipo::class, $pdf);
        $this->assertSame(EquipoFormularioPdfTipo::Entrega->nombreOficial(), $pdf->nombre);
        $this->assertSame('pdf-equipos/entrega/FUE-001-2024.pdf', $pdf->url);
        $this->assertTrue($entrega->fresh()->hasDocumentoOficial());
        Storage::disk('public')->assertExists($pdf->url);
    }

    public function test_it_replaces_existing_oficial_pdf_on_regenerate_when_numero_changes(): void
    {
        $asignacion = AsignacionEquipo::factory()->create([
            'service_tag' => Equipo::factory(),
            'usuario_id' => User::factory(),
            'fecha_fin' => null,
        ]);

        $entrega = EntregaEquipo::factory()->create([
            'asignacion_id' => $asignacion->id,
            'numero' => '002-2024',
            'fecha' => '2024-04-18',
        ]);

        $first = app(GenerateEquipoFormularioPdf::class)->execute($entrega, EquipoFormularioPdfTipo::Entrega);
        $oldPath = $first->url;

        $entrega->update(['numero' => '003-2024']);

        $second = app(GenerateEquipoFormularioPdf::class)->execute($entrega->fresh(), EquipoFormularioPdfTipo::Entrega);

        $this->assertSame($first->id, $second->id);
        $this->assertSame('pdf-equipos/entrega/FUE-003-2024.pdf', $second->url);
        $this->assertNotSame($oldPath, $second->url);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($second->url);
        $this->assertSame(1, $entrega->pdfs()->where('nombre', EquipoFormularioPdfTipo::Entrega->nombreOficial())->count());
    }

    public function test_it_overwrites_same_path_when_regenerating_without_numero_change(): void
    {
        $asignacion = AsignacionEquipo::factory()->create([
            'service_tag' => Equipo::factory(),
            'usuario_id' => User::factory(),
            'fecha_fin' => null,
        ]);

        $entrega = EntregaEquipo::factory()->create([
            'asignacion_id' => $asignacion->id,
            'numero' => '010-2024',
            'fecha' => '2024-04-18',
        ]);

        $first = app(GenerateEquipoFormularioPdf::class)->execute($entrega, EquipoFormularioPdfTipo::Entrega);

        $entrega->update(['fecha' => '2024-05-01']);

        $second = app(GenerateEquipoFormularioPdf::class)->execute($entrega->fresh(), EquipoFormularioPdfTipo::Entrega);

        $this->assertSame($first->id, $second->id);
        $this->assertSame($first->url, $second->url);
        $this->assertSame('pdf-equipos/entrega/FUE-010-2024.pdf', $second->url);
        Storage::disk('public')->assertExists($second->url);
        $this->assertSame(1, $entrega->pdfs()->where('nombre', EquipoFormularioPdfTipo::Entrega->nombreOficial())->count());
    }

    public function test_it_generates_pdf_for_nota_salida(): void
    {
        $nota = NotaSalidaEquipo::factory()->create([
            'equipo_id' => Equipo::factory(),
            'usuario_externo_id' => UsuarioExterno::factory(),
            'numero' => '003-2024',
            'fecha' => '2024-06-01',
        ]);

        $pdf = app(GenerateEquipoFormularioPdf::class)->execute($nota, EquipoFormularioPdfTipo::NotaSalida);

        $this->assertTrue($nota->fresh()->hasDocumentoOficial());
        $this->assertSame('pdf-equipos/nota-salida/NotaSalida-003-2024.pdf', $pdf->url);
        Storage::disk('public')->assertExists($pdf->url);
    }

    public function test_data_mapper_builds_entrega_documento_payload(): void
    {
        $asignacion = AsignacionEquipo::factory()->create([
            'service_tag' => Equipo::factory(),
            'usuario_id' => User::factory(),
            'fecha_fin' => null,
        ]);

        $entrega = EntregaEquipo::factory()->create([
            'asignacion_id' => $asignacion->id,
            'numero' => '004-2024',
            'mouse' => true,
            'cooler' => false,
            'audifonos' => true,
        ]);

        $payload = app(EquipoFormularioPdfData::class)
            ->for($entrega, EquipoFormularioPdfTipo::Entrega);

        $this->assertSame('004-2024', $payload['documento']['numero']);
        $this->assertSame('Entrega', $payload['documento']['tipo']);
        $this->assertTrue($payload['documento']['mouse']['incluido']);
        $this->assertFalse($payload['documento']['cooler']['incluido']);
    }

    public function test_nota_recepcion_uses_selected_firmante_as_firma_derecha(): void
    {
        $cargo = Cargo::factory()->create(['cargo' => 'Jefe de Sistemas']);
        $firmante = User::factory()->create([
            'name' => 'María Gómez',
            'id_cargo' => $cargo->id_cargo,
        ]);

        $nota = NotaRecepcionEquipo::factory()->create([
            'equipo_id' => Equipo::factory(),
            'usuarios_externo_id' => UsuarioExterno::factory(),
        ]);

        $payload = app(EquipoFormularioPdfData::class)
            ->for($nota, EquipoFormularioPdfTipo::NotaRecepcion, (string) $firmante->id);

        $this->assertSame('María Gómez', $payload['documento']['firma_derecha']['nombre']);
        $this->assertSame('Jefe de Sistemas', $payload['documento']['firma_derecha']['cargo']);
    }

    public function test_nota_recepcion_without_firmante_keeps_default_firma_derecha(): void
    {
        $nota = NotaRecepcionEquipo::factory()->create([
            'equipo_id' => Equipo::factory(),
            'usuarios_externo_id' => UsuarioExterno::factory(),
        ]);

        $payload = app(EquipoFormularioPdfData::class)
            ->for($nota, EquipoFormularioPdfTipo::NotaRecepcion);

        $this->assertSame('—', $payload['documento']['firma_derecha']['nombre']);
    }
}
