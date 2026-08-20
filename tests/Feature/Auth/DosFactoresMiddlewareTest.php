<?php

namespace Tests\Feature\Auth;

use App\Filament\Pages\Auth\SetupTwoFactor;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jeffgreco13\FilamentBreezy\Pages\MyProfilePage;
use Livewire\Livewire;
use Tests\TestCase;

class DosFactoresMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_que_requiere_2fa_sin_confirmar_es_redirigido_al_enrolamiento(): void
    {
        $user = User::factory()->conRol('super_admin')->create(['requiere_2fa' => true]);

        $this->actingAs($user)
            ->get(Dashboard::getUrl(panel: 'admin'))
            ->assertRedirect(SetupTwoFactor::getUrl());
    }

    public function test_no_redirige_la_propia_pagina_de_enrolamiento(): void
    {
        $user = User::factory()->conRol('super_admin')->create(['requiere_2fa' => true]);

        $this->actingAs($user)
            ->get(SetupTwoFactor::getUrl())
            ->assertSuccessful()
            ->assertSee('Protege tu cuenta')
            ->assertSee('Microsoft Authenticator')
            ->assertSee('Ya la instalé');
    }

    public function test_usuario_forzado_no_puede_escapar_por_mi_perfil(): void
    {
        $user = User::factory()->conRol('super_admin')->create(['requiere_2fa' => true]);

        $this->actingAs($user)
            ->get(MyProfilePage::getUrl(panel: 'admin'))
            ->assertRedirect(SetupTwoFactor::getUrl());
    }

    public function test_usuario_sin_requerimiento_de_2fa_entra_normal(): void
    {
        $user = User::factory()->conRol('super_admin')->create(['requiere_2fa' => false]);

        $this->actingAs($user)
            ->get(Dashboard::getUrl(panel: 'admin'))
            ->assertSuccessful();
    }

    public function test_empezar_genera_secreto_y_muestra_qr(): void
    {
        $user = User::factory()->conRol('super_admin')->create(['requiere_2fa' => true]);

        $this->actingAs($user);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();

        Livewire::test(SetupTwoFactor::class)
            ->assertSet('step', 'install')
            ->call('empezar')
            ->assertSet('step', 'setup')
            ->assertSee('Escanea el código QR')
            ->assertSee('Clave de configuración');

        $user->refresh();

        $this->assertTrue($user->hasEnabledTwoFactor());
        $this->assertFalse($user->hasConfirmedTwoFactor());
    }
}
