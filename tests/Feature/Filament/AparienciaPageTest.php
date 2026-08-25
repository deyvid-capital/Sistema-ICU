<?php

namespace Tests\Feature\Filament;

use App\Filament\Clusters\Configuracion\Pages\Apariencia;
use App\Models\AppearanceSetting;
use App\Models\User;
use App\Services\Apariencia\AparienciaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AparienciaPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(fn () => true);

        // El panel exige al menos un rol asignado (User::motivoAccesoDenegado()).
        $usuario = User::factory()->create();
        $usuario->assignRole(Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']));

        $this->actingAs($usuario);
    }

    public function test_la_pagina_carga_con_la_sub_navegacion_de_configuracion(): void
    {
        $this->get(Apariencia::getUrl(panel: 'admin'))
            ->assertSuccessful()
            ->assertSee('Tema y colores')
            ->assertSee('Marca e identidad')
            ->assertSee('Corporativo SAFI');
    }

    public function test_guardar_un_tema_curado_persiste_la_configuracion(): void
    {
        Livewire::test(Apariencia::class)
            ->fillForm(['tema' => 'turquesa'])
            ->call('guardar')
            ->assertNotified()
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('appearance_settings', ['tema' => 'turquesa']);
        $this->assertSame('#008A83', app(AparienciaService::class)->resolver()['primario']);
    }

    public function test_guardar_colores_personalizados_persiste_cada_valor(): void
    {
        Livewire::test(Apariencia::class)
            ->fillForm([
                'tema' => AparienciaService::TEMA_PERSONALIZADO,
                'color_primario' => '#008A83',
                'color_gris' => 'Zinc',
                'color_sidebar_claro' => '#003973',
                'color_sidebar_oscuro' => '#041E36',
                'color_topbar_claro' => '#FFFFFF',
                'color_topbar_oscuro' => '#071B2B',
            ])
            ->call('guardar')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('appearance_settings', [
            'tema' => 'personalizado',
            'color_primario' => '#008A83',
            'color_sidebar_claro' => '#003973',
        ]);
    }

    public function test_un_color_con_formato_invalido_no_se_guarda(): void
    {
        Livewire::test(Apariencia::class)
            ->fillForm([
                'tema' => AparienciaService::TEMA_PERSONALIZADO,
                'color_sidebar_claro' => 'azul',
            ])
            ->call('guardar')
            ->assertHasFormErrors(['color_sidebar_claro']);

        $this->assertDatabaseCount('appearance_settings', 0);
    }

    public function test_restaurar_descarta_los_colores_pero_conserva_la_marca(): void
    {
        AppearanceSetting::create([
            'tema' => AparienciaService::TEMA_PERSONALIZADO,
            'color_primario' => '#FF00FF',
            'color_sidebar_claro' => '#FF00FF',
            'logo_claro' => 'apariencia/logo.png',
        ]);

        Livewire::test(Apariencia::class)
            ->call('restaurar')
            ->assertNotified();

        $this->assertDatabaseHas('appearance_settings', [
            'tema' => 'safi',
            'color_primario' => null,
            'color_sidebar_claro' => null,
            'logo_claro' => 'apariencia/logo.png',
        ]);
    }

    public function test_al_elegir_un_tema_se_precargan_sus_colores(): void
    {
        Livewire::test(Apariencia::class)
            ->fillForm(['tema' => 'confianza'])
            ->assertFormSet([
                'color_primario' => '#0081C1',
                'color_topbar_claro' => '#006B96',
            ]);
    }
}
