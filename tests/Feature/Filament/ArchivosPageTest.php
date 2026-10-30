<?php

namespace Tests\Feature\Filament;

use App\Filament\Clusters\Configuracion\Pages\Archivos;
use App\Models\MediaSetting;
use App\Models\User;
use App\Services\Archivos\ArchivosService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ArchivosPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(fn () => true);

        $usuario = User::factory()->create();
        $usuario->assignRole(Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']));

        $this->actingAs($usuario);
    }

    public function test_la_pagina_carga(): void
    {
        $this->get(Archivos::getUrl(panel: 'admin'))
            ->assertSuccessful()
            ->assertSee('Archivos y almacenamiento')
            ->assertSee('Optimización de imágenes');
    }

    public function test_guardar_actualiza_media_settings_y_el_servicio(): void
    {
        Livewire::test(Archivos::class)
            ->fillForm([
                'disco' => 's3',
                'prefijo' => 'icu-test',
                'umbral_webp_kb' => 600,
                'calidad_webp' => 75,
                'thumb_ancho' => 320,
                'max_imagen_kb' => 4096,
                'max_pdf_kb' => 8192,
                'max_logo_kb' => 1024,
                'max_favicon_kb' => 256,
            ])
            ->call('guardar')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('media_settings', [
            'prefijo' => 'icu-test',
            'umbral_webp_kb' => 600,
            'calidad_webp' => 75,
        ]);

        $servicio = app(ArchivosService::class);
        $servicio->olvidar();

        $this->assertSame('s3', $servicio->disco());
        $this->assertSame('private', MediaSetting::query()->first()->visibilidad);
        $this->assertSame('icu-test', $servicio->prefijo());
        $this->assertSame(600, $servicio->umbralWebpKb());
        $this->assertSame(75, $servicio->calidadWebp());
        $this->assertNotNull(MediaSetting::query()->first());
    }

    public function test_no_se_puede_elegir_el_disco_publico_para_confidenciales(): void
    {
        Livewire::test(Archivos::class)
            ->fillForm(['disco' => 'public'])
            ->call('guardar')
            ->assertHasFormErrors(['disco']);
    }
}
