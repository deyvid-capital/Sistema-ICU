<?php

namespace Tests\Feature\Pdf;

use App\Enums\EquipoFormularioPdfTipo;
use App\Models\AsignacionEquipo;
use App\Models\EntregaEquipo;
use App\Models\Equipo;
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
            'numero' => 'ENT-1001',
            'fecha' => '2024-04-18',
        ]);

        $pdf = app(GenerateEquipoFormularioPdf::class)->execute($entrega, EquipoFormularioPdfTipo::Entrega);

        $this->assertInstanceOf(PdfEquipo::class, $pdf);
        $this->assertSame(EquipoFormularioPdfTipo::Entrega->nombreOficial(), $pdf->nombre);
        $this->assertTrue($entrega->fresh()->hasDocumentoOficial());
        Storage::disk('public')->assertExists($pdf->url);
    }

    public function test_it_replaces_existing_oficial_pdf_on_regenerate(): void
    {
        $asignacion = AsignacionEquipo::factory()->create([
            'service_tag' => Equipo::factory(),
            'usuario_id' => User::factory(),
            'fecha_fin' => null,
        ]);

        $entrega = EntregaEquipo::factory()->create([
            'asignacion_id' => $asignacion->id,
            'numero' => 'ENT-2002',
            'fecha' => '2024-04-18',
        ]);

        $first = app(GenerateEquipoFormularioPdf::class)->execute($entrega, EquipoFormularioPdfTipo::Entrega);
        $oldPath = $first->url;

        $entrega->update(['fecha' => '2024-05-01']);

        $second = app(GenerateEquipoFormularioPdf::class)->execute($entrega->fresh(), EquipoFormularioPdfTipo::Entrega);

        $this->assertSame($first->id, $second->id);
        $this->assertNotSame($oldPath, $second->url);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($second->url);
        $this->assertSame(1, $entrega->pdfs()->where('nombre', EquipoFormularioPdfTipo::Entrega->nombreOficial())->count());
    }

    public function test_it_generates_pdf_for_nota_salida(): void
    {
        $nota = NotaSalidaEquipo::factory()->create([
            'equipo_id' => Equipo::factory(),
            'usuario_externo_id' => UsuarioExterno::factory(),
            'numero' => 'NS-3003',
            'fecha' => '2024-06-01',
        ]);

        $pdf = app(GenerateEquipoFormularioPdf::class)->execute($nota, EquipoFormularioPdfTipo::NotaSalida);

        $this->assertTrue($nota->fresh()->hasDocumentoOficial());
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
            'numero' => 'ENT-4004',
            'mouse' => true,
            'cooler' => false,
            'audifonos' => true,
        ]);

        $payload = app(EquipoFormularioPdfData::class)
            ->for($entrega, EquipoFormularioPdfTipo::Entrega);

        $this->assertSame('ENT-4004', $payload['documento']['numero']);
        $this->assertSame('Entrega', $payload['documento']['tipo']);
        $this->assertTrue($payload['documento']['mouse']['incluido']);
        $this->assertFalse($payload['documento']['cooler']['incluido']);
    }
}
