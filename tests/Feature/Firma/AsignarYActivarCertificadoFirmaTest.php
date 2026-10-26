<?php

namespace Tests\Feature\Firma;

use App\Enums\EstadoCertificadoFirma;
use App\Enums\EventoSeguridad;
use App\Exceptions\FirmaDigitalException;
use App\Models\CertificadoFirma;
use App\Models\User;
use App\Notifications\Firma\FirmaActivacionPendienteNotification;
use App\Services\Firma\ActivarCertificadoFirma;
use App\Services\Firma\AsignarCertificadoFirma;
use App\Services\Firma\BovedaCertificados;
use App\Services\Firma\EnvolturaContrasena;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Tests\Feature\Firma\Concerns\FakeFirmador;
use Tests\TestCase;

class AsignarYActivarCertificadoFirmaTest extends TestCase
{
    use FakeFirmador;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Notification::fake();
    }

    // --- Asignar (admin) ---------------------------------------------------

    public function test_el_admin_asigna_un_p12_que_queda_pendiente_y_cifrado(): void
    {
        $this->fakeFirmador();
        $titular = $this->titular();
        $admin = User::factory()->create();

        $certificado = app(AsignarCertificadoFirma::class)($titular, self::CONTENIDO_P12, 'Vladimir Calderón', 'Oficial', $admin);

        $this->assertSame(EstadoCertificadoFirma::PendienteActivacion, $certificado->estado);
        $this->assertSame($admin->id, $certificado->cargado_por);
        $this->assertNull($certificado->titular_cn);
        $this->assertSame(self::CONTENIDO_P12, app(BovedaCertificados::class)->leer($certificado->archivo));
        Notification::assertSentTo($titular, FirmaActivacionPendienteNotification::class, fn ($n): bool => ! $n->esRenovacion);
    }

    public function test_rechaza_un_archivo_que_no_es_p12_sin_dejar_rastro(): void
    {
        $this->fakeFirmador(p12Valido: false);
        $titular = $this->titular();

        try {
            app(AsignarCertificadoFirma::class)($titular, 'no-es-un-p12', 'X', null);
            $this->fail('Se esperaba FirmaDigitalException.');
        } catch (FirmaDigitalException $exception) {
            $this->assertSame('El archivo no es un certificado .p12 válido.', $exception->getMessage());
        }

        $this->assertDatabaseCount('certificados_firma', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_la_renovacion_mantiene_activa_la_firma_anterior(): void
    {
        $this->fakeFirmador();
        $titular = $this->titular();
        $actual = $this->certificadoActivo($titular);

        $renovacion = app(AsignarCertificadoFirma::class)($titular, 'p12-del-nuevo-anio', 'X', null);

        $this->assertSame($actual->id, $renovacion->reemplaza_a_id);
        $this->assertSame(EstadoCertificadoFirma::Activo, $actual->refresh()->estado);
        Notification::assertSentTo($titular, FirmaActivacionPendienteNotification::class, fn ($n): bool => $n->esRenovacion);
    }

    public function test_un_nuevo_archivo_reemplaza_al_pendiente_sin_activar(): void
    {
        $this->fakeFirmador();
        $titular = $this->titular();
        $anterior = $this->certificadoPendiente($titular);
        $archivoAnterior = $anterior->archivo;

        app(AsignarCertificadoFirma::class)($titular, 'otro-p12', 'X', null);

        $this->assertSame(EstadoCertificadoFirma::Revocado, $anterior->refresh()->estado);
        Storage::disk('local')->assertMissing($archivoAnterior);
        $this->assertSame(1, $titular->certificadosFirma()->where('estado', EstadoCertificadoFirma::PendienteActivacion)->count());
    }

    // --- Activar (titular) -------------------------------------------------

    public function test_el_titular_activa_su_firma_con_las_dos_contrasenas(): void
    {
        $this->fakeFirmador();
        $titular = $this->titular();
        $certificado = $this->certificadoPendiente($titular);

        $activado = app(ActivarCertificadoFirma::class)($certificado, $titular, self::CONTRASENA_P12, self::CONTRASENA_SISTEMA);

        $this->assertSame(EstadoCertificadoFirma::Activo, $activado->estado);
        $this->assertSame('VLADIMIR ADALID CALDERON SARMIENTO', $activado->titular_cn);
        $this->assertSame(self::DOCUMENTO, $activado->titular_documento);
        $this->assertSame('Entidad Certificadora Autorizada Digicert', $activado->emisor);
        $this->assertTrue($activado->puedeFirmar());
        $this->assertTrue($titular->certificadoFirmaActivo->is($activado));

        // La contraseña del .p12 solo se recupera con la contraseña del sistema.
        $this->assertSame(
            self::CONTRASENA_P12,
            app(EnvolturaContrasena::class)->desenvolver($activado->contrasena_p12_envuelta, self::CONTRASENA_SISTEMA),
        );

        $this->assertTrue(Activity::query()->where('event', EventoSeguridad::FirmaDigitalActivada->value)->exists());
    }

    public function test_no_guarda_ninguna_contrasena_en_texto_plano(): void
    {
        $this->fakeFirmador();
        $titular = $this->titular();
        $certificado = $this->certificadoPendiente($titular);

        app(ActivarCertificadoFirma::class)($certificado, $titular, self::CONTRASENA_P12, self::CONTRASENA_SISTEMA);

        $fila = json_encode(DB::table('certificados_firma')->where('id', $certificado->id)->first());
        $actividad = json_encode(DB::table('activity_log')->get());

        foreach ([self::CONTRASENA_P12, self::CONTRASENA_SISTEMA] as $secreto) {
            $this->assertStringNotContainsString($secreto, $fila);
            $this->assertStringNotContainsString($secreto, $actividad);
        }
        $this->assertStringNotContainsString('contrasena_p12_envuelta', $actividad);
    }

    public function test_avisa_si_escribio_la_contrasena_del_sistema_en_el_paso_del_p12(): void
    {
        $this->fakeFirmador();
        $titular = $this->titular();
        $certificado = $this->certificadoPendiente($titular);

        $this->expectExceptionObject(FirmaDigitalException::escribioContrasenaDelSistema());

        app(ActivarCertificadoFirma::class)->validarContrasenaP12($certificado, $titular, self::CONTRASENA_SISTEMA);
    }

    public function test_contrasena_del_p12_incorrecta(): void
    {
        $this->fakeFirmador();
        $titular = $this->titular();
        $certificado = $this->certificadoPendiente($titular);

        try {
            app(ActivarCertificadoFirma::class)->validarContrasenaP12($certificado, $titular, 'cualquier-otra');
            $this->fail('Se esperaba FirmaDigitalException.');
        } catch (FirmaDigitalException $exception) {
            $this->assertSame(FirmaDigitalException::contrasenaP12Incorrecta()->getMessage(), $exception->getMessage());
        }

        $this->assertTrue(Activity::query()->where('event', EventoSeguridad::FirmaDigitalContrasenaIncorrecta->value)->exists());
    }

    public function test_contrasena_del_sistema_incorrecta(): void
    {
        $this->fakeFirmador();
        $titular = $this->titular();
        $certificado = $this->certificadoPendiente($titular);

        $this->expectExceptionObject(FirmaDigitalException::contrasenaSistemaIncorrecta());

        app(ActivarCertificadoFirma::class)($certificado, $titular, self::CONTRASENA_P12, 'no-es-mi-contrasena');
    }

    public function test_el_ci_del_certificado_debe_coincidir_con_el_carnet(): void
    {
        $this->fakeFirmador();
        $titular = $this->titular(['carnet' => '1234567']);
        $certificado = $this->certificadoPendiente($titular);

        $this->expectExceptionObject(FirmaDigitalException::documentoNoCoincide(self::DOCUMENTO, '1234567'));

        app(ActivarCertificadoFirma::class)->validarContrasenaP12($certificado, $titular, self::CONTRASENA_P12);
    }

    public function test_el_usuario_debe_tener_carnet(): void
    {
        $this->fakeFirmador();
        $titular = $this->titular(['carnet' => null]);
        $certificado = $this->certificadoPendiente($titular);

        $this->expectExceptionObject(FirmaDigitalException::usuarioSinCarnet());

        app(ActivarCertificadoFirma::class)->validarContrasenaP12($certificado, $titular, self::CONTRASENA_P12);
    }

    public function test_rechaza_un_certificado_vencido(): void
    {
        $this->fakeFirmador(['vigente' => false, 'vigente_hasta' => now()->subDay()->toIso8601String()]);
        $titular = $this->titular();
        $certificado = $this->certificadoPendiente($titular);

        $this->expectException(FirmaDigitalException::class);
        $this->expectExceptionMessage('no está vigente');

        app(ActivarCertificadoFirma::class)->validarContrasenaP12($certificado, $titular, self::CONTRASENA_P12);
    }

    public function test_rechaza_si_la_clave_no_corresponde_al_certificado(): void
    {
        $this->fakeFirmador(['clave_corresponde' => false]);
        $titular = $this->titular();
        $certificado = $this->certificadoPendiente($titular);

        $this->expectExceptionObject(FirmaDigitalException::clavePrivadaNoCorresponde());

        app(ActivarCertificadoFirma::class)->validarContrasenaP12($certificado, $titular, self::CONTRASENA_P12);
    }

    public function test_rechaza_un_certificado_sin_uso_de_firma(): void
    {
        $this->fakeFirmador(['uso_clave' => ['key_encipherment']]);
        $titular = $this->titular();
        $certificado = $this->certificadoPendiente($titular);

        $this->expectExceptionObject(FirmaDigitalException::sinUsoDeFirma());

        app(ActivarCertificadoFirma::class)->validarContrasenaP12($certificado, $titular, self::CONTRASENA_P12);
    }

    public function test_rechaza_un_certificado_asignado_a_otro_usuario(): void
    {
        $this->fakeFirmador();
        $otro = $this->titular(['name' => 'Otra Persona']);
        $this->certificadoActivo($otro, ['huella_sha256' => str_repeat('a', 64)]);
        $titular = $this->titular();
        $certificado = $this->certificadoPendiente($titular);

        $this->expectExceptionObject(FirmaDigitalException::certificadoDuplicado('Otra Persona'));

        app(ActivarCertificadoFirma::class)->validarContrasenaP12($certificado, $titular, self::CONTRASENA_P12);
    }

    public function test_nadie_puede_activar_la_firma_de_otro(): void
    {
        $this->fakeFirmador();
        $certificado = $this->certificadoPendiente($this->titular());
        $intruso = $this->titular();

        $this->expectExceptionObject(FirmaDigitalException::noPerteneceAlUsuario());

        app(ActivarCertificadoFirma::class)($certificado, $intruso, self::CONTRASENA_P12, self::CONTRASENA_SISTEMA);
    }

    public function test_activar_la_renovacion_reemplaza_la_firma_anterior_y_borra_su_archivo(): void
    {
        $this->fakeFirmador();
        $titular = $this->titular();
        $anterior = $this->certificadoActivo($titular, ['huella_sha256' => str_repeat('b', 64)]);
        $archivoAnterior = $anterior->archivo;
        $renovacion = $this->certificadoPendiente($titular, ['reemplaza_a_id' => $anterior->id]);

        app(ActivarCertificadoFirma::class)($renovacion, $titular, self::CONTRASENA_P12, self::CONTRASENA_SISTEMA);

        $this->assertSame(EstadoCertificadoFirma::Reemplazado, $anterior->refresh()->estado);
        $this->assertNull($anterior->contrasena_p12_envuelta);
        Storage::disk('local')->assertMissing($archivoAnterior);
        $this->assertSame(EstadoCertificadoFirma::Activo, $renovacion->refresh()->estado);
        $this->assertSame(1, CertificadoFirma::query()->where('estado', EstadoCertificadoFirma::Activo)->count());
    }

    public function test_reactiva_una_firma_suspendida(): void
    {
        $this->fakeFirmador();
        $titular = $this->titular();
        $suspendida = $this->certificadoActivo($titular)->forceFill([
            'estado' => EstadoCertificadoFirma::RequiereReactivacion,
            'contrasena_p12_envuelta' => null,
        ]);
        $suspendida->save();

        app(ActivarCertificadoFirma::class)($suspendida, $titular, self::CONTRASENA_P12, self::CONTRASENA_SISTEMA);

        $this->assertTrue($suspendida->refresh()->puedeFirmar());
    }
}
