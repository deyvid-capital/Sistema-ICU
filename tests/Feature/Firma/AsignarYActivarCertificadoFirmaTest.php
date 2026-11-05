<?php

namespace Tests\Feature\Firma;

use App\Enums\EstadoCertificadoFirma;
use App\Enums\EventoSeguridad;
use App\Exceptions\FirmaDigitalException;
use App\Filament\Pages\Auth\Login;
use App\Models\CertificadoFirma;
use App\Models\User;
use App\Notifications\Firma\FirmaAsignadaNotification;
use App\Services\Firma\AbrirCredencialesFirma;
use App\Services\Firma\ActivarCertificadoFirma;
use App\Services\Firma\AsignarCertificadoFirma;
use App\Services\Firma\BovedaCertificados;
use App\Services\Firma\EnvolturaContrasena;
use App\Services\Firma\VincularCertificadoFirma;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
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

    private function asignar(User $titular, string $contenido = self::CONTENIDO_P12, string $contrasenaP12 = self::CONTRASENA_P12, ?User $admin = null): CertificadoFirma
    {
        return app(AsignarCertificadoFirma::class)($titular, $contenido, $contrasenaP12, 'Vladimir Calderón', 'Oficial', $admin);
    }

    // --- Asignar y activar (admin) -------------------------------------------

    public function test_el_admin_asigna_y_activa_la_firma_con_la_contrasena_del_p12(): void
    {
        $this->fakeFirmador();
        $titular = $this->titular();
        $admin = User::factory()->create();

        $certificado = $this->asignar($titular, admin: $admin);

        $this->assertSame(EstadoCertificadoFirma::Activo, $certificado->estado);
        $this->assertSame($admin->id, $certificado->cargado_por);
        $this->assertSame('VLADIMIR ADALID CALDERON SARMIENTO', $certificado->titular_cn);
        $this->assertSame(self::DOCUMENTO, $certificado->titular_documento);
        $this->assertSame(self::CONTENIDO_P12, app(BovedaCertificados::class)->leer($certificado->archivo));
        $this->assertSame(self::CONTRASENA_P12, app(BovedaCertificados::class)->descifrar($certificado->contrasena_p12_custodia));
        $this->assertNull($certificado->contrasena_p12_envuelta);
        $this->assertTrue($certificado->estaPendienteDeVincular());
        $this->assertTrue($certificado->puedeFirmar());
        Notification::assertSentTo($titular, FirmaAsignadaNotification::class, fn ($n): bool => ! $n->esRenovacion);

        $evento = Activity::query()->where('event', EventoSeguridad::FirmaDigitalActivada->value)->sole();
        $this->assertSame($admin->id, $evento->causer_id);
        $this->assertSame($titular->id, $evento->subject_id);
    }

    public function test_rechaza_un_archivo_que_no_es_p12_sin_dejar_rastro(): void
    {
        $this->fakeFirmador(p12Valido: false);

        try {
            $this->asignar($this->titular(), 'no-es-un-p12');
            $this->fail('Se esperaba FirmaDigitalException.');
        } catch (FirmaDigitalException $exception) {
            $this->assertSame('El archivo no es un certificado .p12 válido.', $exception->getMessage());
        }

        $this->assertDatabaseCount('certificados_firma', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_contrasena_del_p12_incorrecta_no_deja_rastro(): void
    {
        $this->fakeFirmador();

        try {
            $this->asignar($this->titular(), contrasenaP12: 'cualquier-otra');
            $this->fail('Se esperaba FirmaDigitalException.');
        } catch (FirmaDigitalException $exception) {
            $this->assertSame(FirmaDigitalException::contrasenaP12Incorrecta()->getMessage(), $exception->getMessage());
        }

        $this->assertDatabaseCount('certificados_firma', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertTrue(Activity::query()->where('event', EventoSeguridad::FirmaDigitalContrasenaIncorrecta->value)->exists());
    }

    public function test_la_renovacion_reemplaza_la_firma_anterior_y_borra_su_archivo(): void
    {
        $this->fakeFirmador();
        $titular = $this->titular();
        $anterior = $this->certificadoActivo($titular, ['huella_sha256' => str_repeat('b', 64)]);
        $archivoAnterior = $anterior->archivo;

        $renovacion = $this->asignar($titular, 'p12-del-nuevo-anio');

        $this->assertSame($anterior->id, $renovacion->reemplaza_a_id);
        $this->assertSame(EstadoCertificadoFirma::Reemplazado, $anterior->refresh()->estado);
        $this->assertNull($anterior->contrasena_p12_envuelta);
        $this->assertNull($anterior->contrasena_p12_custodia);
        Storage::disk('local')->assertMissing($archivoAnterior);
        $this->assertSame(1, CertificadoFirma::query()->where('estado', EstadoCertificadoFirma::Activo)->count());
        Notification::assertSentTo($titular, FirmaAsignadaNotification::class, fn ($n): bool => $n->esRenovacion);
    }

    public function test_un_nuevo_archivo_descarta_el_pendiente_del_flujo_anterior(): void
    {
        $this->fakeFirmador();
        $titular = $this->titular();
        $anterior = $this->certificadoPendiente($titular);
        $archivoAnterior = $anterior->archivo;

        $this->asignar($titular, 'otro-p12');

        $this->assertSame(EstadoCertificadoFirma::Revocado, $anterior->refresh()->estado);
        Storage::disk('local')->assertMissing($archivoAnterior);
    }

    public function test_no_guarda_ninguna_contrasena_en_texto_plano(): void
    {
        $this->fakeFirmador();
        $titular = $this->titular();

        $certificado = $this->asignar($titular);
        app(VincularCertificadoFirma::class)($titular, self::CONTRASENA_SISTEMA);

        $fila = json_encode(DB::table('certificados_firma')->where('id', $certificado->id)->first());
        $actividad = json_encode(DB::table('activity_log')->get());

        foreach ([self::CONTRASENA_P12, self::CONTRASENA_SISTEMA] as $secreto) {
            $this->assertStringNotContainsString($secreto, $fila);
            $this->assertStringNotContainsString($secreto, $actividad);
        }
        $this->assertStringNotContainsString('contrasena_p12_envuelta', $actividad);
        $this->assertStringNotContainsString('contrasena_p12_custodia', $actividad);
    }

    public function test_el_ci_del_certificado_debe_coincidir_con_el_carnet(): void
    {
        $this->fakeFirmador();

        $this->expectExceptionObject(FirmaDigitalException::documentoNoCoincide(self::DOCUMENTO, '1234567'));

        $this->asignar($this->titular(['carnet' => '1234567']));
    }

    public function test_el_usuario_debe_tener_carnet(): void
    {
        $this->fakeFirmador();

        $this->expectExceptionObject(FirmaDigitalException::usuarioSinCarnet());

        $this->asignar($this->titular(['carnet' => null]));
    }

    public function test_rechaza_un_certificado_vencido(): void
    {
        $this->fakeFirmador(['vigente' => false, 'vigente_hasta' => now()->subDay()->toIso8601String()]);

        $this->expectException(FirmaDigitalException::class);
        $this->expectExceptionMessage('no está vigente');

        $this->asignar($this->titular());
    }

    public function test_rechaza_si_la_clave_no_corresponde_al_certificado(): void
    {
        $this->fakeFirmador(['clave_corresponde' => false]);

        $this->expectExceptionObject(FirmaDigitalException::clavePrivadaNoCorresponde());

        $this->asignar($this->titular());
    }

    public function test_rechaza_un_certificado_sin_uso_de_firma(): void
    {
        $this->fakeFirmador(['uso_clave' => ['key_encipherment']]);

        $this->expectExceptionObject(FirmaDigitalException::sinUsoDeFirma());

        $this->asignar($this->titular());
    }

    public function test_rechaza_un_certificado_asignado_a_otro_usuario(): void
    {
        $this->fakeFirmador();
        $otro = $this->titular(['name' => 'Otra Persona']);
        $this->certificadoActivo($otro, ['huella_sha256' => str_repeat('a', 64)]);

        $this->expectExceptionObject(FirmaDigitalException::certificadoDuplicado('Otra Persona'));

        $this->asignar($this->titular());
    }

    // --- Activar una firma ya subida (admin) ---------------------------------

    public function test_el_admin_activa_un_p12_pendiente_del_flujo_anterior(): void
    {
        $this->fakeFirmador();
        $titular = $this->titular();
        $pendiente = $this->certificadoPendiente($titular);

        $activado = app(ActivarCertificadoFirma::class)($pendiente, self::CONTRASENA_P12, User::factory()->create());

        $this->assertTrue($activado->estaPendienteDeVincular());
        $this->assertSame('Entidad Certificadora Autorizada Digicert', $activado->emisor);
    }

    public function test_el_admin_reactiva_una_firma_en_pausa_sin_copia_en_custodia(): void
    {
        $this->fakeFirmador();
        $titular = $this->titular();
        $suspendida = $this->certificadoActivo($titular, [
            'estado' => EstadoCertificadoFirma::RequiereReactivacion,
            'contrasena_p12_envuelta' => null,
            'contrasena_p12_custodia' => null,
        ]);

        app(ActivarCertificadoFirma::class)($suspendida, self::CONTRASENA_P12);

        $this->assertTrue($suspendida->refresh()->estaPendienteDeVincular());
    }

    public function test_no_se_puede_activar_una_firma_ya_activa(): void
    {
        $this->fakeFirmador();
        $activa = $this->certificadoActivo($this->titular());

        $this->expectExceptionObject(FirmaDigitalException::noActivable());

        app(ActivarCertificadoFirma::class)($activa, self::CONTRASENA_P12);
    }

    // --- Vincular con la contraseña del sistema (titular) --------------------

    public function test_se_vincula_con_la_contrasena_del_sistema(): void
    {
        $titular = $this->titular();
        $certificado = $this->certificadoPendienteDeVincular($titular);

        $vinculado = app(VincularCertificadoFirma::class)($titular, self::CONTRASENA_SISTEMA);

        $this->assertTrue($vinculado->is($certificado));
        $this->assertSame(
            self::CONTRASENA_P12,
            app(EnvolturaContrasena::class)->desenvolver($vinculado->contrasena_p12_envuelta, self::CONTRASENA_SISTEMA),
        );
        $this->assertFalse($vinculado->estaPendienteDeVincular());
        $this->assertNotNull($vinculado->contrasena_p12_custodia);
        $this->assertTrue(Activity::query()->where('event', EventoSeguridad::FirmaDigitalVinculada->value)->exists());
    }

    public function test_con_otra_contrasena_no_se_vincula(): void
    {
        $titular = $this->titular();
        $certificado = $this->certificadoPendienteDeVincular($titular);

        $this->assertNull(app(VincularCertificadoFirma::class)($titular, 'no-es-mi-contrasena'));
        $this->assertTrue($certificado->refresh()->estaPendienteDeVincular());
    }

    public function test_una_firma_ya_vinculada_no_se_toca(): void
    {
        $titular = $this->titular();
        $certificado = $this->certificadoActivo($titular);
        $envuelta = $certificado->contrasena_p12_envuelta;

        $this->assertNull(app(VincularCertificadoFirma::class)($titular, self::CONTRASENA_SISTEMA));
        $this->assertSame($envuelta, $certificado->refresh()->contrasena_p12_envuelta);
    }

    public function test_una_firma_en_pausa_se_reactiva_con_la_nueva_contrasena(): void
    {
        $titular = $this->titular();
        $suspendida = $this->certificadoActivo($titular, [
            'estado' => EstadoCertificadoFirma::RequiereReactivacion,
            'contrasena_p12_envuelta' => null,
        ]);

        app(VincularCertificadoFirma::class)($titular, self::CONTRASENA_SISTEMA);

        $this->assertSame(EstadoCertificadoFirma::Activo, $suspendida->refresh()->estado);
        $this->assertTrue($suspendida->puedeFirmar());
    }

    public function test_al_iniciar_sesion_la_firma_queda_vinculada(): void
    {
        $titular = User::factory()->conRol()->create(['carnet' => self::DOCUMENTO]);
        $certificado = $this->certificadoPendienteDeVincular($titular);

        Livewire::test(Login::class)
            ->fillForm(['email' => $titular->email, 'password' => self::CONTRASENA_SISTEMA])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($titular);
        $this->assertFalse($certificado->refresh()->estaPendienteDeVincular());
    }

    public function test_al_firmar_por_primera_vez_la_firma_queda_vinculada(): void
    {
        $titular = $this->titular();
        $this->certificadoPendienteDeVincular($titular);

        $credenciales = app(AbrirCredencialesFirma::class)($titular, self::CONTRASENA_SISTEMA);

        $this->assertSame(self::CONTRASENA_P12, $credenciales->contrasenaP12());
        $this->assertFalse($credenciales->certificado->estaPendienteDeVincular());
    }

    public function test_al_firmar_sin_vincular_con_contrasena_incorrecta_cuenta_el_intento(): void
    {
        $titular = $this->titular();
        $certificado = $this->certificadoPendienteDeVincular($titular);

        try {
            app(AbrirCredencialesFirma::class)($titular, 'no-es-mi-contrasena');
            $this->fail('Se esperaba FirmaDigitalException.');
        } catch (FirmaDigitalException) {
        }

        $this->assertSame(1, $certificado->refresh()->intentos_fallidos);
        $this->assertTrue($certificado->estaPendienteDeVincular());
    }
}
