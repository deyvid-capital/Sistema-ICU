<?php

namespace Tests\Feature\Models;

use App\Enums\EquipoFormularioPdfTipo;
use App\Models\EntregaEquipo;
use App\Models\NotaSalidaEquipo;
use App\Models\PdfEquipo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FormularioEquipoForceDeletesPdfDocumentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_soft_deleting_formulario_keeps_pdf_file_for_restore(): void
    {
        Storage::fake('local');

        $path = 'pdf-equipos/entrega/FUE-001-2026.pdf';
        Storage::disk('local')->put($path, 'contenido-pdf');

        $entrega = EntregaEquipo::factory()->create(['numero' => '001-2026']);
        $pdf = $entrega->pdfs()->create([
            'nombre' => EquipoFormularioPdfTipo::Entrega->nombreOficial(),
            'fecha' => $entrega->fecha,
            'url' => $path,
        ]);

        $entrega->delete();

        $this->assertTrue($entrega->trashed());
        $this->assertNotNull(PdfEquipo::withTrashed()->find($pdf->id));
        Storage::disk('local')->assertExists($path);
    }

    public function test_force_deleting_entrega_removes_pdf_records_and_files(): void
    {
        Storage::fake('local');

        $path = 'pdf-equipos/entrega/FUE-002-2026.pdf';
        Storage::disk('local')->put($path, 'contenido-pdf');

        $entrega = EntregaEquipo::factory()->create(['numero' => '002-2026']);
        $pdf = $entrega->pdfs()->create([
            'nombre' => EquipoFormularioPdfTipo::Entrega->nombreOficial(),
            'fecha' => $entrega->fecha,
            'url' => $path,
        ]);

        $entrega->delete();
        $entrega->forceDelete();

        Storage::disk('local')->assertMissing($path);
        $this->assertDatabaseMissing('pdf_equipos', ['id' => $pdf->id]);
        $this->assertDatabaseMissing('entregas_equipos', ['id' => $entrega->id]);
    }

    public function test_force_deleting_nota_salida_removes_soft_deleted_pdfs_too(): void
    {
        Storage::fake('local');

        $path = 'pdf-equipos/nota-salida/NotaSalida-003-2026.pdf';
        Storage::disk('local')->put($path, 'contenido-pdf');

        $nota = NotaSalidaEquipo::factory()->create(['numero' => '003-2026']);
        $pdf = $nota->pdfs()->create([
            'nombre' => EquipoFormularioPdfTipo::NotaSalida->nombreOficial(),
            'fecha' => $nota->fecha,
            'url' => $path,
        ]);

        $pdf->delete();
        $this->assertTrue($pdf->trashed());
        Storage::disk('local')->assertExists($path);

        $nota->forceDelete();

        Storage::disk('local')->assertMissing($path);
        $this->assertDatabaseMissing('pdf_equipos', ['id' => $pdf->id]);
        $this->assertDatabaseMissing('notas_salida_equipos', ['id' => $nota->id]);
    }
}
