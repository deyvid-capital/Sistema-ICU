<?php

namespace Tests\Feature\Models;

use App\Models\ReciboEquipo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReciboEquipoDeletesStoredDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_soft_deleting_keeps_stored_document_for_restore(): void
    {
        Storage::fake('public');

        $path = 'recibo-equipos/recibo-test.pdf';
        Storage::disk('public')->put($path, 'contenido-pdf');

        $recibo = ReciboEquipo::factory()->create(['url' => $path]);

        $recibo->delete();

        $this->assertTrue($recibo->trashed());
        $this->assertFalse($recibo->storedDocumentWasDeleted);
        Storage::disk('public')->assertExists($path);
    }

    public function test_restoring_keeps_access_to_stored_document(): void
    {
        Storage::fake('public');

        $path = 'recibo-equipos/recibo-restore.pdf';
        Storage::disk('public')->put($path, 'contenido-pdf');

        $recibo = ReciboEquipo::factory()->create(['url' => $path]);
        $recibo->delete();
        $recibo->restore();

        $this->assertFalse($recibo->trashed());
        Storage::disk('public')->assertExists($path);
    }

    public function test_force_deleting_removes_stored_document_when_file_exists(): void
    {
        Storage::fake('public');

        $path = 'recibo-equipos/recibo-force.pdf';
        Storage::disk('public')->put($path, 'contenido-pdf');

        $recibo = ReciboEquipo::factory()->create(['url' => $path]);
        $recibo->delete();
        $recibo->forceDelete();

        $this->assertTrue($recibo->storedDocumentWasDeleted);
        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseMissing('recibo_equipo', ['id' => $recibo->id]);
    }

    public function test_force_deleting_without_file_does_not_flag_document_as_deleted(): void
    {
        Storage::fake('public');

        $recibo = ReciboEquipo::factory()->create([
            'url' => 'recibo-equipos/inexistente.pdf',
        ]);

        $recibo->forceDelete();

        $this->assertFalse($recibo->storedDocumentWasDeleted);
        $this->assertDatabaseMissing('recibo_equipo', ['id' => $recibo->id]);
    }
}
