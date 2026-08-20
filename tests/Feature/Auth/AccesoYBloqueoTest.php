<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccesoYBloqueoTest extends TestCase
{
    use RefreshDatabase;

    private function panel(): Panel
    {
        return Filament::getPanel('admin');
    }

    public function test_usuario_sin_rol_no_puede_acceder(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->canAccessPanel($this->panel()));
    }

    public function test_usuario_con_rol_puede_acceder(): void
    {
        $user = User::factory()->conRol()->create();

        $this->assertTrue($user->canAccessPanel($this->panel()));
    }

    public function test_acceso_deshabilitado_bloquea_el_ingreso(): void
    {
        $user = User::factory()->conRol()->sinAcceso()->create();

        $this->assertFalse($user->canAccessPanel($this->panel()));
    }

    public function test_acceso_vencido_bloquea_y_vigente_permite(): void
    {
        $vencido = User::factory()->conRol()->accesoDefinido(now()->subDay())->create();
        $vigente = User::factory()->conRol()->accesoDefinido(now()->addDay())->create();

        $this->assertFalse($vencido->canAccessPanel($this->panel()));
        $this->assertTrue($vigente->canAccessPanel($this->panel()));
    }

    public function test_intentos_fallidos_bloquean_al_alcanzar_el_maximo(): void
    {
        config(['acceso.max_intentos_fallidos' => 3]);
        $user = User::factory()->conRol()->create();

        $user->registrarIntentoFallido();
        $user->registrarIntentoFallido();
        $this->assertFalse($user->estaBloqueado());

        $user->registrarIntentoFallido();
        $this->assertTrue($user->estaBloqueado());
        $this->assertFalse($user->canAccessPanel($this->panel()));
    }

    public function test_desbloquear_restablece_el_acceso(): void
    {
        $user = User::factory()->conRol()->bloqueado()->create();

        $this->assertTrue($user->estaBloqueado());

        $user->desbloquear();

        $this->assertFalse($user->estaBloqueado());
        $this->assertSame(0, $user->intentos_fallidos);
        $this->assertTrue($user->canAccessPanel($this->panel()));
    }

    public function test_reset_de_intentos_tras_login_exitoso(): void
    {
        $user = User::factory()->conRol()->create(['intentos_fallidos' => 2]);

        $user->resetearIntentosFallidos();

        $this->assertSame(0, $user->fresh()->intentos_fallidos);
    }
}
