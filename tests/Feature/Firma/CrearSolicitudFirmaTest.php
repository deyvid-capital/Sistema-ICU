<?php

namespace Tests\Feature\Firma;

use App\Enums\EstadoDocumentoFirma;
use App\Enums\EstadoSolicitudFirma;
use App\Exceptions\FirmaDigitalException;
use App\Models\PdfEquipo;
use App\Models\SolicitudFirma;
use App\Models\User;
use App\Services\Firma\CrearSolicitudFirma;
use App\Services\Firma\DocumentosFirmables;
use App\Services\Firma\EliminarBorradorSolicitudFirma;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Firma\Concerns\FakeFirmador;
use Tests\TestCase;

class CrearSolicitudFirmaTest extends TestCase
{
    use FakeFirmador;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
        Notification::fake();
        $this->fakeFirmador();
    }

    /**
     * @param  list<array{nombre: string, contenido: string}>|null  $documentos
     * @param  list<string>|null  $firmantes
     */
    private function crear(?array $documentos = null, ?array $firmantes = null, array $datos = []): SolicitudFirma
    {
        $documentos ??= [['nombre' => 'acta.pdf', 'contenido' => $this->pdfDePrueba(paginas: 2)]];
        $firmantes ??= [(string) $this->firmante()->id];

        return app(CrearSolicitudFirma::class)(
            User::factory()->create(),
            ['asunto' => 'Entrega de equipo', 'mensaje' => 'Por favor firmar', ...$datos],
            $documentos,
            $firmantes,
        );
    }

    private function firmante(array $atributos = []): User
    {
        $usuario = $this->titular($atributos);
        $this->certificadoActivo($usuario);

        return $usuario;
    }

    public function test_crea_un_borrador_con_copia_privada_de_cada_documento(): void
    {
        $solicitud = $this->crear(datos: ['fecha_limite' => '2026-12-31', 'recordatorio_cada_horas' => 12]);

        $this->assertSame(EstadoSolicitudFirma::Borrador, $solicitud->estado);
        $this->assertMatchesRegularExpression('/^SF-\d{4}-\d{5}$/', $solicitud->codigo);
        $this->assertSame(12, $solicitud->recordatorio_cada_horas);

        $documento = $solicitud->documentos()->sole();
        $this->assertSame('acta.pdf', $documento->nombre);
        $this->assertSame(EstadoDocumentoFirma::Borrador, $documento->estado);
        $this->assertSame(2, $documento->totalPaginas());
        $this->assertStringStartsWith($solicitud->directorio(), $documento->archivo_original);
        $this->assertSame($this->pdfDePrueba(paginas: 2), Storage::disk('local')->get($documento->archivo_actual));
        $this->assertSame(hash('sha256', $this->pdfDePrueba(paginas: 2)), $documento->hash_actual);
        Storage::disk('public')->assertDirectoryEmpty('/');
    }

    public function test_guarda_los_firmantes_en_el_orden_recibido(): void
    {
        $primero = $this->firmante();
        $segundo = $this->firmante();

        $solicitud = $this->crear(firmantes: [(string) $segundo->id, (string) $primero->id], datos: ['orden_obligatorio' => true]);

        $this->assertTrue($solicitud->orden_obligatorio);
        $this->assertSame([$segundo->id, $primero->id], $solicitud->firmantes->pluck('usuario_id')->all());
        $this->assertSame([1, 2], $solicitud->firmantes->pluck('orden')->all());
    }

    public function test_enlaza_el_documento_del_sistema_de_origen(): void
    {
        Storage::disk('local')->put('pdf-equipos/entrega/FUE-0042.pdf', $this->pdfDePrueba());
        $pdf = PdfEquipo::factory()->create(['url' => 'pdf-equipos/entrega/FUE-0042.pdf']);
        $catalogo = app(DocumentosFirmables::class);

        $this->assertArrayHasKey("pdf_equipo:{$pdf->id}", $catalogo->buscar('FUE-0042'));

        $solicitud = $this->crear([[
            'nombre' => $catalogo->nombre($pdf),
            'contenido' => $catalogo->contenido($pdf),
            'origen' => $pdf,
        ]]);

        $documento = $solicitud->documentos()->sole();
        $this->assertTrue($documento->origen->is($pdf));
        $this->assertSame('FUE-0042.pdf', $documento->nombre);
        $this->assertSame('Equipos', $catalogo->etiquetaOrigen($documento->origen_type));
    }

    public function test_un_documento_del_sistema_sin_archivo_no_se_puede_enviar(): void
    {
        $pdf = PdfEquipo::factory()->create(['url' => 'pdf-equipos/no-existe.pdf']);

        $this->expectExceptionObject(FirmaDigitalException::documentoNoDisponible('no-existe.pdf'));

        app(DocumentosFirmables::class)->contenido($pdf);
    }

    public function test_rechaza_un_pdf_con_contrasena_sin_dejar_nada(): void
    {
        try {
            $this->crear([
                ['nombre' => 'ok.pdf', 'contenido' => $this->pdfDePrueba()],
                ['nombre' => 'protegido.pdf', 'contenido' => $this->pdfDePrueba('CIFRADO')],
            ]);
            $this->fail('Se esperaba FirmaDigitalException.');
        } catch (FirmaDigitalException $exception) {
            $this->assertSame(FirmaDigitalException::pdfCifrado('protegido.pdf')->getMessage(), $exception->getMessage());
        }

        $this->assertDatabaseCount('solicitudes_firma', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('firma-digital/solicitudes'));
    }

    public function test_rechaza_un_archivo_que_no_es_pdf(): void
    {
        $this->expectExceptionObject(FirmaDigitalException::pdfInvalido('foto.pdf'));

        $this->crear([['nombre' => 'foto.pdf', 'contenido' => 'no es un pdf']]);
    }

    public function test_rechaza_firmantes_sin_firma_activa_o_vencida(): void
    {
        $sinFirma = $this->titular(['name' => 'Sin Firma']);
        $vencida = $this->titular(['name' => 'Firma Vencida']);
        $this->certificadoActivo($vencida, ['vigente_hasta' => now()->subDay()]);

        $this->expectExceptionObject(FirmaDigitalException::firmanteSinFirmaActiva('Sin Firma, Firma Vencida'));

        $this->crear(firmantes: [(string) $sinFirma->id, (string) $vencida->id]);
    }

    public function test_rechaza_firmantes_repetidos(): void
    {
        $firmante = $this->firmante();

        $this->expectExceptionObject(FirmaDigitalException::firmanteRepetido());

        $this->crear(firmantes: [(string) $firmante->id, (string) $firmante->id]);
    }

    public function test_exige_documentos_y_firmantes(): void
    {
        try {
            $this->crear(documentos: []);
            $this->fail('Se esperaba FirmaDigitalException.');
        } catch (FirmaDigitalException $exception) {
            $this->assertSame(FirmaDigitalException::sinDocumentos()->getMessage(), $exception->getMessage());
        }

        $this->expectExceptionObject(FirmaDigitalException::sinFirmantes());
        $this->crear(firmantes: []);
    }

    public function test_limita_la_cantidad_de_documentos(): void
    {
        $documentos = array_fill(0, CrearSolicitudFirma::MAX_DOCUMENTOS + 1, ['nombre' => 'a.pdf', 'contenido' => $this->pdfDePrueba()]);

        $this->expectExceptionObject(FirmaDigitalException::demasiadosDocumentos(CrearSolicitudFirma::MAX_DOCUMENTOS));

        $this->crear($documentos);
    }

    public function test_eliminar_un_borrador_borra_sus_copias(): void
    {
        $solicitud = $this->crear();
        $directorio = $solicitud->directorio();

        app(EliminarBorradorSolicitudFirma::class)($solicitud);

        $this->assertDatabaseCount('solicitudes_firma', 0);
        $this->assertDatabaseCount('solicitud_firma_documentos', 0);
        $this->assertDatabaseCount('solicitud_firma_firmantes', 0);
        $this->assertSame([], Storage::disk('local')->allFiles($directorio));
    }
}
