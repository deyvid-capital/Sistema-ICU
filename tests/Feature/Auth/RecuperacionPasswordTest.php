<?php

namespace Tests\Feature\Auth;

use App\Filament\Pages\Auth\RequestPasswordReset;
use App\Filament\Pages\Auth\ResetPassword;
use App\Models\PasswordResetChallenge;
use App\Models\User;
use App\Notifications\CodigoRecuperacionPassword;
use App\Notifications\CriticalEventNotification;
use App\Support\Auth\RecuperacionPassword;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RecuperacionPasswordTest extends TestCase
{
    use RefreshDatabase;

    private RecuperacionPassword $recuperacion;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();

        $this->recuperacion = app(RecuperacionPassword::class);
    }

    /**
     * @return array{0: User, 1: User}
     */
    private function crearAdministradores(): array
    {
        Role::findOrCreate('super_admin', 'web');
        Role::findOrCreate('admin', 'web');

        $superAdmin = User::factory()->create()->assignRole('super_admin');
        $admin = User::factory()->create()->assignRole('admin');

        return [$superAdmin, $admin];
    }

    public function test_correo_desconocido_no_envia_codigo_ni_avisa_admins(): void
    {
        Notification::fake();
        [$superAdmin, $admin] = $this->crearAdministradores();

        $this->recuperacion->solicitar('nadie@example.com', '127.0.0.1');

        Notification::assertNothingSentTo($superAdmin);
        Notification::assertNothingSentTo($admin);
        $this->assertDatabaseCount(PasswordResetChallenge::class, 0);
    }

    public function test_usuario_valido_recibe_codigo_alfanumerico_y_se_notifica_a_admins(): void
    {
        Notification::fake();
        [$superAdmin, $admin] = $this->crearAdministradores();
        $usuario = User::factory()->conRol('operador')->create();

        $this->recuperacion->solicitar($usuario->email, '10.0.0.8');

        $codigo = null;

        Notification::assertSentTo($usuario, CodigoRecuperacionPassword::class, function (CodigoRecuperacionPassword $notification) use (&$codigo): bool {
            $codigo = $notification->codigo;

            return strlen($codigo) === 8
                && (bool) preg_match('/^[A-Z0-9]+$/', $codigo)
                && (bool) preg_match('/[A-Z]/', $codigo)
                && (bool) preg_match('/[0-9]/', $codigo);
        });

        $this->assertNotNull($codigo);

        Notification::assertSentTo($superAdmin, CriticalEventNotification::class, function (CriticalEventNotification $notification) use ($usuario, $codigo): bool {
            return str_contains($notification->title, 'Solicitud de restablecimiento')
                && str_contains($notification->body, $usuario->email)
                && ! str_contains($notification->body, (string) $codigo);
        });

        Notification::assertSentTo($admin, CriticalEventNotification::class);
        Notification::assertNotSentTo($usuario, CriticalEventNotification::class);

        $this->assertNotNull($codigo);
        $this->assertDatabaseHas(PasswordResetChallenge::class, ['email' => $usuario->email]);
        $this->assertTrue(Hash::check($codigo, PasswordResetChallenge::query()->where('email', $usuario->email)->value('code_hash')));
    }

    public function test_reenvio_inmediato_no_genera_otro_correo(): void
    {
        Notification::fake();
        $this->crearAdministradores();
        $usuario = User::factory()->conRol('operador')->create();

        $this->recuperacion->solicitar($usuario->email);
        Notification::assertSentToTimes($usuario, CodigoRecuperacionPassword::class, 1);

        $this->recuperacion->solicitar($usuario->email);
        Notification::assertSentToTimes($usuario, CodigoRecuperacionPassword::class, 1);
    }

    public function test_codigo_correcto_cambia_password_desbloquea_y_avisa_admins(): void
    {
        Notification::fake();
        [$superAdmin] = $this->crearAdministradores();
        $usuario = User::factory()->conRol('operador')->bloqueado()->create();

        $this->recuperacion->solicitar($usuario->email);

        $codigo = null;
        Notification::assertSentTo($usuario, CodigoRecuperacionPassword::class, function (CodigoRecuperacionPassword $notification) use (&$codigo): bool {
            $codigo = $notification->codigo;

            return true;
        });

        $resultado = $this->recuperacion->restablecer($usuario->email, $this->recuperacion->formatearCodigo((string) $codigo), 'NuevaPass1');

        $this->assertTrue($resultado->ok);
        $this->assertTrue(Hash::check('NuevaPass1', $usuario->fresh()->password));
        $this->assertFalse($usuario->fresh()->estaBloqueado());
        $this->assertDatabaseCount(PasswordResetChallenge::class, 0);

        Notification::assertSentTo($superAdmin, CriticalEventNotification::class, function (CriticalEventNotification $notification): bool {
            return str_contains($notification->title, 'Contraseña restablecida');
        });
    }

    public function test_codigo_incorrecto_agota_intentos_bloquea_y_avisa_admins(): void
    {
        Notification::fake();
        config(['acceso.recuperacion.max_intentos_codigo' => 3]);
        config(['acceso.recuperacion.bloqueo_minutos' => 15]);

        [$superAdmin] = $this->crearAdministradores();
        $usuario = User::factory()->conRol('operador')->create();

        $this->recuperacion->solicitar($usuario->email);

        $this->recuperacion->restablecer($usuario->email, 'XXXX0000', 'NuevaPass1');
        $this->recuperacion->restablecer($usuario->email, 'XXXX0000', 'NuevaPass1');
        $resultado = $this->recuperacion->restablecer($usuario->email, 'XXXX0000', 'NuevaPass1');

        $this->assertFalse($resultado->ok);
        $this->assertTrue($resultado->bloqueado);
        $this->assertTrue(Hash::check('12345678', $usuario->fresh()->password));

        $desafio = PasswordResetChallenge::query()->where('email', $usuario->email)->first();
        $this->assertNotNull($desafio);
        $this->assertTrue($desafio->estaBloqueado());
        $this->assertSame('', $desafio->code_hash);

        Notification::assertSentTo($superAdmin, CriticalEventNotification::class, function (CriticalEventNotification $notification): bool {
            return str_contains($notification->title, 'Código de recuperación incorrecto');
        });

        Notification::fake();
        $this->recuperacion->solicitar($usuario->email);
        Notification::assertNotSentTo($usuario, CodigoRecuperacionPassword::class);
    }

    public function test_codigo_caducado_no_restablece(): void
    {
        Notification::fake();
        $this->crearAdministradores();
        $usuario = User::factory()->conRol('operador')->create();

        $this->recuperacion->solicitar($usuario->email);

        PasswordResetChallenge::query()->where('email', $usuario->email)->update([
            'expires_at' => now()->subMinute(),
        ]);

        $resultado = $this->recuperacion->restablecer($usuario->email, 'ABCD2345', 'NuevaPass1');

        $this->assertFalse($resultado->ok);
        $this->assertTrue(Hash::check('12345678', $usuario->fresh()->password));
    }

    public function test_correo_de_codigo_es_claro_y_muestra_el_otp(): void
    {
        $usuario = User::factory()->create(['name' => 'Ana Pérez']);
        $expira = now()->addMinutes(10);
        $mail = (new CodigoRecuperacionPassword('AB3KM7PQ', 10, $expira, 'https://example.test/reset'))
            ->toMail($usuario);

        $html = $mail->render();

        $this->assertStringContainsString('Ana Pérez', $html);
        $this->assertStringContainsString('AB3K-M7PQ', $html);
        $this->assertStringContainsString('10 minutos', $html);
        $this->assertStringContainsString('Ingresar el código', $html);
        $this->assertStringContainsString('Si no fuiste tú', $html);
    }

    public function test_pagina_de_solicitud_redirige_con_mensaje_generico(): void
    {
        Notification::fake();
        $this->crearAdministradores();
        $usuario = User::factory()->conRol('operador')->create();

        Livewire::test(RequestPasswordReset::class)
            ->fillForm(['email' => $usuario->email])
            ->call('request')
            ->assertNotified()
            ->assertRedirect();

        Notification::assertSentTo($usuario, CodigoRecuperacionPassword::class);
    }

    public function test_pagina_de_reset_acepta_el_codigo_y_la_nueva_contrasena(): void
    {
        Notification::fake();
        $this->crearAdministradores();
        $usuario = User::factory()->conRol('operador')->create();

        $this->recuperacion->solicitar($usuario->email);

        $codigo = null;
        Notification::assertSentTo($usuario, CodigoRecuperacionPassword::class, function (CodigoRecuperacionPassword $notification) use (&$codigo): bool {
            $codigo = $notification->codigo;

            return true;
        });

        Livewire::test(ResetPassword::class, ['email' => $usuario->email, 'token' => 'otp'])
            ->fillForm([
                'code' => $codigo,
                'password' => 'NuevaPass1',
                'passwordConfirmation' => 'NuevaPass1',
            ])
            ->call('resetPassword')
            ->assertNotified()
            ->assertRedirect();

        $this->assertTrue(Hash::check('NuevaPass1', $usuario->fresh()->password));
    }
}
