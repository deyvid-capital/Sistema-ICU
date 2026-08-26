<?php

namespace Tests\Feature\Media;

use App\Models\MantenimientoEquipo;
use App\Services\Archivos\ArchivosService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageConversionTest extends TestCase
{
    use RefreshDatabase;

    public function test_imagen_grande_genera_conversion_webp_y_conserva_original(): void
    {
        Storage::fake('public');

        config([
            'archivos.umbral_webp_kb' => 1,
            'media-library.queue_conversions_by_default' => false,
        ]);
        app(ArchivosService::class)->olvidar();

        $mantenimiento = MantenimientoEquipo::factory()->create([
            'cap_antes' => null,
            'cap_despues' => null,
        ]);

        $file = UploadedFile::fake()->image('grande.jpg', 1200, 800)->size(50);

        $media = $mantenimiento
            ->addMedia($file)
            ->usingFileName('antes_ST1_2026-01-01.jpg')
            ->toMediaCollection('cap_antes');

        $media->refresh();

        $this->assertTrue($media->hasGeneratedConversion('thumb'));
        $this->assertTrue($media->hasGeneratedConversion('webp'));
        $this->assertSame('antes_ST1_2026-01-01.jpg', $media->file_name);
        $this->assertNotSame('webp', pathinfo($media->file_name, PATHINFO_EXTENSION));
    }

    public function test_imagen_pequena_no_genera_webp(): void
    {
        Storage::fake('public');

        config([
            'archivos.umbral_webp_kb' => 500,
            'media-library.queue_conversions_by_default' => false,
        ]);
        app(ArchivosService::class)->olvidar();

        $mantenimiento = MantenimientoEquipo::factory()->create([
            'cap_antes' => null,
            'cap_despues' => null,
        ]);

        $file = UploadedFile::fake()->image('chica.png', 40, 40);

        $media = $mantenimiento
            ->addMedia($file)
            ->usingFileName('antes_ST1_2026-01-01.png')
            ->toMediaCollection('cap_antes');

        $media->refresh();

        $this->assertTrue($media->hasGeneratedConversion('thumb'));
        $this->assertFalse($media->hasGeneratedConversion('webp'));
    }
}
