<?php

namespace Tests\Feature\Models;

use App\Models\PdfEquipo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PdfEquipoDeletesStoredDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_soft_deleting_keeps_stored_document_for_restore(): void
    {
        Storage::fake('public');

        $path = 'pdf-equipos/entrega-test.pdf';
        Storage::disk('public')->put($path, 'contenido-pdf');

        $pdf = PdfEquipo::factory()->create(['url' => $path]);

        $pdf->delete();

        $this->assertTrue($pdf->trashed());
        $this->assertFalse($pdf->storedDocumentWasDeleted);
        Storage::disk('public')->assertExists($path);
    }

    public function test_force_deleting_removes_stored_document_when_file_exists(): void
    {
        Storage::fake('public');

        $path = 'pdf-equipos/entrega-force.pdf';
        Storage::disk('public')->put($path, 'contenido-pdf');

        $pdf = PdfEquipo::factory()->create(['url' => $path]);
        $pdf->delete();
        $pdf->forceDelete();

        $this->assertTrue($pdf->storedDocumentWasDeleted);
        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseMissing('pdf_equipos', ['id' => $pdf->id]);
    }
}
