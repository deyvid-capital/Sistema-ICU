<?php

namespace Tests\Feature\Firma;

use App\Enums\EquipoFormularioPdfTipo;
use App\Enums\EstadoDocumentoFirma;
use App\Enums\EstadoFirma;
use App\Enums\EstadoSolicitudFirma;
use App\Enums\EventoSeguridad;
use App\Exceptions\EquipoFormularioPdfException;
use App\Exceptions\FirmaDigitalException;
use App\Models\FirmaSetting;
use App\Models\PdfEquipo;
use App\Models\SolicitudFirmaDocumento;
use App\Models\User;
use App\Notifications\CriticalEventNotification;
use App\Notifications\Firma\FirmaSolicitadaNotification;
use App\Services\Firma\AbrirCredencialesFirma;
use App\Services\Firma\AjustarFirmantesEnCurso;
use App\Services\Firma\CancelarSolicitudFirma;
use App\Services\Firma\CrearSolicitudFirma;
use App\Services\Firma\CredencialesFirma;
use App\Services\Firma\DocumentosEnFirma;
use App\Services\Firma\EnviarSolicitudFirma;
use App\Services\Firma\FirmarDocumento;
use App\Services\Firma\FirmarLote;
use App\Services\Firma\GuardarPosicionesFirma;
use App\Services\Firma\RechazarFirma;
use App\Services\Pdf\GenerateEquipoFormularioPdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\Feature\Firma\Concerns\FakeFirmador;
use Tests\Feature\Firma\Concerns\PreparaEnviosEnCurso;
use Tests\TestCase;

class FirmarDocumentoTest extends TestCase
{
    use FakeFirmador;
    use PreparaEnviosEnCurso;
    use RefreshDatabase;

    private User $creador;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
        Notification::fake();
        $this->fakeFirmador();

