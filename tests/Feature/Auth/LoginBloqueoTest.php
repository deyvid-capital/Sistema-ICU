<?php

namespace Tests\Feature\Auth;

use App\Filament\Pages\Auth\Login;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class LoginBloqueoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Reinicia el rate-limit por IP de Filament entre pruebas.
        Cache::flush();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
    }

    public function test_contrasena_incorrecta_suma_intento_y_avisa_restantes(): void
    {
        config(['acceso.max_intentos_fallidos' => 3]);
        $user = User::factory()->conRol()->create();

        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => 'incorrecta'])
            ->call('authenticate')
            ->assertHasFormErrors(['email'])
            ->assertNotified();

        $this->assertSame(1, $user->fresh()->intentos_fallidos);
        $this->assertFalse($user->fresh()->estaBloqueado());
    }

    public function test_se_bloquea_al_alcanzar_el_maximo_de_intentos(): void
    {
        config(['acceso.max_intentos_fallidos' => 3]);
        $user = User::factory()->conRol()->create();

        $component = Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => 'incorrecta']);

        $component->call('authenticate');
        $component->call('authenticate');
        $this->assertFalse($user->fresh()->estaBloqueado());

        $component->call('authenticate')->assertNotified();

        $this->assertTrue($user->fresh()->estaBloqueado());
    }

    public function test_contrasena_correcta_pero_acceso_deshabilitado_no_suma_intento(): void
    {
        $user = User::factory()->conRol()->sinAcceso()->create();

        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => '12345678'])
            ->call('authenticate')
            ->assertHasFormErrors(['email'])
            ->assertNotified();

        $this->assertSame(0, $user->fresh()->intentos_fallidos);
        $this->assertFalse(auth()->check());
    }

    public function test_contrasena_correcta_pero_acceso_vencido_es_rechazado(): void
    {
        $user = User::factory()->conRol()->accesoDefinido(now()->subDay())->create();

        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => '12345678'])
            ->call('authenticate')
            ->assertHasFormErrors(['email'])
            ->assertNotified();

        $this->assertFalse(auth()->check());
    }
}
