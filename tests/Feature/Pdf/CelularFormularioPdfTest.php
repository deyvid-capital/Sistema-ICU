<?php

namespace Tests\Feature\Pdf;

use App\Enums\CelularFormularioPdfTipo;
use App\Enums\FirmantePosicion;
use App\Models\AsigCelular;
use App\Models\Cargo;
use App\Models\Celular;
use App\Models\EntregaCelular;
use App\Models\Firmante;
use App\Models\PdfCelular;
use App\Models\User;
use App\Services\Pdf\BrowsershotPdfFactory;
use App\Services\Pdf\CelularFormularioPdfData;
use App\Services\Pdf\GenerateCelularFormularioPdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CelularFormularioPdfTest extends TestCase
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

    public function test_it_generates_and_stores_oficial_pdf_for_entrega_celular(): void
    {
        $asignacion = AsigCelular::factory()->create([
            'celular_id' => Celular::factory(),
            'usuario_id' => User::factory(),
            'fecha_fin' => null,
        ]);

        $entrega = EntregaCelular::factory()->create([
            'asignacion_cel_id' => $asignacion->id,
            'numero' => '001-2026',
            'fecha' => '2026-04-18',
        ]);

        $pdf = app(GenerateCelularFormularioPdf::class)->execute($entrega, CelularFormularioPdfTipo::Entrega);

        $this->assertInstanceOf(PdfCelular::class, $pdf);
        $this->assertSame(CelularFormularioPdfTipo::Entrega->nombreOficial(), $pdf->nombre);
        $this->assertSame('pdf-celulares/entrega/FUC-001-2026.pdf', $pdf->url);
        $this->assertTrue($entrega->fresh()->hasDocumentoOficial());
        Storage::disk('public')->assertExists($pdf->url);
    }

    public function test_defaults_without_firmantes_use_ti_administrador_and_assigned_user(): void
    {
        $cargo = Cargo::factory()->create(['cargo' => 'Analista']);
        $usuario = User::factory()->create([
            'name' => 'Ana Pérez',
            'id_cargo' => $cargo->id_cargo,
        ]);

        $asignacion = AsigCelular::factory()->create([
            'celular_id' => Celular::factory(),
            'usuario_id' => $usuario->id,
        ]);

        $entrega = EntregaCelular::factory()->create([
            'asignacion_cel_id' => $asignacion->id,
        ]);

        $payload = app(CelularFormularioPdfData::class)
            ->for($entrega, CelularFormularioPdfTipo::Entrega);

        $this->assertSame('Tecnología de la Información', $payload['documento']['firma_izquierda']['nombre']);
        $this->assertSame('Administrador', $payload['documento']['firma_centro']['nombre']);
        $this->assertSame('Ana Pérez', $payload['documento']['firma_derecha']['nombre']);
        $this->assertSame('Analista', $payload['documento']['firma_derecha']['cargo']);
    }

    public function test_firmantes_by_position_override_defaults(): void
    {
        $asignacion = AsigCelular::factory()->create([
            'celular_id' => Celular::factory(),
            'usuario_id' => User::factory()->create(['name' => 'Asignado']),
        ]);

        $entrega = EntregaCelular::factory()->create([
            'asignacion_cel_id' => $asignacion->id,
        ]);

        $izquierda = User::factory()->create(['name' => 'Firma Izquierda']);
        $centro = User::factory()->create(['name' => 'Firma Centro']);
        $derecha = User::factory()->create(['name' => 'Firma Derecha']);

        foreach ([
            [$izquierda, FirmantePosicion::Izquierda],
            [$centro, FirmantePosicion::Centro],
            [$derecha, FirmantePosicion::Derecha],
        ] as [$usuario, $posicion]) {
            Firmante::query()->create([
                'usuario_id' => $usuario->id,
                'fecha' => now(),
                'posicion' => $posicion,
                'documento_id' => $entrega->id,
                'documento_type' => EntregaCelular::class,
            ]);
        }

        $payload = app(CelularFormularioPdfData::class)
            ->for($entrega->fresh(), CelularFormularioPdfTipo::Entrega);

        $this->assertSame('Firma Izquierda', $payload['documento']['firma_izquierda']['nombre']);
        $this->assertSame('Firma Centro', $payload['documento']['firma_centro']['nombre']);
        $this->assertSame('Firma Derecha', $payload['documento']['firma_derecha']['nombre']);
    }
}
