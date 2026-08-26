<?php

namespace Tests\Feature\Console;

use App\Models\AppearanceSetting;
use App\Models\MantenimientoEquipo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MigrarImagenesAMediaCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_migra_capturas_y_logos_desde_columnas_path(): void
    {
        Storage::fake('public');

        $antes = 'mantenimiento-equipos/antes_ST1_2026-01-01.png';
        $logo = 'apariencia/logo-claro.png';

        $this->putFakeImage($antes);
        $this->putFakeImage($logo);

        $mantenimiento = MantenimientoEquipo::factory()->create([
            'cap_antes' => $antes,
            'cap_despues' => null,
        ]);

        $marca = AppearanceSetting::create([
            'tema' => 'safi',
            'logo_claro' => $logo,
        ]);

        $exit = Artisan::call('icu:migrar-imagenes-a-media');

        $this->assertSame(0, $exit);

        $mantenimiento->refresh();
        $marca->refresh();

        $this->assertTrue($mantenimiento->hasMedia('cap_antes'));
        $this->assertSame('antes_ST1_2026-01-01.png', $mantenimiento->getFirstMedia('cap_antes')?->file_name);
        $this->assertTrue($marca->hasMedia('logo_claro'));
        Storage::disk('public')->assertExists($antes);
    }

    public function test_es_idempotente_si_ya_hay_media(): void
    {
        Storage::fake('public');

        $antes = 'mantenimiento-equipos/antes_ST1_2026-01-01.png';
        $this->putFakeImage($antes);

        $mantenimiento = MantenimientoEquipo::factory()->create([
            'cap_antes' => $antes,
        ]);

        Artisan::call('icu:migrar-imagenes-a-media');
        Artisan::call('icu:migrar-imagenes-a-media');

        $mantenimiento->refresh();

        $this->assertSame(1, $mantenimiento->getMedia('cap_antes')->count());
    }

    /**
     * Spatie genera conversiones al migrar: el archivo debe ser una imagen real, no texto.
     */
    private function putFakeImage(string $path): void
    {
        $file = UploadedFile::fake()->image(basename($path), 40, 40);

        Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));
    }
}
