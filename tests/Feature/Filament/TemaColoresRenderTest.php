<?php

namespace Tests\Feature\Filament;

use App\Filament\Clusters\Configuracion\Pages\TemaColores;
use App\Models\AppearanceSetting;
use App\Models\User;
use App\Services\Configuracion\TemaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TemaColoresRenderTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioConAcceso(): User
    {
        $usuario = User::factory()->create();
        $usuario->assignRole(Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']));

        return $usuario;
    }

    public function test_el_panel_inyecta_los_tokens_de_color_del_tema_guardado(): void
    {
        Gate::before(fn () => true);

        AppearanceSetting::create([
            'tema' => TemaService::TEMA_PERSONALIZADO,
            'color_primario' => '#008A83',
            'color_gris' => 'Slate',
            'color_sidebar_claro' => '#003973',
            'color_sidebar_oscuro' => '#041E36',
            'color_topbar_claro' => '#006B96',
            'color_topbar_oscuro' => '#071B2B',
        ]);

        $respuesta = $this->actingAs($this->usuarioConAcceso())
            ->get('/admin')
            ->assertSuccessful();

        // Tokens del sidebar y de la barra superior, y texto blanco por contraste.
        $respuesta->assertSee('--icu-sidebar-bg: #003973', false);
        $respuesta->assertSee('--icu-topbar-bg: #006B96', false);
        $respuesta->assertSee('--icu-sidebar-fg: #FFFFFF', false);
        $respuesta->assertSee('--icu-topbar-fg: #FFFFFF', false);
    }

    /**
     * La sub-navegación lateral de una página reutiliza las clases del sidebar
     * (`fi-sidebar-item-*`) fuera de `.fi-sidebar`, así que una regla sin acotar
     * le pintaría el texto del color del sidebar y la dejaría invisible.
     */
    public function test_las_reglas_del_menu_estan_acotadas_al_sidebar(): void
    {
        Gate::before(fn () => true);

        $respuesta = $this->actingAs($this->usuarioConAcceso())
            ->get(TemaColores::getUrl(panel: 'admin'))
            ->assertSuccessful();

        $respuesta->assertSee('.fi-sidebar .fi-sidebar-item-label', false);
        $respuesta->assertSee('.fi-sidebar .fi-sidebar-item.fi-active > .fi-sidebar-item-btn', false);

        // Ningún selector puede empezar por la clase suelta (cada uno va en su línea).
        $this->assertDoesNotMatchRegularExpression(
            '/^\s*\.fi-sidebar-(item|group)/m',
            $this->soloEstilosDeTema($respuesta->getContent()),
        );

        // Y la sub-navegación sigue mostrando sus dos páginas.
        $respuesta->assertSee('Tema y colores')->assertSee('Marca e identidad');
    }

    private function soloEstilosDeTema(string $html): string
    {
        preg_match_all('/<style>(?<css>[^<]*--icu-sidebar-bg[^<]*)<\/style>/', $html, $coincidencias);

        return implode("\n", $coincidencias['css']);
    }

    public function test_el_panel_renderiza_sin_configuracion_guardada(): void
    {
        Gate::before(fn () => true);

        $this->actingAs($this->usuarioConAcceso())
            ->get('/admin')
            ->assertSuccessful()
            ->assertSee('--icu-sidebar-bg: #003973', false);
    }

    public function test_el_tema_se_resuelve_aunque_la_tabla_no_exista(): void
    {
        Schema::drop('appearance_settings');

        $resolucion = (new TemaService)->resolver();

        $this->assertSame(config('tema.tema_por_defecto'), $resolucion['tema']);
    }

    public function test_solo_quien_tiene_el_permiso_de_shield_entra_a_la_pagina(): void
    {
        Artisan::call('shield:generate', [
            '--all' => true,
            '--panel' => 'admin',
            '--option' => 'permissions',
            '--ignore-existing-policies' => true,
        ]);

        $superAdmin = User::factory()->create();
        $rolAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $rolAdmin->syncPermissions(Permission::query()->get());
        $superAdmin->assignRole($rolAdmin);

        $operador = User::factory()->create();
        $operador->assignRole(Role::firstOrCreate(['name' => 'operador', 'guard_name' => 'web']));

        $this->actingAs($superAdmin)
            ->get(TemaColores::getUrl(panel: 'admin'))
            ->assertSuccessful();

        $this->actingAs($operador)
            ->get(TemaColores::getUrl(panel: 'admin'))
            ->assertForbidden();
    }
}
