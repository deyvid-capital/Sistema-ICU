<?php

namespace Tests\Feature\Models;

use App\Models\ReciboEquipo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReciboEquipoStoredDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_stored_document_returns_true_when_file_exists(): void
    {
        Storage::fake('public');

        $path = 'recibo-equipos/recibo.pdf';
        Storage::disk('public')->put($path, 'contenido-pdf');

        $recibo = ReciboEquipo::factory()->create(['url' => $path]);

        $this->assertTrue($recibo->hasStoredDocument());
        $this->assertSame('recibo.pdf', $recibo->storedDocumentFilename());
        $this->assertNotNull($recibo->storedDocumentUrl());
    }

    public function test_has_stored_document_returns_false_when_file_is_missing(): void
    {
        Storage::fake('public');

        $recibo = ReciboEquipo::factory()->create([
            'url' => 'recibo-equipos/inexistente.pdf',
        ]);

        $this->assertFalse($recibo->hasStoredDocument());
        $this->assertNull($recibo->storedDocumentUrl());
    }

    public function test_download_stored_document_returns_streamed_response(): void
    {
        Storage::fake('public');

        $path = 'recibo-equipos/descarga.pdf';
        Storage::disk('public')->put($path, 'contenido-pdf');

        $recibo = ReciboEquipo::factory()->create(['url' => $path]);
        $response = $recibo->downloadStoredDocument();

        $response->sendContent();

        $this->assertSame(200, $response->getStatusCode());
    }
}
