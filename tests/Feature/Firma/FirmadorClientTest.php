<?php

namespace Tests\Feature\Firma;

use App\Exceptions\FirmadorException;
use App\Services\Firma\FirmadorClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FirmadorClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'firma.firmador.url' => 'http://firmador:8000',
            'firma.firmador.token' => 'token-de-prueba',
            'firma.firmador.timeout' => 30,
        ]);
    }

    public function test_inspeccionar_certificado_envia_p12_contrasena_y_token(): void
    {
        Http::fake([
            'firmador:8000/certificados/inspeccionar' => Http::response([
                'titular' => ['cn' => 'VLADIMIR ADALID CALDERON SARMIENTO', 'documento' => '8347375'],
                'vigente' => true,
                'clave_corresponde' => true,
            ]),
        ]);

        $datos = app(FirmadorClient::class)->inspeccionarCertificado('contenido-p12', 'secreta');

        $this->assertSame('VLADIMIR ADALID CALDERON SARMIENTO', $datos['titular']['cn']);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'http://firmador:8000/certificados/inspeccionar'
            && $request->hasHeader('X-Firmador-Token', 'token-de-prueba')
            && $request->hasFile('p12', 'contenido-p12', 'firma.p12')
            && $this->campoMultipart($request, 'contrasena') === 'secreta');
    }

    public function test_preparar_pdf_envia_campos_en_json_y_devuelve_el_pdf(): void
    {
        Http::fake(['firmador:8000/pdf/preparar' => Http::response('%PDF-preparado')]);

        $campos = [['nombre' => 'firma_1', 'pagina' => 1, 'x' => 60.0, 'y' => 60.0, 'ancho' => 200.0, 'alto' => 95.0]];

        $pdf = app(FirmadorClient::class)->prepararPdf('%PDF-original', $campos);

        $this->assertSame('%PDF-preparado', $pdf);
        Http::assertSent(fn (Request $request): bool => $request->hasFile('pdf', '%PDF-original')
            && json_decode((string) $this->campoMultipart($request, 'campos'), true) == $campos);
    }

    public function test_firmar_pdf_envia_sello_y_logo_opcional(): void
    {
        Http::fake(['firmador:8000/pdf/firmar' => Http::response('%PDF-firmado')]);

        $sello = ['nombre' => 'Vladimir Adalid Calderón Sarmiento', 'cargo' => 'Oficial de Infraestructura', 'fecha' => '26/10/2026 10:30 BOT'];

        $pdf = app(FirmadorClient::class)->firmarPdf('%PDF-preparado', 'contenido-p12', 'secreta', 'firma_1', $sello, 'png-logo');

        $this->assertSame('%PDF-firmado', $pdf);
        Http::assertSent(fn (Request $request): bool => $request->hasFile('pdf')
            && $request->hasFile('p12')
            && $request->hasFile('logo', 'png-logo')
            && $this->campoMultipart($request, 'campo') === 'firma_1'
            && json_decode((string) $this->campoMultipart($request, 'sello'), true) === $sello);
    }

    public function test_firmar_pdf_sin_logo_no_adjunta_logo(): void
    {
        Http::fake(['firmador:8000/pdf/firmar' => Http::response('%PDF-firmado')]);

        app(FirmadorClient::class)->firmarPdf('%PDF', 'p12', 'secreta', 'firma_1', ['nombre' => 'X', 'cargo' => null, 'fecha' => 'hoy']);

        Http::assertSent(fn (Request $request): bool => ! $request->hasFile('logo'));
    }

    public function test_verificar_pdf_devuelve_la_lista_de_firmas(): void
    {
        Http::fake(['firmador:8000/pdf/verificar' => Http::response([
            'firmas' => [['campo' => 'firma_1', 'intacta' => true, 'valida' => true]],
        ])]);

        $firmas = app(FirmadorClient::class)->verificarPdf('%PDF-firmado');

        $this->assertCount(1, $firmas);
        $this->assertTrue($firmas[0]['valida']);
    }

    public function test_error_de_negocio_se_convierte_en_excepcion_con_codigo(): void
    {
        Http::fake(['firmador:8000/*' => Http::response([
            'codigo' => 'contrasena_incorrecta',
            'mensaje' => 'La contraseña del certificado .p12 no es correcta.',
        ], 422)]);

        try {
            app(FirmadorClient::class)->inspeccionarCertificado('p12', 'mala');
            $this->fail('Se esperaba FirmadorException.');
        } catch (FirmadorException $exception) {
            $this->assertTrue($exception->esContrasenaIncorrecta());
            $this->assertSame('La contraseña del certificado .p12 no es correcta.', $exception->getMessage());
        }
    }

    public function test_respuesta_de_error_sin_formato_es_error_interno(): void
    {
        Http::fake(['firmador:8000/*' => Http::response('Bad Gateway', 502)]);

        try {
            app(FirmadorClient::class)->verificarPdf('%PDF');
            $this->fail('Se esperaba FirmadorException.');
        } catch (FirmadorException $exception) {
            $this->assertSame(FirmadorException::ERROR_INTERNO, $exception->codigo);
        }
    }

    public function test_servicio_caido_lanza_no_disponible(): void
    {
        Http::fake(['firmador:8000/*' => Http::failedConnection()]);

        try {
            app(FirmadorClient::class)->inspeccionarPdf('%PDF');
            $this->fail('Se esperaba FirmadorException.');
        } catch (FirmadorException $exception) {
            $this->assertSame(FirmadorException::NO_DISPONIBLE, $exception->codigo);
        }
    }

    public function test_disponible_consulta_salud(): void
    {
        Http::fake(['firmador:8000/salud' => Http::response(['estado' => 'ok'])]);

        $this->assertTrue(app(FirmadorClient::class)->disponible());
    }

    public function test_disponible_es_falso_si_el_servicio_no_responde(): void
    {
        Http::fake(['firmador:8000/salud' => Http::failedConnection()]);

        $this->assertFalse(app(FirmadorClient::class)->disponible());
    }

    public function test_la_contrasena_no_aparece_en_la_traza_de_errores(): void
    {
        Http::fake(['firmador:8000/*' => Http::failedConnection()]);

        try {
            app(FirmadorClient::class)->firmarPdf('%PDF', 'p12', 'contrasena-super-secreta', 'firma_1', ['nombre' => 'X', 'cargo' => null, 'fecha' => 'hoy']);
        } catch (FirmadorException $exception) {
            $this->assertStringNotContainsString('contrasena-super-secreta', $exception->getTraceAsString());

            return;
        }

        $this->fail('Se esperaba FirmadorException.');
    }

    private function campoMultipart(Request $request, string $nombre): ?string
    {
        foreach ($request->data() as $parte) {
            if (($parte['name'] ?? null) === $nombre) {
                return (string) $parte['contents'];
            }
        }

        return null;
    }
}
