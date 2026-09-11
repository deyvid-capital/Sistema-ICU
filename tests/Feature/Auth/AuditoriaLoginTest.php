<?php

namespace Tests\Feature\Auth;

use App\Enums\DominioActividad;
use App\Enums\EventoSeguridad;
use App\Filament\Pages\Auth\Login;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AuditoriaLoginTest extends TestCase
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

    private function intentar(string $email, string $password): void
    {
        Livewire::test(Login::class)
            ->fillForm(['email' => $email, 'password' => $password])
            ->call('authenticate');
    }

    public function test_login_exitoso_registra_ingreso(): void
    {
        $usuario = User::factory()->conRol()->create();

        $this->intentar($usuario->email, '12345678');

        $eventos = $this->eventos(EventoSeguridad::IngresoExitoso);

        $this->assertCount(1, $eventos);
        $this->assertSame((string) $usuario->id, (string) $eventos->first()->causer_id);
        $this->assertSame($usuario->email, $eventos->first()->properties['email']);
        $this->assertNotNull($eventos->first()->properties['ip']);
    }

    public function test_password_incorrecta_registra_intentos_restantes(): void
    {
        config(['acceso.max_intentos_fallidos' => 3]);
        $usuario = User::factory()->conRol()->create();

        $this->intentar($usuario->email, 'incorrecta');

        $eventos = $this->eventos(EventoSeguridad::PasswordIncorrecta);

        $this->assertCount(1, $eventos);
        $this->assertSame(2, $eventos->first()->properties['intentos_restantes']);
        $this->assertSame((string) $usuario->id, (string) $eventos->first()->subject_id);
    }

    public function test_un_solo_fallo_produce_una_sola_fila(): void
    {
        $usuario = User::factory()->conRol()->create();

        $this->intentar($usuario->email, 'incorrecta');

        // Si además se escuchara el evento Failed, aquí habría dos registros.
        $this->assertCount(1, $this->eventos());
    }

    public function test_correo_desconocido_se_registra_sin_causer_con_el_correo_tecleado(): void
    {
        $this->intentar('intruso@example.com', 'loquesea');

        $eventos = $this->eventos(EventoSeguridad::CorreoDesconocido);

        $this->assertCount(1, $eventos);
        $this->assertNull($eventos->first()->causer_id);
        $this->assertNull($eventos->first()->subject_id);
        $this->assertSame('intruso@example.com', $eventos->first()->properties['email']);
    }

    public function test_acceso_deshabilitado_registra_el_motivo(): void
    {
        $usuario = User::factory()->conRol()->sinAcceso()->create();

        $this->intentar($usuario->email, '12345678');

        $eventos = $this->eventos(EventoSeguridad::AccesoDenegado);

        $this->assertCount(1, $eventos);
        $this->assertNotEmpty($eventos->first()->properties['motivo']);
        $this->assertSame((string) $usuario->id, (string) $eventos->first()->subject_id);
        $this->assertCount(0, $this->eventos(EventoSeguridad::PasswordIncorrecta));
    }

    public function test_acceso_vencido_registra_el_motivo(): void
    {
        $usuario = User::factory()->conRol()->accesoDefinido(now()->subDay())->create();

        $this->intentar($usuario->email, '12345678');

        $this->assertCount(1, $this->eventos(EventoSeguridad::AccesoDenegado));
    }

    public function test_usuario_sin_rol_registra_el_motivo(): void
    {
        $usuario = User::factory()->create();

        $this->intentar($usuario->email, '12345678');

        $eventos = $this->eventos(EventoSeguridad::AccesoDenegado);

        $this->assertCount(1, $eventos);
        $this->assertNotEmpty($eventos->first()->properties['motivo']);
    }

    public function test_cuenta_ya_bloqueada_registra_el_intento(): void
    {
        $usuario = User::factory()->conRol()->bloqueado()->create();

        $this->intentar($usuario->email, '12345678');

        $eventos = $this->eventos(EventoSeguridad::IntentoConCuentaBloqueada);

        $this->assertCount(1, $eventos);
        $this->assertSame($usuario->email, $eventos->first()->properties['email']);
    }

    public function test_agotar_intentos_registra_password_incorrecta_y_cuenta_bloqueada(): void
    {
        config(['acceso.max_intentos_fallidos' => 2]);
        $usuario = User::factory()->conRol()->create();

        $componente = Livewire::test(Login::class)
            ->fillForm(['email' => $usuario->email, 'password' => 'incorrecta']);

        $componente->call('authenticate');
        $componente->call('authenticate');

        $this->assertCount(2, $this->eventos(EventoSeguridad::PasswordIncorrecta));
        $this->assertCount(1, $this->eventos(EventoSeguridad::CuentaBloqueada));
        $this->assertSame(0, $this->eventos(EventoSeguridad::PasswordIncorrecta)->last()->properties['intentos_restantes']);
    }

    public function test_cerrar_sesion_registra_el_evento(): void
    {
        $usuario = User::factory()->conRol()->create();

        $this->actingAs($usuario);
        auth()->logout();

        $eventos = $this->eventos(EventoSeguridad::CierreSesion);

        $this->assertCount(1, $eventos);
        $this->assertSame((string) $usuario->id, (string) $eventos->first()->causer_id);
    }
}
