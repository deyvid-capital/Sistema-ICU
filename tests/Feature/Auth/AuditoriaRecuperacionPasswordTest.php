<?php

namespace Tests\Feature\Auth;

use App\Enums\DominioActividad;
use App\Enums\EventoSeguridad;
use App\Models\User;
use App\Notifications\CodigoRecuperacionPassword;
use App\Support\Auth\RecuperacionPassword;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AuditoriaRecuperacionPasswordTest extends TestCase
{
    use RefreshDatabase;

    private RecuperacionPassword $recuperacion;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();

        $this->recuperacion = app(RecuperacionPassword::class);
    }

    /**
     * @return Collection<int, Activity>
     */
    private function eventos(?EventoSeguridad $evento = null): Collection
    {
        return Activity::query()
            ->where('log_name', DominioActividad::Seguridad->value)
            ->when($evento !== null, fn ($query) => $query->where('event', $evento->value))
            ->orderBy('id')
            ->get();
    }

    public function test_solicitud_de_codigo_se_registra_con_ip(): void
    {
        $usuario = User::factory()->conRol('operador')->create();

        $this->recuperacion->solicitar($usuario->email, '203.0.113.9');

        $eventos = $this->eventos(EventoSeguridad::OtpSolicitado);

        $this->assertCount(1, $eventos);
        $this->assertSame($usuario->email, $eventos->first()->properties['email']);
        $this->assertSame('203.0.113.9', $eventos->first()->properties['ip']);
        $this->assertSame((string) $usuario->id, (string) $eventos->first()->subject_id);
    }

    public function test_correo_desconocido_no_registra_solicitud(): void
    {
        $this->recuperacion->solicitar('nadie@example.com', '203.0.113.9');

        $this->assertCount(0, $this->eventos(EventoSeguridad::OtpSolicitado));
    }

    public function test_el_cooldown_de_reenvio_no_registra_una_segunda_solicitud(): void
    {
        $usuario = User::factory()->conRol('operador')->create();

        $this->recuperacion->solicitar($usuario->email);
        $this->recuperacion->solicitar($usuario->email);

        $this->assertCount(1, $this->eventos(EventoSeguridad::OtpSolicitado));
    }

    public function test_codigo_incorrecto_registra_intentos_restantes_y_no_guarda_el_codigo(): void
    {
        config(['acceso.recuperacion.max_intentos_codigo' => 3]);
        $usuario = User::factory()->conRol('operador')->create();

        $this->recuperacion->solicitar($usuario->email);
        $this->recuperacion->restablecer($usuario->email, 'XXXX0000', 'NuevaPass1');

        $eventos = $this->eventos(EventoSeguridad::OtpIncorrecto);

        $this->assertCount(1, $eventos);
        $this->assertSame(2, $eventos->first()->properties['intentos_restantes']);
        $this->assertSame((string) $usuario->id, (string) $eventos->first()->subject_id);
        $this->assertStringNotContainsString('XXXX0000', json_encode($eventos->first()->properties->all()));
    }

    public function test_agotar_intentos_registra_bloqueo_con_fecha(): void
    {
        config(['acceso.recuperacion.max_intentos_codigo' => 2]);
        config(['acceso.recuperacion.bloqueo_minutos' => 15]);
        $usuario = User::factory()->conRol('operador')->create();

        $this->recuperacion->solicitar($usuario->email);
        $this->recuperacion->restablecer($usuario->email, 'XXXX0000', 'NuevaPass1');
        $this->recuperacion->restablecer($usuario->email, 'XXXX0000', 'NuevaPass1');

        $bloqueos = $this->eventos(EventoSeguridad::OtpBloqueado);

        $this->assertCount(1, $bloqueos);
        $this->assertCount(1, $this->eventos(EventoSeguridad::OtpIncorrecto));
        $this->assertNotNull($bloqueos->first()->properties['bloqueado_hasta']);
        $this->assertSame($usuario->email, $bloqueos->first()->properties['email']);
    }

    public function test_codigo_caducado_no_registra_intento_fallido(): void
    {
        config(['acceso.recuperacion.codigo_ttl_minutos' => 10]);
        $usuario = User::factory()->conRol('operador')->create();

        $this->recuperacion->solicitar($usuario->email);

        $this->travel(11)->minutes();

        $this->recuperacion->restablecer($usuario->email, 'XXXX0000', 'NuevaPass1');

        // El código ya no está vigente: no consume intentos, así que tampoco
        // tiene sentido registrar un fallo con intentos restantes.
        $this->assertCount(0, $this->eventos(EventoSeguridad::OtpIncorrecto));
    }

    public function test_restablecimiento_exitoso_se_registra(): void
    {
        $usuario = User::factory()->conRol('operador')->create();

        $codigo = null;
        Notification::assertNothingSent();
        $this->recuperacion->solicitar($usuario->email);

        Notification::assertSentTo($usuario, CodigoRecuperacionPassword::class, function ($notificacion) use (&$codigo): bool {
            $codigo = $notificacion->codigo;

            return true;
        });

        $resultado = $this->recuperacion->restablecer($usuario->email, $codigo, 'NuevaPass1');

        $this->assertTrue($resultado->ok);

        $eventos = $this->eventos(EventoSeguridad::PasswordRestablecida);

        $this->assertCount(1, $eventos);
        $this->assertSame((string) $usuario->id, (string) $eventos->first()->causer_id);
    }
}
