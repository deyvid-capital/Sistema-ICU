<?php

namespace Tests\Feature\Filament;

use App\Filament\Clusters\Configuracion\Pages\Marca;
use App\Models\AppearanceSetting;
use App\Models\User;
use App\Services\Apariencia\AparienciaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MarcaPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(fn () => true);
        Storage::fake('public');

        $usuario = User::factory()->create();
        $usuario->assignRole(Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']));

        $this->actingAs($usuario);
    }

    public function test_la_pagina_carga(): void
    {
        $this->get(Marca::getUrl(panel: 'admin'))
            ->assertSuccessful()
            ->assertSee('Marca e identidad')
            ->assertSee('Favicon');
    }

    public function test_subir_un_logo_lo_guarda_en_media_y_el_panel_lo_usa(): void
    {
        Livewire::test(Marca::class)
            ->fillForm([
                'logo_claro' => [UploadedFile::fake()->image('logo.png')],
                'alto_logo' => '3rem',
            ])
            ->call('guardar')
            ->assertHasNoFormErrors();

        $configuracion = AppearanceSetting::query()->firstOrFail();

        $this->assertTrue($configuracion->hasMedia('logo_claro'));
        $this->assertSame('3rem', $configuracion->alto_logo);
        $this->assertNull($configuracion->logo_claro);

        $servicio = app(AparienciaService::class);
        $servicio->olvidar();

        $this->assertNotNull($servicio->logoClaro());
        $this->assertStringNotContainsString('Capital-Safi-SA-logo-.png', (string) $servicio->logoClaro());
        $this->assertSame('3rem', $servicio->altoLogo());
    }

    /**
     * Cada colección es `singleFile()`: al subir un archivo nuevo, Media Library
     * borra el anterior del disco (original y conversiones) y su carpeta.
     */
    public function test_reemplazar_logo_o_favicon_borra_el_archivo_anterior(): void
    {
        foreach (Marca::COLECCIONES as $coleccion) {
            Livewire::test(Marca::class)
                ->fillForm([$coleccion => [UploadedFile::fake()->image('primero.png')]])
                ->call('guardar')
                ->assertHasNoFormErrors();

            $configuracion = AppearanceSetting::query()->firstOrFail();
            $anterior = $configuracion->getFirstMedia($coleccion);

            $this->assertNotNull($anterior, "No se guardó el archivo inicial de {$coleccion}.");

            $idAnterior = $anterior->getKey();
            $rutaAnterior = $anterior->getPathRelativeToRoot();
            Storage::disk('public')->assertExists($rutaAnterior);

            Livewire::test(Marca::class)
                ->fillForm([$coleccion => [UploadedFile::fake()->image('segundo.png')]])
                ->call('guardar')
                ->assertHasNoFormErrors();

            $configuracion->refresh();

            $this->assertDatabaseMissing('media', ['id' => $idAnterior]);
            Storage::disk('public')->assertMissing($rutaAnterior);
            $this->assertCount(1, $configuracion->getMedia($coleccion), "Quedó más de un archivo en {$coleccion}.");
            Storage::disk('public')->assertExists($configuracion->getFirstMedia($coleccion)->getPathRelativeToRoot());
        }
    }

    public function test_quitar_el_archivo_en_la_interfaz_lo_borra_del_disco(): void
    {
        Livewire::test(Marca::class)
            ->fillForm(['logo_claro' => [UploadedFile::fake()->image('logo.png')]])
            ->call('guardar');

        $configuracion = AppearanceSetting::query()->firstOrFail();
        $ruta = $configuracion->getFirstMedia('logo_claro')->getPathRelativeToRoot();

        Livewire::test(Marca::class)
            ->fillForm(['logo_claro' => []])
            ->call('guardar')
            ->assertHasNoFormErrors();

        $configuracion->refresh();

        $this->assertFalse($configuracion->hasMedia('logo_claro'));
        Storage::disk('public')->assertMissing($ruta);
    }

    /**
     * Tras `icu:migrar-imagenes-a-media` (que usa `preservingOriginal()`) la ruta
     * heredada y su archivo siguen existiendo: al subir un archivo nuevo hay que
     * borrarlos, o quedan huérfanos para siempre.
     */
    public function test_subir_un_archivo_borra_el_residuo_de_la_columna_heredada(): void
    {
        $heredados = [
            'logo_claro' => 'apariencia/logo-claro-viejo.png',
            'logo_oscuro' => 'apariencia/logo-oscuro-viejo.png',
            'favicon' => 'apariencia/favicon-viejo.png',
        ];

        $configuracion = AppearanceSetting::query()->create(['tema' => 'safi'] + $heredados);

        foreach ($heredados as $ruta) {
            Storage::disk('public')->put($ruta, 'contenido');
        }

        Livewire::test(Marca::class)
            ->fillForm([
                'logo_claro' => [UploadedFile::fake()->image('nuevo-claro.png')],
                'logo_oscuro' => [UploadedFile::fake()->image('nuevo-oscuro.png')],
                'favicon' => [UploadedFile::fake()->image('nuevo-favicon.png')],
            ])
            ->call('guardar')
            ->assertHasNoFormErrors();

        $configuracion->refresh();

        foreach ($heredados as $columna => $ruta) {
            Storage::disk('public')->assertMissing($ruta);
            $this->assertNull($configuracion->{$columna}, "La columna heredada {$columna} sigue apuntando a un archivo.");
            $this->assertTrue($configuracion->hasMedia($columna));
        }
    }

    public function test_no_se_borra_la_ruta_heredada_mientras_sea_la_que_usa_el_panel(): void
    {
        $configuracion = AppearanceSetting::query()->create([
            'tema' => 'safi',
            'logo_claro' => 'apariencia/logo-en-uso.png',
        ]);

        Storage::disk('public')->put('apariencia/logo-en-uso.png', 'contenido');

        // Se guarda la página sin subir nada: el logo heredado sigue siendo el vigente.
        Livewire::test(Marca::class)
            ->fillForm(['alto_logo' => '3rem'])
            ->call('guardar')
            ->assertHasNoFormErrors();

        $configuracion->refresh();

        Storage::disk('public')->assertExists('apariencia/logo-en-uso.png');
        $this->assertSame('apariencia/logo-en-uso.png', $configuracion->logo_claro);
        $this->assertStringContainsString('logo-en-uso.png', (string) app(AparienciaService::class)->logoClaro());
    }

    public function test_quitar_la_personalizacion_no_deja_ningun_archivo(): void
    {
        $configuracion = AppearanceSetting::query()->create([
            'tema' => 'safi',
            'favicon' => 'apariencia/favicon-viejo.png',
        ]);

        Storage::disk('public')->put('apariencia/favicon-viejo.png', 'contenido');

        Livewire::test(Marca::class)
            ->fillForm([
                'logo_claro' => [UploadedFile::fake()->image('logo.png')],
                'logo_oscuro' => [UploadedFile::fake()->image('logo-oscuro.png')],
            ])
            ->call('guardar');

        $configuracion->refresh();
        $rutas = collect(Marca::COLECCIONES)
            ->map(fn (string $coleccion): ?string => $configuracion->getFirstMedia($coleccion)?->getPathRelativeToRoot())
            ->filter()
            ->all();

        $this->assertNotEmpty($rutas);

        Livewire::test(Marca::class)
            ->call('restaurar')
            ->assertNotified();

        $configuracion->refresh();

        foreach (Marca::COLECCIONES as $coleccion) {
            $this->assertFalse($configuracion->hasMedia($coleccion));
            $this->assertNull($configuracion->{$coleccion});
        }

        foreach ($rutas as $ruta) {
            Storage::disk('public')->assertMissing($ruta);
        }

        Storage::disk('public')->assertMissing('apariencia/favicon-viejo.png');
        $this->assertDatabaseCount('media', 0);
    }

    public function test_un_alto_de_logo_invalido_se_rechaza(): void
    {
        Livewire::test(Marca::class)
            ->fillForm(['alto_logo' => 'grande'])
            ->call('guardar')
            ->assertHasFormErrors(['alto_logo']);
    }

    public function test_quitar_la_personalizacion_deja_los_archivos_de_la_aplicacion(): void
    {
        $configuracion = AppearanceSetting::create([
            'tema' => 'safi',
            'alto_logo' => '4rem',
        ]);

        $configuracion
            ->addMedia(UploadedFile::fake()->image('logo.png'))
            ->toMediaCollection('logo_claro');

        $configuracion
            ->addMedia(UploadedFile::fake()->image('favicon.png'))
            ->toMediaCollection('favicon');

        Livewire::test(Marca::class)
            ->call('restaurar')
            ->assertNotified();

        $configuracion->refresh();

        $this->assertFalse($configuracion->hasMedia('logo_claro'));
        $this->assertFalse($configuracion->hasMedia('favicon'));
        $this->assertDatabaseHas('appearance_settings', [
            'logo_claro' => null,
            'favicon' => null,
            'alto_logo' => null,
        ]);

        $this->assertStringContainsString(
            'Capital-Safi-SA-logo-.png',
            (string) app(AparienciaService::class)->logoClaro(),
        );
    }
}
