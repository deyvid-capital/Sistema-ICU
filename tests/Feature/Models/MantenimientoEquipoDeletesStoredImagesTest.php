<?php

namespace Tests\Feature\Models;

use App\Models\MantenimientoEquipo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MantenimientoEquipoDeletesStoredImagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_soft_deleting_keeps_stored_images_for_restore(): void
    {
        Storage::fake('local');

        $mantenimiento = $this->mantenimientoConMedia();

        $mantenimiento->delete();

        $this->assertTrue($mantenimiento->trashed());
        $this->assertTrue($mantenimiento->hasMedia('cap_antes'));
        $this->assertTrue($mantenimiento->hasMedia('cap_despues'));
    }

    public function test_force_deleting_removes_media(): void
    {
        Storage::fake('local');

        $mantenimiento = $this->mantenimientoConMedia();
        $antesId = $mantenimiento->getFirstMedia('cap_antes')?->id;
        $despuesId = $mantenimiento->getFirstMedia('cap_despues')?->id;

        $mantenimiento->delete();
        $mantenimiento->forceDelete();

        $this->assertDatabaseMissing('media', ['id' => $antesId]);
        $this->assertDatabaseMissing('media', ['id' => $despuesId]);
        $this->assertDatabaseMissing('mantenimientos_equipos', ['id' => $mantenimiento->id]);
    }

    public function test_force_deleting_without_files_does_not_fail(): void
    {
        Storage::fake('local');

        $mantenimiento = MantenimientoEquipo::factory()->create([
            'cap_antes' => 'mantenimiento-equipos/inexistente.png',
            'cap_despues' => null,
        ]);

        $mantenimiento->forceDelete();

        $this->assertDatabaseMissing('mantenimientos_equipos', ['id' => $mantenimiento->id]);
    }

    public function test_replacing_media_keeps_single_file_in_collection(): void
    {
        Storage::fake('local');

        $mantenimiento = $this->mantenimientoConMedia();
        $oldId = $mantenimiento->getFirstMedia('cap_antes')?->id;

        $mantenimiento
            ->addMedia(UploadedFile::fake()->image('nueva.png'))
            ->usingFileName('antes_ST100001_2026-08-05.png')
            ->toMediaCollection('cap_antes');

        $mantenimiento->refresh();

        $this->assertSame(1, $mantenimiento->getMedia('cap_antes')->count());
        $this->assertNotSame($oldId, $mantenimiento->getFirstMedia('cap_antes')?->id);
        $this->assertDatabaseMissing('media', ['id' => $oldId]);
    }

    public function test_legacy_column_fallback_still_reports_stored_image(): void
    {
        Storage::fake('local');

        $path = 'mantenimiento-equipos/antes_ST100001_2026-08-01.png';
        Storage::disk('local')->put($path, 'imagen-antes');

        $mantenimiento = MantenimientoEquipo::factory()->create([
            'cap_antes' => $path,
            'cap_despues' => null,
        ]);

        $this->assertTrue($mantenimiento->hasStoredImage('cap_antes'));
        $this->assertStringContainsString($path, (string) $mantenimiento->storedImageUrl('cap_antes'));
    }

    private function mantenimientoConMedia(): MantenimientoEquipo
    {
        $mantenimiento = MantenimientoEquipo::factory()->create([
            'cap_antes' => null,
            'cap_despues' => null,
        ]);

        $mantenimiento
            ->addMedia(UploadedFile::fake()->image('antes.png'))
            ->usingFileName('antes_ST100001_2026-08-01.png')
            ->toMediaCollection('cap_antes');

        $mantenimiento
            ->addMedia(UploadedFile::fake()->image('despues.png'))
            ->usingFileName('despues_ST100001_2026-08-01.png')
            ->toMediaCollection('cap_despues');

        return $mantenimiento->refresh();
    }
}