        $this->creador = User::factory()->create();
    }

    private function credenciales(User $usuario): CredencialesFirma
    {
        return app(AbrirCredencialesFirma::class)($usuario, self::CONTRASENA_SISTEMA);
    }

    private function firmar(User $usuario, $firma): SolicitudFirmaDocumento
    {
        return app(FirmarDocumento::class)($firma, $usuario, $this->credenciales($usuario));
    }

    // --- Firma ---------------------------------------------------------------

    public function test_firmar_guarda_una_nueva_version_y_finaliza_el_envio(): void
    {
        $juanito = $this->firmanteConFirma('Juanito');
        $solicitud = $this->envioEnCurso($this->creador, [$juanito]);
        $firma = $this->firmaDe($solicitud, $juanito);

        $documento = $this->firmar($juanito, $firma);

        $firma->refresh();
        $this->assertSame(EstadoFirma::Firmado, $firma->estado);
        $this->assertNotNull($firma->firmado_at);
        $this->assertSame($juanito->certificadoFirmaActivo->id, $firma->certificado_firma_id);

        $this->assertSame(1, $documento->version);
        $this->assertStringEndsWith('/v1.pdf', $documento->archivo_actual);
        $this->assertSame("%PDF-preparado +{$firma->campo}", Storage::disk('local')->get($documento->archivo_actual));
        $this->assertSame(hash('sha256', "%PDF-preparado +{$firma->campo}"), $documento->hash_actual);
        $this->assertSame([0, 1], $documento->versiones()->pluck('version')->all());
        $this->assertSame(EstadoDocumentoFirma::Completado, $documento->estado);
        $this->assertSame(EstadoSolicitudFirma::Finalizada, $solicitud->refresh()->estado);
    }

    public function test_en_orden_libre_cualquiera_firma_primero_y_las_firmas_se_acumulan(): void
    {
        $juanito = $this->firmanteConFirma('Juanito');
        $carlos = $this->firmanteConFirma('Carlos');
        $solicitud = $this->envioEnCurso($this->creador, [$juanito, $carlos]);

        $this->firmar($carlos, $this->firmaDe($solicitud, $carlos));
        $this->assertSame(EstadoSolicitudFirma::EnCurso, $solicitud->refresh()->estado);

        $documento = $this->firmar($juanito, $this->firmaDe($solicitud, $juanito));

        $this->assertSame(2, $documento->version);
        $contenido = Storage::disk('local')->get($documento->archivo_actual);
        $this->assertStringContainsString('+'.$this->firmaDe($solicitud, $carlos)->campo, $contenido);
        $this->assertStringContainsString('+'.$this->firmaDe($solicitud, $juanito)->campo, $contenido);
        $this->assertSame(EstadoSolicitudFirma::Finalizada, $solicitud->refresh()->estado);
    }

    public function test_con_orden_obligatorio_hay_que_esperar_el_turno_y_se_avisa_al_siguiente(): void
    {
        $pedro = $this->firmanteConFirma('Pedro');
        $carlos = $this->firmanteConFirma('Carlos');
        $solicitud = $this->envioEnCurso($this->creador, [$pedro, $carlos], ordenObligatorio: true);
        Notification::fake();

        try {
            $this->firmar($carlos, $this->firmaDe($solicitud, $carlos));
            $this->fail('Se esperaba FirmaDigitalException.');
        } catch (FirmaDigitalException $exception) {
            $this->assertSame(FirmaDigitalException::noEsTuTurno('Pedro')->getMessage(), $exception->getMessage());
        }

        $this->firmar($pedro, $this->firmaDe($solicitud, $pedro));
        Notification::assertSentTo($carlos, FirmaSolicitadaNotification::class);

        $this->firmar($carlos, $this->firmaDe($solicitud, $carlos));
        $this->assertSame(EstadoSolicitudFirma::Finalizada, $solicitud->refresh()->estado);
    }

    public function test_nadie_firma_el_recuadro_de_otro(): void
    {
        $juanito = $this->firmanteConFirma('Juanito');
        $intruso = $this->firmanteConFirma('Intruso');
        $solicitud = $this->envioEnCurso($this->creador, [$juanito]);

        $this->expectExceptionObject(FirmaDigitalException::noEsTuFirma());

        $this->firmar($intruso, $this->firmaDe($solicitud, $juanito));
    }

    public function test_no_se_firma_dos_veces(): void
    {
        $juanito = $this->firmanteConFirma('Juanito');
        $carlos = $this->firmanteConFirma('Carlos');
        $solicitud = $this->envioEnCurso($this->creador, [$juanito, $carlos]);
        $firma = $this->firmaDe($solicitud, $juanito);
        $this->firmar($juanito, $firma);

        $this->expectExceptionObject(FirmaDigitalException::firmaNoPendiente());

        $this->firmar($juanito, $firma->refresh());
    }

    // --- Contraseña e intentos --------------------------------------------------

    public function test_contrasena_incorrecta_cuenta_intentos_y_bloquea(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']));
        FirmaSetting::query()->create(['intentos_maximos' => 3, 'minutos_bloqueo' => 15]);
        $juanito = $this->firmanteConFirma('Juanito');

        foreach ([2, 1] as $restantes) {
            try {
                app(AbrirCredencialesFirma::class)($juanito, 'equivocada');
                $this->fail('Se esperaba FirmaDigitalException.');
            } catch (FirmaDigitalException $exception) {
                $this->assertSame(FirmaDigitalException::contrasenaAlFirmarIncorrecta($restantes)->getMessage(), $exception->getMessage());
            }
        }

        try {
            app(AbrirCredencialesFirma::class)($juanito, 'equivocada');
        } catch (FirmaDigitalException) {
        }

        $certificado = $juanito->certificadoFirmaActivo()->first();
        $this->assertTrue($certificado->estaBloqueado());
        $this->assertSame(0, $certificado->intentos_fallidos);
        Notification::assertSentTo($admin, CriticalEventNotification::class, fn ($n): bool => $n->title === 'Firma digital bloqueada');
        $this->assertSame(2, Activity::query()->where('event', EventoSeguridad::FirmaDigitalIntentoFallido->value)->count());
        $this->assertTrue(Activity::query()->where('event', EventoSeguridad::FirmaDigitalBloqueada->value)->exists());

        // Bloqueada: ni con la contraseña correcta.
        $this->expectException(FirmaDigitalException::class);
        $this->expectExceptionMessage('bloqueada');
        app(AbrirCredencialesFirma::class)($juanito, self::CONTRASENA_SISTEMA);
    }

    public function test_un_acierto_reinicia_los_intentos(): void
    {
        $juanito = $this->firmanteConFirma('Juanito');

        try {
            app(AbrirCredencialesFirma::class)($juanito, 'equivocada');
        } catch (FirmaDigitalException) {
        }

        app(AbrirCredencialesFirma::class)($juanito, self::CONTRASENA_SISTEMA);

        $this->assertSame(0, $juanito->certificadoFirmaActivo()->first()->intentos_fallidos);
    }

    public function test_una_firma_suspendida_no_puede_firmar(): void
    {
        $juanito = $this->firmanteConFirma('Juanito');
        $juanito->forceFill(['password' => 'Restablecida123'])->save();

        $this->expectExceptionObject(FirmaDigitalException::sinFirmaActiva());

        app(AbrirCredencialesFirma::class)($juanito, 'Restablecida123');
    }

    public function test_con_dos_pasos_obligatorio_exige_tenerlo_configurado(): void
    {
        FirmaSetting::query()->create(['requiere_2fa_al_firmar' => true]);
        $juanito = $this->firmanteConFirma('Juanito');

        $this->expectExceptionObject(FirmaDigitalException::dosPasosNoConfigurado());

        app(AbrirCredencialesFirma::class)($juanito, self::CONTRASENA_SISTEMA, '123456');
    }

    public function test_las_contrasenas_no_quedan_en_la_base_de_datos(): void
    {
        $juanito = $this->firmanteConFirma('Juanito');
        $solicitud = $this->envioEnCurso($this->creador, [$juanito]);
        $this->firmar($juanito, $this->firmaDe($solicitud, $juanito));

        $volcado = json_encode([
            DB::table('activity_log')->get(),
            DB::table('solicitud_firma_firmas')->get(),
            DB::table('certificados_firma')->get(),
        ]);

        $this->assertStringNotContainsString(self::CONTRASENA_SISTEMA, $volcado);
        $this->assertStringNotContainsString(self::CONTRASENA_P12, $volcado);
    }

    // --- Bloqueo, integridad y concurrencia ---------------------------------------

    public function test_si_otro_esta_firmando_el_documento_se_avisa(): void
    {
        $juanito = $this->firmanteConFirma('Juanito');
        $solicitud = $this->envioEnCurso($this->creador, [$juanito]);
        $firma = $this->firmaDe($solicitud, $juanito);
        $bloqueo = Cache::lock("firma-documento:{$firma->documento_id}", 60);
        $bloqueo->get();

        try {
            $this->firmar($juanito, $firma);
            $this->fail('Se esperaba FirmaDigitalException.');
        } catch (FirmaDigitalException $exception) {
            $this->assertSame(FirmaDigitalException::documentoEnUso('acta-1.pdf')->getMessage(), $exception->getMessage());
        } finally {
            $bloqueo->release();
        }

        $this->assertSame(EstadoFirma::Pendiente, $firma->refresh()->estado);
    }

    public function test_si_el_archivo_fue_alterado_se_detiene_y_se_alerta(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']));
        $juanito = $this->firmanteConFirma('Juanito');
        $solicitud = $this->envioEnCurso($this->creador, [$juanito]);
        $firma = $this->firmaDe($solicitud, $juanito);
        Storage::disk('local')->put($firma->documento->archivo_actual, '%PDF-manipulado');

        try {
            $this->firmar($juanito, $firma);
            $this->fail('Se esperaba FirmaDigitalException.');
        } catch (FirmaDigitalException $exception) {
            $this->assertSame(FirmaDigitalException::documentoAlterado('acta-1.pdf')->getMessage(), $exception->getMessage());
        }

        $this->assertSame(0, $firma->documento->refresh()->version);
        Notification::assertSentTo($admin, CriticalEventNotification::class, fn ($n): bool => $n->title === 'Documento en firma alterado');
    }

    public function test_si_otro_guardo_una_version_en_medio_se_reintenta_sobre_la_nueva(): void
    {
        $juanito = $this->firmanteConFirma('Juanito');
        $solicitud = $this->envioEnCurso($this->creador, [$juanito]);
        $firma = $this->firmaDe($solicitud, $juanito);
        $llamadas = 0;

        // La primera vez, mientras el firmador trabaja, "otro proceso" sube la versión.
        $this->fakeFirmador(alFirmar: function () use ($firma, &$llamadas): void {
            if (++$llamadas === 1) {
                SolicitudFirmaDocumento::query()->whereKey($firma->documento_id)->increment('version');
            }
        });

        $documento = $this->firmar($juanito, $firma);

        $this->assertSame(2, $llamadas);
        $this->assertSame(2, $documento->version);
        $this->assertSame(EstadoFirma::Firmado, $firma->refresh()->estado);
        $this->assertSame([0, 2], $documento->versiones()->pluck('version')->all());
        Storage::disk('local')->assertMissing(dirname($documento->archivo_original).'/v1.pdf');
    }

    public function test_si_la_verificacion_falla_no_se_guarda_nada(): void
    {
        $juanito = $this->firmanteConFirma('Juanito');
        $solicitud = $this->envioEnCurso($this->creador, [$juanito]);
        $firma = $this->firmaDe($solicitud, $juanito);
        $this->fakeFirmador(verificacionValida: false);

        try {
            $this->firmar($juanito, $firma);
            $this->fail('Se esperaba FirmaDigitalException.');
        } catch (FirmaDigitalException $exception) {
            $this->assertSame(FirmaDigitalException::firmaNoVerificada('acta-1.pdf')->getMessage(), $exception->getMessage());
        }

        $this->assertSame(EstadoFirma::Pendiente, $firma->refresh()->estado);
        Storage::disk('local')->assertMissing(dirname($firma->documento->archivo_original).'/v1.pdf');
    }

    // --- Lote ---------------------------------------------------------------------

    public function test_firmar_todos_con_una_sola_contrasena_y_resumen_parcial(): void
    {
        $juanito = $this->firmanteConFirma('Juanito');
        $solicitud = $this->envioEnCurso($this->creador, [$juanito], documentos: 3);
        $ocupado = $this->firmaDe($solicitud, $juanito, documento: 1);
        $bloqueo = Cache::lock("firma-documento:{$ocupado->documento_id}", 60);
        $bloqueo->get();

        $resultado = app(FirmarLote::class)($solicitud, $juanito, $this->credenciales($juanito));
        $bloqueo->release();

        $this->assertSame(['acta-1.pdf', 'acta-3.pdf'], $resultado->firmados);
        $this->assertSame(['acta-2.pdf'], array_keys($resultado->fallidos));
        $this->assertStringContainsString('2 de 3', $resultado->resumen());
        $this->assertSame(EstadoSolicitudFirma::EnCurso, $solicitud->refresh()->estado);
    }

    // --- Rechazo, cancelación y ajustes ----------------------------------------------

    public function test_rechazar_cierra_solo_ese_documento(): void
    {
        $juanito = $this->firmanteConFirma('Juanito');
        $solicitud = $this->envioEnCurso($this->creador, [$juanito], documentos: 2);
        $firma = $this->firmaDe($solicitud, $juanito);

        app(RechazarFirma::class)($firma, $juanito, 'El número de serie está mal');

        $this->assertSame(EstadoFirma::Rechazado, $firma->refresh()->estado);
        $this->assertSame('El número de serie está mal', $firma->motivo_rechazo);
        $this->assertSame(EstadoDocumentoFirma::Rechazado, $firma->documento->estado);
        $this->assertSame(EstadoSolicitudFirma::EnCurso, $solicitud->refresh()->estado);

        $this->firmar($juanito, $this->firmaDe($solicitud, $juanito, documento: 1));
        $this->assertSame(EstadoSolicitudFirma::Finalizada, $solicitud->refresh()->estado);
    }

    public function test_cancelar_detiene_las_firmas_pendientes(): void
    {
        $juanito = $this->firmanteConFirma('Juanito');
        $solicitud = $this->envioEnCurso($this->creador, [$juanito]);

        app(CancelarSolicitudFirma::class)($solicitud, $this->creador, 'Se cargó el documento equivocado');

        $solicitud->refresh();
        $this->assertSame(EstadoSolicitudFirma::Cancelada, $solicitud->estado);
        $this->assertSame($this->creador->id, $solicitud->cancelada_por);
        $this->assertSame(EstadoDocumentoFirma::Cancelado, $solicitud->documentos()->sole()->estado);

        $this->expectExceptionObject(FirmaDigitalException::solicitudNoEnCurso());
        $this->firmar($juanito, $this->firmaDe($solicitud, $juanito));
    }

    public function test_reemplazar_un_firmante_que_no_firmo(): void
    {
        $pedro = $this->firmanteConFirma('Pedro');
        $carlos = $this->firmanteConFirma('Carlos');
        $solicitud = $this->envioEnCurso($this->creador, [$pedro]);
        $firmante = $solicitud->firmantes()->sole();

        app(AjustarFirmantesEnCurso::class)->reemplazar($firmante, $carlos);

        Notification::assertSentTo($carlos, FirmaSolicitadaNotification::class);
        $this->firmar($carlos, $this->firmaDe($solicitud, $carlos));
        $this->assertSame(EstadoSolicitudFirma::Finalizada, $solicitud->refresh()->estado);
    }

    public function test_no_se_reemplaza_a_quien_ya_firmo(): void
    {
        $pedro = $this->firmanteConFirma('Pedro');
        $solicitud = $this->envioEnCurso($this->creador, [$pedro], documentos: 2);
        $this->firmar($pedro, $this->firmaDe($solicitud, $pedro));

        $this->expectExceptionObject(FirmaDigitalException::firmanteYaFirmo('Pedro'));

        app(AjustarFirmantesEnCurso::class)->reemplazar($solicitud->firmantes()->sole(), $this->firmanteConFirma('Carlos'));
    }

    public function test_quitar_el_orden_avisa_a_quienes_esperaban(): void
    {
        $pedro = $this->firmanteConFirma('Pedro');
        $carlos = $this->firmanteConFirma('Carlos');
        $solicitud = $this->envioEnCurso($this->creador, [$pedro, $carlos], ordenObligatorio: true);
        Notification::fake();

        app(AjustarFirmantesEnCurso::class)->quitarOrden($solicitud);

        Notification::assertSentTo($carlos, FirmaSolicitadaNotification::class);
        Notification::assertNotSentTo($pedro, FirmaSolicitadaNotification::class);
        $this->firmar($carlos, $this->firmaDe($solicitud, $carlos));
    }

    // --- Regeneración del PDF de origen ----------------------------------------------

    public function test_no_se_regenera_el_pdf_de_un_documento_en_firma(): void
    {
        Storage::disk('public')->put('pdf-equipos/entrega/FUE-0042.pdf', $this->pdfDePrueba());
        $pdf = PdfEquipo::factory()->create(['url' => 'pdf-equipos/entrega/FUE-0042.pdf']);
        $juanito = $this->firmanteConFirma('Juanito');

        $solicitud = app(CrearSolicitudFirma::class)(
            $this->creador,
            ['asunto' => 'Entrega'],
            [['nombre' => 'FUE-0042.pdf', 'contenido' => $this->pdfDePrueba(), 'origen' => $pdf]],
            [(string) $juanito->id],
        );
        $enFirma = app(DocumentosEnFirma::class);
        $this->assertNull($enFirma->envioEnCurso($pdf->documento), 'Un borrador no bloquea la regeneración.');

        app(GuardarPosicionesFirma::class)($solicitud, [[
            'documento_id' => $solicitud->documentos()->sole()->id,
            'firmante_id' => $solicitud->firmantes()->sole()->id,
            'pagina' => 1, 'x' => 20, 'y' => 60,
        ]]);
        app(EnviarSolicitudFirma::class)($solicitud);

        $this->assertTrue($enFirma->envioEnCurso($pdf->documento)->is($solicitud));

        $this->expectException(EquipoFormularioPdfException::class);
        $this->expectExceptionMessage('en proceso de firma digital');

        app(GenerateEquipoFormularioPdf::class)->execute($pdf->documento, EquipoFormularioPdfTipo::Entrega);
    }

    public function test_el_firmador_recibe_la_contrasena_del_p12_y_el_sello_del_titular(): void
    {
        $juanito = $this->firmanteConFirma('Juanito');
        $juanito->certificadoFirmaActivo->update(['nombre_en_sello' => 'Juanito Pérez']);
        $solicitud = $this->envioEnCurso($this->creador, [$juanito]);

        $this->firmar($juanito, $this->firmaDe($solicitud, $juanito));

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/pdf/firmar')
            && $this->campoMultipart($request, 'contrasena') === self::CONTRASENA_P12
            && json_decode((string) $this->campoMultipart($request, 'sello'), true)['nombre'] === 'Juanito Pérez'
            && $this->campoMultipart($request, 'motivo') === 'Firma de Acta de entrega');
    }
}
