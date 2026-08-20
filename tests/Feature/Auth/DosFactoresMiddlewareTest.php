<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Filament\Pages\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jeffgreco13\FilamentBreezy\Pages\MyProfilePage;
use Tests\TestCase;

class DosFactoresMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_que_requiere_2fa_sin_confirmar_es_redirigido_al_perfil(): void
    {
        $user = User::factory()->conRol('super_admin')->create(['requiere_2fa' => true]);

        $this->actingAs($user)
            ->get(Dashboard::getUrl(panel: 'admin'))
            ->assertRedirect(MyProfilePage::getUrl(panel: 'admin'));
    }

    public function test_no_redirige_la_propia_pagina_de_perfil(): void
    {
        $user = User::factory()->conRol('super_admin')->create(['requiere_2fa' => true]);

        $this->actingAs($user)
            ->get(MyProfilePage::getUrl(panel: 'admin'))
            ->assertSuccessful();
    }

    public function test_usuario_sin_requerimiento_de_2fa_entra_normal(): void
    {
        $user = User::factory()->conRol('super_admin')->create(['requiere_2fa' => false]);

        $this->actingAs($user)
            ->get(Dashboard::getUrl(panel: 'admin'))
            ->assertSuccessful();
    }
}
