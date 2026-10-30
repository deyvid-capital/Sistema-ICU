<?php

namespace Tests\Unit\Services;

use App\Models\MediaSetting;
use App\Services\Archivos\ArchivosService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArchivosServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sin_fila_usa_defaults_de_config(): void
    {
        $servicio = new ArchivosService;

        $this->assertSame(config('archivos.disco'), $servicio->disco());
        $this->assertSame(config('archivos.prefijo'), $servicio->prefijo());
        $this->assertSame((int) config('archivos.umbral_webp_kb'), $servicio->umbralWebpKb());
    }

    public function test_fila_guardada_tiene_prioridad(): void
    {
        MediaSetting::create([
            'disco' => 's3',
            'prefijo' => 'custom',
            'umbral_webp_kb' => 750,
            'calidad_webp' => 70,
            'thumb_ancho' => 200,
            'max_imagen_kb' => 3000,
            'max_pdf_kb' => 7000,
            'max_logo_kb' => 900,
            'max_favicon_kb' => 100,
            'visibilidad' => 'private',
        ]);

        $servicio = new ArchivosService;

        $this->assertSame('s3', $servicio->disco());
        $this->assertSame('custom', $servicio->prefijo());
        $this->assertSame(750, $servicio->umbralWebpKb());
        $this->assertSame(750 * 1024, $servicio->umbralWebpBytes());
        $this->assertSame('private', $servicio->visibilidad());
        $this->assertSame('pdf-equipos', $servicio->directorioPdf('pdf_equipos'));
    }

    public function test_los_confidenciales_nunca_usan_el_disco_publico(): void
    {
        MediaSetting::create(['disco' => 'public', 'visibilidad' => 'public']);

        $servicio = new ArchivosService;

        $this->assertSame('local', $servicio->disco());
        $this->assertSame('private', $servicio->visibilidad());
        $this->assertSame('public', $servicio->discoPublico());
    }
}
