<?php

namespace Tests\Feature\Firma;

use App\Enums\EstadoCertificadoFirma;
use App\Enums\EventoSolicitudFirma;
use App\Models\CertificadoFirma;
use App\Models\SolicitudFirma;
use App\Models\User;
use App\Notifications\Firma\CertificadoPorVencerNotification;
use App\Notifications\Firma\FirmaActivacionPendienteNotification;
use App\Notifications\Firma\FirmaRequiereReactivacionNotification;
use App\Notifications\Firma\FirmaSolicitadaNotification;
use App\Notifications\Firma\SolicitudFirmaNotification;
use App\Services\Firma\AbrirCredencialesFirma;
use App\Services\Firma\CancelarSolicitudFirma;
use App\Services\Firma\FirmarDocumento;
use App\Services\Firma\FirmarLote;
use App\Services\Firma\RechazarFirma;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\Feature\Firma\Concerns\FakeFirmador;
use Tests\Feature\Firma\Concerns\PreparaEnviosEnCurso;
use Tests\TestCase;

class NotificacionesFirmaTest extends TestCase
{
    use FakeFirmador;
    use PreparaEnviosEnCurso;
    use RefreshDatabase;

    private User $creador;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Notification::fake();
        $this->fakeFirmador();

        // Miércoles 10:00 en Bolivia: dentro del horario hábil.
        $this->travelTo(Carbon::parse('2026-10-28 10:00', 'America/La_Paz'));

