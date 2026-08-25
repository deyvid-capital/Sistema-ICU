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

    public function test_subir_un_logo_lo_guarda_y_el_panel_lo_usa(): void
    {
        Livewire::test(Marca::class)
            ->fillForm([
                'logo_claro' => [UploadedFile::fake()->image('logo.png')],
                'alto_logo' => '3rem',
            ])
            ->call('guardar')
            ->assertHasNoFormErrors();

        $configuracion = AppearanceSetting::query()->firstOrFail();

        $this->assertNotNull($configuracion->logo_claro);
        $this->assertSame('3rem', $configuracion->alto_logo);
        Storage::disk('public')->assertExists($configuracion->logo_claro);

        $servicio = app(AparienciaService::class);
        $servicio->olvidar();

        $this->assertStringContainsString($configuracion->logo_claro, (string) $servicio->logoClaro());
        $this->assertSame('3rem', $servicio->altoLogo());
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
        AppearanceSetting::create([
            'tema' => 'safi',
            'logo_claro' => 'apariencia/logo.png',
            'favicon' => 'apariencia/favicon.png',
            'alto_logo' => '4rem',
        ]);

        Livewire::test(Marca::class)
            ->call('restaurar')
            ->assertNotified();

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
