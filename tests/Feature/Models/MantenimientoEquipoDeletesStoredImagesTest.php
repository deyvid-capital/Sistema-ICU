<?php

namespace Tests\Feature\Models;

use App\Models\MantenimientoEquipo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MantenimientoEquipoDeletesStoredImagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_soft_deleting_keeps_stored_images_for_restore(): void
    {
        Storage::fake('public');

        [$antes, $despues] = $this->fakeStoredImages();

        $mantenimiento = MantenimientoEquipo::factory()->create([
            'cap_antes' => $antes,
            'cap_despues' => $despues,
        ]);

        $mantenimiento->delete();

        $this->assertTrue($mantenimiento->trashed());
        Storage::disk('public')->assertExists($antes);
        Storage::disk('public')->assertExists($despues);
    }

    public function test_force_deleting_removes_both_stored_images(): void
    {
        Storage::fake('public');

        [$antes, $despues] = $this->fakeStoredImages();

        $mantenimiento = MantenimientoEquipo::factory()->create([
            'cap_antes' => $antes,
            'cap_despues' => $despues,
        ]);

        $mantenimiento->delete();
        $mantenimiento->forceDelete();

        Storage::disk('public')->assertMissing($antes);
        Storage::disk('public')->assertMissing($despues);
        $this->assertDatabaseMissing('mantenimientos_equipos', ['id' => $mantenimiento->id]);
    }

    public function test_force_deleting_without_files_does_not_fail(): void
    {
        Storage::fake('public');

        $mantenimiento = MantenimientoEquipo::factory()->create([
            'cap_antes' => 'mantenimiento-equipos/inexistente.png',
            'cap_despues' => null,
        ]);

        $mantenimiento->forceDelete();

        $this->assertSame([], $mantenimiento->deleteStoredImages());
        $this->assertDatabaseMissing('mantenimientos_equipos', ['id' => $mantenimiento->id]);
    }

    public function test_replacing_an_image_deletes_the_previous_file(): void
    {
        Storage::fake('public');

        [$antes] = $this->fakeStoredImages();
        $nueva = 'mantenimiento-equipos/antes_ST100001_2026-08-05.jpg';
        Storage::disk('public')->put($nueva, 'imagen-nueva');

        $mantenimiento = MantenimientoEquipo::factory()->create(['cap_antes' => $antes]);

        $mantenimiento->update(['cap_antes' => $nueva]);

        Storage::disk('public')->assertMissing($antes);
        Storage::disk('public')->assertExists($nueva);
    }

    public function test_clearing_an_image_deletes_the_previous_file(): void
    {
        Storage::fake('public');

        [$antes] = $this->fakeStoredImages();

        $mantenimiento = MantenimientoEquipo::factory()->create(['cap_antes' => $antes]);

        $mantenimiento->update(['cap_antes' => null]);

        Storage::disk('public')->assertMissing($antes);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function fakeStoredImages(): array
    {
        $antes = 'mantenimiento-equipos/antes_ST100001_2026-08-01.png';
        $despues = 'mantenimiento-equipos/despues_ST100001_2026-08-01.png';

        Storage::disk('public')->put($antes, 'imagen-antes');
        Storage::disk('public')->put($despues, 'imagen-despues');

        return [$antes, $despues];
    }
}
