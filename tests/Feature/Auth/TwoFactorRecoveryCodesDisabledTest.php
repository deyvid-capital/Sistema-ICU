<?php

namespace Tests\Feature\Auth;

use App\Filament\Pages\Auth\SetupTwoFactor;
use App\Filament\Pages\Auth\TwoFactorChallenge;
use App\Livewire\Profile\TwoFactorAuthentication;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class TwoFactorRecoveryCodesDisabledTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
    }

    public function test_activar_2fa_no_persiste_codigos_de_recuperacion(): void
    {
        $user = User::factory()->conRol()->create();

        $user->enableTwoFactorAuthentication();
        $user->refresh();

        $this->assertTrue($user->hasEnabledTwoFactor());
        $this->assertEmpty($user->generateRecoveryCodes());
        $this->assertNull($user->breezySession->getRawOriginal('two_factor_recovery_codes'));
        $this->assertTrue(filament('filament-breezy')->verifyRecoveryCode('cualquier-codigo', $user) === false);
    }

    public function test_observer_impide_guardar_codigos_aunque_se_fuerce_el_modelo(): void
    {
        $user = User::factory()->conRol()->create();
        $user->enableTwoFactorAuthentication();

        $user->breezySession->forceFill([
            'two_factor_recovery_codes' => ['aaaaaa-bbbbbb'],
        ])->save();

        $user->refresh();

        $this->assertNull($user->breezySession->getRawOriginal('two_factor_recovery_codes'));
        $this->assertFalse(filament('filament-breezy')->verifyRecoveryCode('aaaaaa-bbbbbb', $user));
    }

    public function test_enrolamiento_no_expone_codigos_y_no_deja_paso_de_recuperacion(): void
    {
        $user = User::factory()->conRol()->create(['requiere_2fa' => true]);
        $this->actingAs($user);

        Livewire::test(SetupTwoFactor::class)
            ->assertDontSee('códigos de recuperación')
            ->call('empezar')
            ->assertSet('step', 'setup')
            ->assertDontSee('códigos de recuperación')
            ->assertDontSee('Guarda tus códigos');

        $user->refresh();
        $this->assertNull($user->breezySession->getRawOriginal('two_factor_recovery_codes'));
    }

    public function test_perfil_oculta_acciones_y_listado_de_recuperacion(): void
    {
        $user = User::factory()->conRol()->create();
        $this->actingAs($user);

        $user->enableTwoFactorAuthentication();
        $user->confirmTwoFactorAuthentication();

        Livewire::test(TwoFactorAuthentication::class)
            ->assertSuccessful()
            ->assertDontSee('código de recuperación')
            ->assertDontSee('códigos de recuperación')
            ->assertActionHidden('regenerateCodes')
            ->call('toggleRecoveryCodes')
            ->assertSet('showRecoveryCodes', false);

        $this->assertTrue(
            Livewire::test(TwoFactorAuthentication::class)->get('recoveryCodes')->isEmpty()
        );
    }

    public function test_desafio_login_no_permite_usar_codigos_de_recuperacion(): void
    {
        $user = User::factory()->conRol()->create();
        $this->actingAs($user);

        $user->enableTwoFactorAuthentication();
        $user->breezySession->confirm();
        session()->forget('breezy_session_id');

        $this->get(Filament::getPanel('admin')->route('auth.two-factor'))
            ->assertSuccessful()
            ->assertDontSee(__('filament-breezy::default.two_factor.recovery_code_link'));

        Livewire::test(TwoFactorChallenge::class)
            ->set('usingRecoveryCode', true)
            ->assertSet('usingRecoveryCode', false)
            ->set('data.code', 'aaaaaa-bbbbbb')
            ->call('authenticate')
            ->assertHasErrors(['data.code']);
    }

    public function test_migracion_deja_la_columna_de_recuperacion_en_nulo(): void
    {
        $user = User::factory()->conRol()->create();
        $user->enableTwoFactorAuthentication();

        $this->assertDatabaseHas('breezy_sessions', [
            'authenticatable_id' => $user->id,
            'two_factor_recovery_codes' => null,
        ]);

        $this->assertTrue(
            DB::table('breezy_sessions')->whereNotNull('two_factor_recovery_codes')->doesntExist()
        );
    }
}
