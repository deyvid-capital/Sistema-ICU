<?php

namespace Tests\Feature\Auditoria;

use App\Enums\DominioActividad;
use App\Enums\EventoSeguridad;
use App\Models\User;
use App\Support\Auditoria\AuditorSeguridad;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AuditorSeguridadTest extends TestCase
{
    use RefreshDatabase;

    private function auditor(): AuditorSeguridad
    {
        return app(AuditorSeguridad::class);
    }

    private function ultimaActividad(): Activity
    {
        return Activity::query()->latest('id')->firstOrFail();
    }

    public function test_registra_dominio_evento_descripcion_y_sujeto(): void
    {
        $usuario = User::factory()->create();

        $this->auditor()->ingresoExitoso($usuario);

        $actividad = $this->ultimaActividad();

        $this->assertSame(DominioActividad::Seguridad->value, $actividad->log_name);
        $this->assertSame(EventoSeguridad::IngresoExitoso->value, $actividad->event);
        $this->assertSame(EventoSeguridad::IngresoExitoso->getLabel(), $actividad->description);
        $this->assertSame(User::class, $actividad->subject_type);
        $this->assertSame((string) $usuario->id, (string) $actividad->subject_id);
        $this->assertSame((string) $usuario->id, (string) $actividad->causer_id);
        $this->assertSame($usuario->email, $actividad->properties['email']);
    }

    public function test_nunca_persiste_contrasenas_ni_codigos(): void
    {
        $usuario = User::factory()->create();

        $this->auditor()->registrar(EventoSeguridad::OtpIncorrecto, sujeto: $usuario, propiedades: [
            'password' => 'SuperSecreta123',
            'code' => 'ABCD-2345',
            'codigo' => 'ABCD-2345',
            'code_hash' => '$2y$10$loquesea',
            'otp' => 'ABCD2345',
            'two_factor_secret' => 'JBSWY3DPEHPK3PXP',
            'intentos_restantes' => 3,
        ]);

        $propiedades = $this->ultimaActividad()->properties->all();

        foreach (['password', 'code', 'codigo', 'code_hash', 'otp', 'two_factor_secret'] as $prohibida) {
            $this->assertArrayNotHasKey($prohibida, $propiedades);
        }

        $this->assertSame(3, $propiedades['intentos_restantes']);
        $this->assertStringNotContainsString('SuperSecreta123', json_encode($propiedades));
        $this->assertStringNotContainsString('ABCD', json_encode($propiedades));
    }

    public function test_evento_sin_usuario_deja_causer_y_subject_nulos_y_guarda_el_correo(): void
    {
        $this->auditor()->correoDesconocido('intruso@example.com');

        $actividad = $this->ultimaActividad();

        $this->assertNull($actividad->subject_id);
        $this->assertNull($actividad->causer_id);
        $this->assertSame('intruso@example.com', $actividad->properties['email']);
    }

    public function test_no_atribuye_al_usuario_autenticado_un_evento_anonimo(): void
    {
        $this->actingAs(User::factory()->create());

        $this->auditor()->correoDesconocido('intruso@example.com');

        $this->assertNull(
            $this->ultimaActividad()->causer_id,
            'Sin causedByAnonymous(), ActivityLogger atribuiría el intento a la sesión activa.',
        );
    }

    public function test_normaliza_y_trunca_el_correo(): void
    {
        $this->auditor()->correoDesconocido('   MAYUSCULAS@Example.COM  ');

        $this->assertSame('mayusculas@example.com', $this->ultimaActividad()->properties['email']);

        $this->auditor()->correoDesconocido(str_repeat('a', 300).'@example.com');

        $this->assertSame(254, mb_strlen($this->ultimaActividad()->properties['email']));
    }

    public function test_captura_la_ip_y_el_navegador_de_la_peticion(): void
    {
        $usuario = User::factory()->create();

        $this->auditor()->ingresoExitoso($usuario);

        $propiedades = $this->ultimaActividad()->properties;

        $this->assertArrayHasKey('ip', $propiedades);
        $this->assertArrayHasKey('user_agent', $propiedades);
        $this->assertNotNull($propiedades['ip']);
    }

    public function test_no_registra_un_origen_falso_en_consola(): void
    {
        $usuario = User::factory()->create();

        // Simula una cola o un comando de artisan: ahí request() devuelve un
        // 127.0.0.1 y un «Symfony» inventados que no deben quedar registrados.
        $this->app['env'] = 'production';

        try {
            $this->auditor()->ingresoExitoso($usuario);
        } finally {
            $this->app['env'] = 'testing';
        }

        $propiedades = $this->ultimaActividad()->properties;

        $this->assertNull($propiedades['ip']);
        $this->assertNull($propiedades['user_agent']);
    }

    public function test_trunca_un_navegador_desmesurado(): void
    {
        $usuario = User::factory()->create();

        request()->headers->set('User-Agent', str_repeat('x', 900));

        $this->auditor()->ingresoExitoso($usuario);

        $this->assertSame(500, mb_strlen($this->ultimaActividad()->properties['user_agent']));
    }

    public function test_el_reinicio_de_dos_factores_atribuye_al_administrador(): void
    {
        $administrador = User::factory()->create();
        $objetivo = User::factory()->create();

        $this->auditor()->dosFactoresReseteado($objetivo, $administrador);

        $actividad = $this->ultimaActividad();

        $this->assertSame((string) $objetivo->id, (string) $actividad->subject_id);
        $this->assertSame((string) $administrador->id, (string) $actividad->causer_id);
        $this->assertSame($administrador->name, $actividad->properties['administrador']);
    }
}