        $this->creador = User::factory()->create(['name' => 'Ana Creadora']);
    }

    private function firmar(User $usuario, SolicitudFirma $solicitud, int $documento = 0): void
    {
        app(FirmarDocumento::class)(
            $this->firmaDe($solicitud, $usuario, $documento),
            $usuario,
            app(AbrirCredencialesFirma::class)($usuario, self::CONTRASENA_SISTEMA),
        );
    }

    private function apagarPreferencia(User $usuario, string $canal): void
    {
        $usuario->notificationPreferences()->create([
            'tipo_evento' => 'firma_digital',
            'canal' => $canal,
            'habilitado' => false,
        ]);
    }

    /**
     * @return Collection<int, SolicitudFirmaNotification>
     */
    private function avisosDeEnvio(User $usuario, EventoSolicitudFirma $evento)
    {
        return Notification::sent($usuario, SolicitudFirmaNotification::class)
            ->filter(fn (SolicitudFirmaNotification $aviso): bool => $aviso->evento === $evento)
            ->values();
    }

    // --- Preferencia "Firma digital" -------------------------------------------

    public function test_con_el_correo_de_firma_apagado_el_aviso_llega_solo_a_la_campana(): void
    {
        $juanito = $this->firmanteConFirma('Juanito');
        $this->apagarPreferencia($juanito, 'mail');

        $this->envioEnCurso($this->creador, [$juanito]);

        Notification::assertSentTo($juanito, FirmaSolicitadaNotification::class, fn ($aviso, array $canales): bool => $canales === ['database']);
    }

    public function test_con_los_dos_canales_apagados_no_le_llega_nada_pero_puede_firmar(): void
    {
        $juanito = $this->firmanteConFirma('Juanito');
        $this->apagarPreferencia($juanito, 'mail');
        $this->apagarPreferencia($juanito, 'database');

        $solicitud = $this->envioEnCurso($this->creador, [$juanito]);

        Notification::assertNotSentTo($juanito, FirmaSolicitadaNotification::class);
        $this->assertSame(1, $solicitud->firmasPendientesDe($juanito)->count());
    }

    public function test_la_preferencia_tambien_aplica_a_activacion_reactivacion_y_vencimiento(): void
    {
        $usuario = User::factory()->create();
        $this->apagarPreferencia($usuario, 'mail');

        $this->assertSame(['database'], (new FirmaActivacionPendienteNotification)->via($usuario));
        $this->assertSame(['database'], FirmaRequiereReactivacionNotification::paraTitular('prueba')->via($usuario));
        $this->assertSame(['database'], (new CertificadoPorVencerNotification('X', '01/01/2027', 30))->via($usuario));
        $this->assertSame(['database', 'mail'], (new FirmaActivacionPendienteNotification)->via(User::factory()->create()));
    }

    // --- Avance del envío -------------------------------------------------------

    public function test_cada_firma_avisa_al_creador_solo_por_la_campana(): void
    {
        $juanito = $this->firmanteConFirma('Juanito');
        $carlos = $this->firmanteConFirma('Carlos');
        $solicitud = $this->envioEnCurso($this->creador, [$juanito, $carlos]);

        $this->firmar($juanito, $solicitud);

        $avisos = $this->avisosDeEnvio($this->creador, EventoSolicitudFirma::Realizada);
        $this->assertCount(1, $avisos);
        $this->assertSame('Juanito firmó en '.$solicitud->codigo, $avisos[0]->titulo);
        $this->assertStringContainsString('Van 1 de 2 firmas', $avisos[0]->cuerpo);
        $this->assertSame(['database'], $avisos[0]->via($this->creador));
    }

    public function test_al_terminar_se_avisa_al_creador_y_a_todos_los_firmantes(): void
    {
        $juanito = $this->firmanteConFirma('Juanito');
        $carlos = $this->firmanteConFirma('Carlos');
        $solicitud = $this->envioEnCurso($this->creador, [$juanito, $carlos]);

        $this->firmar($juanito, $solicitud);
        $this->firmar($carlos, $solicitud);

        foreach ([$this->creador, $juanito, $carlos] as $usuario) {
            $avisos = $this->avisosDeEnvio($usuario, EventoSolicitudFirma::Finalizada);
            $this->assertCount(1, $avisos);
            $this->assertSame('Envío completado: '.$solicitud->codigo, $avisos[0]->titulo);
            $this->assertSame(['database', 'mail'], $avisos[0]->via($usuario));
        }

        // La última firma no genera además un "firmó": basta con el de finalizado.
        $this->assertCount(1, $this->avisosDeEnvio($this->creador, EventoSolicitudFirma::Realizada));
    }

    public function test_firmar_todos_avisa_una_sola_vez_al_creador(): void
    {
        $juanito = $this->firmanteConFirma('Juanito');
        $carlos = $this->firmanteConFirma('Carlos');
        $solicitud = $this->envioEnCurso($this->creador, [$juanito, $carlos], documentos: 2);

        app(FirmarLote::class)($solicitud, $juanito, app(AbrirCredencialesFirma::class)($juanito, self::CONTRASENA_SISTEMA));

        $avisos = $this->avisosDeEnvio($this->creador, EventoSolicitudFirma::Realizada);
        $this->assertCount(1, $avisos);
        $this->assertStringContainsString('Firmó 2 documentos', $avisos[0]->cuerpo);
    }

    public function test_el_creador_que_firma_no_se_avisa_a_si_mismo(): void
    {
        $ana = $this->firmanteConFirma('Ana');
        $carlos = $this->firmanteConFirma('Carlos');
        $solicitud = $this->envioEnCurso($ana, [$ana, $carlos]);

        $this->firmar($ana, $solicitud);

        $this->assertCount(0, $this->avisosDeEnvio($ana, EventoSolicitudFirma::Realizada));
    }

    public function test_rechazar_avisa_al_creador_y_a_los_demas_firmantes_del_documento(): void
    {
        $juanito = $this->firmanteConFirma('Juanito');
        $carlos = $this->firmanteConFirma('Carlos');
        $solicitud = $this->envioEnCurso($this->creador, [$juanito, $carlos], documentos: 2);

        app(RechazarFirma::class)($this->firmaDe($solicitud, $juanito), $juanito, 'Faltan datos');

        foreach ([$this->creador, $carlos] as $usuario) {
            $avisos = $this->avisosDeEnvio($usuario, EventoSolicitudFirma::Rechazada);
            $this->assertCount(1, $avisos);
            $this->assertSame('Motivo: Faltan datos', $avisos[0]->cuerpo);
        }

        $this->assertCount(0, $this->avisosDeEnvio($juanito, EventoSolicitudFirma::Rechazada));
        $this->assertCount(0, $this->avisosDeEnvio($this->creador, EventoSolicitudFirma::Finalizada));
    }

    public function test_si_el_rechazo_cierra_el_ultimo_documento_se_avisa_el_final_con_rechazos(): void
    {
        $juanito = $this->firmanteConFirma('Juanito');
        $solicitud = $this->envioEnCurso($this->creador, [$juanito]);

        app(RechazarFirma::class)($this->firmaDe($solicitud, $juanito), $juanito, 'No corresponde');

        $avisos = $this->avisosDeEnvio($this->creador, EventoSolicitudFirma::Finalizada);
        $this->assertCount(1, $avisos);
        $this->assertSame('Envío finalizado con rechazos: '.$solicitud->codigo, $avisos[0]->titulo);
    }

    public function test_cancelar_avisa_solo_a_quienes_tenian_firmas_pendientes(): void
    {
        $juanito = $this->firmanteConFirma('Juanito');
        $carlos = $this->firmanteConFirma('Carlos');
        $solicitud = $this->envioEnCurso($this->creador, [$juanito, $carlos]);
        $this->firmar($juanito, $solicitud);

        app(CancelarSolicitudFirma::class)($solicitud, $this->creador, 'Se rehace el acta');

        $avisos = $this->avisosDeEnvio($carlos, EventoSolicitudFirma::Cancelada);
        $this->assertCount(1, $avisos);
        $this->assertStringContainsString('Se rehace el acta', $avisos[0]->cuerpo);
        $this->assertSame(['database'], $avisos[0]->via($carlos));
        $this->assertCount(0, $this->avisosDeEnvio($juanito, EventoSolicitudFirma::Cancelada));
    }

    // --- Recordatorios ----------------------------------------------------------

    private function recordatoriosA(User $usuario)
    {
        return Notification::sent($usuario, FirmaSolicitadaNotification::class)
            ->filter(fn (FirmaSolicitadaNotification $aviso): bool => $aviso->esRecordatorio)
            ->values();
    }

    public function test_recuerda_a_los_pendientes_una_vez_por_intervalo(): void
    {
        $juanito = $this->firmanteConFirma('Juanito');
        $carlos = $this->firmanteConFirma('Carlos');
        $solicitud = $this->envioEnCurso($this->creador, [$juanito, $carlos]);
        $solicitud->update(['recordatorio_cada_horas' => 24]);
        $this->firmar($carlos, $solicitud);

        $this->travel(23)->hours();
        $this->artisan('firmas:enviar-recordatorios')->assertSuccessful();
        $this->assertCount(0, $this->recordatoriosA($juanito));

        $this->travel(1)->hours();
        $this->artisan('firmas:enviar-recordatorios')->assertSuccessful();
        $this->artisan('firmas:enviar-recordatorios')->assertSuccessful();

        $this->assertCount(1, $this->recordatoriosA($juanito));
        $this->assertCount(0, $this->recordatoriosA($carlos));
        $this->assertFalse($this->recordatoriosA($juanito)[0]->vencido);
    }

    public function test_sin_recordatorio_configurado_no_se_recuerda(): void
    {
        $juanito = $this->firmanteConFirma('Juanito');
        $solicitud = $this->envioEnCurso($this->creador, [$juanito]);
        $solicitud->update(['recordatorio_cada_horas' => null]);

        $this->travel(3)->days();
        $this->artisan('firmas:enviar-recordatorios')->assertSuccessful();

        $this->assertCount(0, $this->recordatoriosA($juanito));
    }

    public function test_fuera_del_horario_habil_no_se_recuerda(): void
    {
        $juanito = $this->firmanteConFirma('Juanito');
        $solicitud = $this->envioEnCurso($this->creador, [$juanito]);
        $solicitud->update(['recordatorio_cada_horas' => 1]);

        $this->travelTo(Carbon::parse('2026-10-31 10:00', 'America/La_Paz')); // sábado
        $this->artisan('firmas:enviar-recordatorios')->assertSuccessful();

        $this->travelTo(Carbon::parse('2026-11-02 20:30', 'America/La_Paz')); // lunes de noche
        $this->artisan('firmas:enviar-recordatorios')->assertSuccessful();

        $this->assertCount(0, $this->recordatoriosA($juanito));

        $this->artisan('firmas:enviar-recordatorios', ['--fuera-de-horario' => true])->assertSuccessful();
        $this->assertCount(1, $this->recordatoriosA($juanito));
    }

    public function test_con_orden_solo_se_recuerda_a_quien_le_toca_y_el_turno_reinicia_el_intervalo(): void
    {
        $pedro = $this->firmanteConFirma('Pedro');
        $carlos = $this->firmanteConFirma('Carlos');
        $solicitud = $this->envioEnCurso($this->creador, [$pedro, $carlos], ordenObligatorio: true);
        $solicitud->update(['recordatorio_cada_horas' => 4]);

        $this->travel(4)->hours();
        $this->artisan('firmas:enviar-recordatorios')->assertSuccessful();
        $this->assertCount(1, $this->recordatoriosA($pedro));
        $this->assertCount(0, $this->recordatoriosA($carlos));

        $this->firmar($pedro, $solicitud);
        $this->travel(2)->hours();
        $this->artisan('firmas:enviar-recordatorios')->assertSuccessful();

        $this->assertCount(0, $this->recordatoriosA($carlos), 'Carlos recibió el aviso de su turno hace 2 horas.');
    }

    public function test_con_el_plazo_vencido_el_recordatorio_se_marca_y_se_copia_al_creador(): void
    {
        $juanito = $this->firmanteConFirma('Juanito');
        $solicitud = $this->envioEnCurso($this->creador, [$juanito]);
        $solicitud->update(['recordatorio_cada_horas' => 24, 'fecha_limite' => '2026-10-28']);

        $this->travelTo(Carbon::parse('2026-10-29 10:00', 'America/La_Paz'));
        $this->artisan('firmas:enviar-recordatorios')->assertSuccessful();

        $this->assertTrue($this->recordatoriosA($juanito)[0]->vencido);

        $copias = $this->avisosDeEnvio($this->creador, EventoSolicitudFirma::Vencida);
        $this->assertCount(1, $copias);
        $this->assertStringContainsString('Juanito', $copias[0]->cuerpo);
        $this->assertStringContainsString('28/10/2026', $copias[0]->cuerpo);
    }

    public function test_el_dia_limite_todavia_no_esta_vencido(): void
    {
        $juanito = $this->firmanteConFirma('Juanito');
        $solicitud = $this->envioEnCurso($this->creador, [$juanito]);
        $solicitud->update(['recordatorio_cada_horas' => 24, 'fecha_limite' => '2026-10-29']);

        $this->travelTo(Carbon::parse('2026-10-29 18:00', 'America/La_Paz'));
        $this->artisan('firmas:enviar-recordatorios')->assertSuccessful();

        $this->assertFalse($this->recordatoriosA($juanito)[0]->vencido);
        $this->assertCount(0, $this->avisosDeEnvio($this->creador, EventoSolicitudFirma::Vencida));
    }

    // --- Vencimiento del certificado -------------------------------------------

    public function test_avisa_a_los_30_15_y_7_dias_una_vez_cada_uno(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']));
        $titular = $this->titular(['name' => 'Juanito']);
        $certificado = $this->certificadoActivo($titular, ['vigente_hasta' => now()->addDays(30)]);

        $this->artisan('firmas:avisar-vencimientos')->assertSuccessful();
        $this->artisan('firmas:avisar-vencimientos')->assertSuccessful();

        Notification::assertSentToTimes($titular, CertificadoPorVencerNotification::class, 1);
        Notification::assertSentTo($admin, CertificadoPorVencerNotification::class, fn ($aviso): bool => $aviso->paraAdministrador && $aviso->dias === 30);
        $this->assertSame(30, $certificado->refresh()->aviso_vencimiento_dias);

        $this->travel(10)->days(); // 20 días: aún no toca el siguiente
        $this->artisan('firmas:avisar-vencimientos')->assertSuccessful();
        Notification::assertSentToTimes($titular, CertificadoPorVencerNotification::class, 1);

        $this->travel(5)->days(); // 15 días
        $this->artisan('firmas:avisar-vencimientos')->assertSuccessful();
        $this->travel(8)->days(); // 7 días
        $this->artisan('firmas:avisar-vencimientos')->assertSuccessful();

        Notification::assertSentToTimes($titular, CertificadoPorVencerNotification::class, 3);
        $this->assertSame([30, 15, 7], Notification::sent($titular, CertificadoPorVencerNotification::class)->pluck('dias')->all());
    }

    public function test_no_avisa_lejos_del_vencimiento_ni_si_ya_hay_renovacion_pendiente(): void
    {
        $lejano = $this->titular();
        $this->certificadoActivo($lejano, ['vigente_hasta' => now()->addDays(90)]);

        $renovado = $this->titular();
        $actual = $this->certificadoActivo($renovado, ['vigente_hasta' => now()->addDays(10)]);
        CertificadoFirma::factory()->create([
            'usuario_id' => $renovado->id,
            'estado' => EstadoCertificadoFirma::PendienteActivacion,
            'reemplaza_a_id' => $actual->id,
        ]);

        $this->artisan('firmas:avisar-vencimientos')->assertSuccessful();

        Notification::assertNotSentTo([$lejano, $renovado], CertificadoPorVencerNotification::class);
    }

    public function test_el_scheduler_programa_las_dos_tareas(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('firmas:enviar-recordatorios')
            ->expectsOutputToContain('firmas:avisar-vencimientos')
            ->assertSuccessful();
    }
}
