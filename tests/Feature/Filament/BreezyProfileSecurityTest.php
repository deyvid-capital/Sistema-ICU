<?php

namespace Tests\Feature\Filament;

use App\Livewire\Profile\PersonalInfo;
use App\Livewire\Profile\TwoFactorAuthentication;
use App\Livewire\Profile\UpdatePassword;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Jeffgreco13\FilamentBreezy\Livewire\BrowserSessions;
use Jeffgreco13\FilamentBreezy\Pages\MyProfilePage;
use Livewire\Livewire;
use Tests\TestCase;

class BreezyProfileSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->conRol()->create());

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
    }

    public function test_profile_page_includes_password_two_factor_and_browser_sessions(): void
    {
        $components = filament('filament-breezy')->getRegisteredMyProfileComponents();

        $this->assertContains(UpdatePassword::class, $components);
        $this->assertContains(TwoFactorAuthentication::class, $components);
        $this->assertContains(BrowserSessions::class, $components);

        $this->assertContains(PersonalInfo::class, $components);

        $this->get(MyProfilePage::getUrl(panel: 'admin'))
            ->assertSuccessful()
            ->assertSee('Información personal')
            ->assertSee('Contraseña')
            ->assertSee('Autenticación de dos factores')
            ->assertSee('Sesiones del navegador')
            ->assertSee('El correo no se puede modificar.');
    }

    public function test_personal_info_shows_email_disabled(): void
    {
        Livewire::test(PersonalInfo::class)
            ->assertSuccessful()
            ->assertFormFieldIsDisabled('email')
            ->assertFormFieldIsDisabled('carnet');
    }

    public function test_personal_info_updates_name_and_phone_but_not_email(): void
    {
        $user = auth()->user();
        $originalEmail = $user->email;
        $originalCarnet = $user->carnet;

        Livewire::test(PersonalInfo::class)
            ->fillForm([
                'nombres' => 'Ana María',
                'apellidos' => 'García López',
                'telefono' => '7123-4567',
            ])
            ->set('data.email', 'no-cambiar@example.com')
            ->set('data.carnet', '999999')
            ->call('submit')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $user->refresh();

        $this->assertSame('Ana María', $user->nombres);
        $this->assertSame('García López', $user->apellidos);
        $this->assertSame('Ana María García López', $user->name);
        $this->assertSame('7123-4567', $user->telefono);
        $this->assertSame($originalEmail, $user->email);
        $this->assertSame($originalCarnet, $user->carnet);
    }

    public function test_password_update_requires_current_password(): void
    {
        Livewire::test(UpdatePassword::class)
            ->fillForm([
                'current_password' => 'incorrecta',
                'new_password' => 'NuevaClave1',
                'new_password_confirmation' => 'NuevaClave1',
            ])
            ->call('submit')
            ->assertHasFormErrors(['current_password']);
    }

    public function test_password_update_rejects_password_without_mixed_case(): void
    {
        Livewire::test(UpdatePassword::class)
            ->fillForm([
                'current_password' => '12345678',
                'new_password' => 'nuevaclave1',
                'new_password_confirmation' => 'nuevaclave1',
            ])
            ->call('submit')
            ->assertHasFormErrors(['new_password']);
    }

    public function test_password_update_rejects_password_without_numbers(): void
    {
        Livewire::test(UpdatePassword::class)
            ->fillForm([
                'current_password' => '12345678',
                'new_password' => 'NuevaClave',
                'new_password_confirmation' => 'NuevaClave',
            ])
            ->call('submit')
            ->assertHasFormErrors(['new_password']);
    }

    public function test_password_update_succeeds_with_valid_password(): void
    {
        $user = auth()->user();

        Livewire::test(UpdatePassword::class)
            ->fillForm([
                'current_password' => '12345678',
                'new_password' => 'NuevaClave1',
                'new_password_confirmation' => 'NuevaClave1',
            ])
            ->call('submit')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertTrue(Hash::check('NuevaClave1', $user->fresh()->password));
    }

    public function test_two_factor_can_be_enabled_for_uuid_users(): void
    {
        $user = auth()->user();

        $this->assertFalse($user->hasEnabledTwoFactor());

        $user->enableTwoFactorAuthentication();

        $user->refresh();

        $this->assertTrue($user->hasEnabledTwoFactor());
        $this->assertFalse($user->hasConfirmedTwoFactor());
        $this->assertNotNull($user->breezySession);
        $this->assertDatabaseHas('breezy_sessions', [
            'authenticatable_id' => $user->id,
            'authenticatable_type' => $user->getMorphClass(),
        ]);
    }

    public function test_two_factor_section_is_visible_when_not_enabled(): void
    {
        Livewire::test(TwoFactorAuthentication::class)
            ->assertSuccessful()
            ->assertSee('Aún no ha activado la verificación en dos pasos');
    }

    public function test_usuario_sin_rol_admin_no_puede_desactivar_2fa_en_perfil(): void
    {
        $user = User::factory()->conRol('operador')->create();
        $user->enableTwoFactorAuthentication();
        $user->confirmTwoFactorAuthentication();

        $this->actingAs($user);

        Livewire::test(TwoFactorAuthentication::class)
            ->assertSuccessful()
            ->assertActionHidden('disable');
    }

    public function test_super_admin_puede_desactivar_2fa_en_perfil(): void
    {
        $user = auth()->user();
        $user->enableTwoFactorAuthentication();
        $user->confirmTwoFactorAuthentication();

        Livewire::test(TwoFactorAuthentication::class)
            ->assertSuccessful()
            ->assertActionVisible('disable');
    }

    public function test_browser_sessions_section_is_visible(): void
    {
        Livewire::test(BrowserSessions::class)
            ->assertSuccessful()
            ->assertSee('Sesiones del navegador');
    }
}
