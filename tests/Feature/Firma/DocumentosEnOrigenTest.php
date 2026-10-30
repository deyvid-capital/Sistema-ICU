<?php

namespace Tests\Feature\Firma;

use App\Enums\EquipoFormularioPdfTipo;
use App\Enums\EstadoFirmaOrigen;
use App\Exceptions\FirmaDigitalException;
use App\Models\EntregaEquipo;
use App\Models\Movimiento;
use App\Models\PdfEquipo;
use App\Models\PdfMovimiento;
use App\Models\SolicitudFirma;
use App\Models\User;
use App\Services\Firma\AbrirCredencialesFirma;
use App\Services\Firma\CancelarSolicitudFirma;
use App\Services\Firma\CrearSolicitudFirma;
use App\Services\Firma\DocumentosFirmables;
use App\Services\Firma\EnviarSolicitudFirma;
use App\Services\Firma\FirmarDocumento;
use App\Services\Firma\GuardarPosicionesFirma;
use App\Services\Firma\RechazarFirma;
use App\Services\Firma\ReemplazoDocumentoFirmado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Firma\Concerns\FakeFirmador;
use Tests\Feature\Firma\Concerns\PreparaEnviosEnCurso;
use Tests\TestCase;

class DocumentosEnOrigenTest extends TestCase
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

    private function pdfEquipo(string $numero = '001-2026', string $contenido = ''): PdfEquipo
    {
        $ruta = EquipoFormularioPdfTipo::Entrega->pdfRelativePath($numero);
        Storage::disk('local')->put($ruta, $contenido ?: $this->pdfDePrueba($numero));

        return PdfEquipo::factory()->create([
            'nombre' => EquipoFormularioPdfTipo::Entrega->nombreOficial(),
            'documento_type' => EntregaEquipo::class,
            'documento_id' => EntregaEquipo::factory(),
            'url' => $ruta,
            'fecha' => now(),
        ]);
    }

    /**
     * @param  list<User>  $firmantes
     * @param  list<PdfEquipo|PdfMovimiento>  $origenes
     */
    private function envioConOrigen(array $firmantes, array $origenes): SolicitudFirma
    {
        $catalogo = app(DocumentosFirmables::class);

        $solicitud = app(CrearSolicitudFirma::class)(
            $this->creador,
            ['asunto' => 'Actas'],
            array_map(fn ($origen): array => [
                'nombre' => $catalogo->nombre($origen),
                'contenido' => $catalogo->contenido($origen),
                'origen' => $origen,
            ], $origenes),
            array_map(fn (User $usuario): string => (string) $usuario->id, $firmantes),
        );

        $posiciones = [];

        foreach ($solicitud->documentos as $documento) {
            foreach ($solicitud->firmantes as $indice => $firmante) {
                $posiciones[] = ['documento_id' => $documento->id, 'firmante_id' => $firmante->id, 'pagina' => 1, 'x' => 20 + $indice * 200, 'y' => 60];
            }
        }

        app(GuardarPosicionesFirma::class)($solicitud, $posiciones);
        app(EnviarSolicitudFirma::class)($solicitud);

        return $solicitud->refresh();
    }

    private function firmar(User $usuario, SolicitudFirma $solicitud, int $documento = 0): void
    {
        app(FirmarDocumento::class)(
            $this->firmaDe($solicitud, $usuario, $documento),
            $usuario,
            app(AbrirCredencialesFirma::class)($usuario, self::CONTRASENA_SISTEMA),
        );
    }

    // --- Estados ---------------------------------------------------------------

    public function test_al_enviar_queda_en_firma_con_el_contador(): void
    {
        $juanito = $this->firmanteConFirma('Juanito');
        $carlos = $this->firmanteConFirma('Carlos');
        $pdf = $this->pdfEquipo();

        $solicitud = $this->envioConOrigen([$juanito, $carlos], [$pdf]);
        $pdf->refresh();

        $this->assertSame(EstadoFirmaOrigen::EnFirma, $pdf->estado_firma);
        $this->assertSame($solicitud->documentos()->sole()->id, $pdf->firma_documento_id);
        $this->assertSame('En firma 0/2', $pdf->etiquetaEstadoFirma());

        $this->firmar($juanito, $solicitud);

        $this->assertSame('En firma 1/2', $pdf->refresh()->etiquetaEstadoFirma());
    }

    public function test_al_completarse_el_firmado_reemplaza_al_original_en_la_misma_ruta(): void
    {
        $juanito = $this->firmanteConFirma('Juanito');
        $pdf = $this->pdfEquipo();
        $ruta = $pdf->url;

        $solicitud = $this->envioConOrigen([$juanito], [$pdf]);
        $this->firmar($juanito, $solicitud);

        $documento = $solicitud->documentos()->sole();
        $pdf->refresh();

        $this->assertSame(EstadoFirmaOrigen::Firmado, $pdf->estado_firma);
        $this->assertSame($ruta, $pdf->url);
        $this->assertNotNull($pdf->firmado_at);
        $this->assertSame($documento->hash_actual, $pdf->hash_sha256);
        $this->assertSame(Storage::disk('local')->get($documento->archivo_actual), Storage::disk('local')->get($ruta));
        $this->assertSame(['pdf-equipos/entrega/FUE-001-2026.pdf'], Storage::disk('local')->allFiles('pdf-equipos'));
        $this->assertSame([['nombre' => 'Juanito']], $pdf->firmasDigitales()->map(fn (array $firma): array => ['nombre' => $firma['nombre']])->all());
    }

    public function test_un_rechazado_no_se_publica_y_los_completados_si(): void
    {
        $juanito = $this->firmanteConFirma('Juanito');
        $aceptado = $this->pdfEquipo('001-2026');
        $rechazado = $this->pdfEquipo('002-2026');
        $contenidoRechazado = Storage::disk('local')->get($rechazado->url);

        $solicitud = $this->envioConOrigen([$juanito], [$aceptado, $rechazado]);
        $this->firmar($juanito, $solicitud, 0);
        app(RechazarFirma::class)($this->firmaDe($solicitud, $juanito, 1), $juanito, 'Datos mal');

        $this->assertSame(EstadoFirmaOrigen::Firmado, $aceptado->refresh()->estado_firma);
        $this->assertSame(EstadoFirmaOrigen::Rechazado, $rechazado->refresh()->estado_firma);
        $this->assertSame($contenidoRechazado, Storage::disk('local')->get($rechazado->url));
    }

    public function test_cancelar_devuelve_los_pendientes_a_sin_firma(): void
    {
        $juanito = $this->firmanteConFirma('Juanito');
        $pdf = $this->pdfEquipo();
        $solicitud = $this->envioConOrigen([$juanito], [$pdf]);

        app(CancelarSolicitudFirma::class)($solicitud, $this->creador, 'Se rehace');

        $pdf->refresh();
        $this->assertSame(EstadoFirmaOrigen::SinFirma, $pdf->estado_firma);
        $this->assertNull($pdf->firma_documento_id);
    }

    public function test_no_se_envia_un_documento_en_firma_o_firmado(): void
    {
        $juanito = $this->firmanteConFirma('Juanito');
        $pdf = $this->pdfEquipo();
        $this->envioConOrigen([$juanito], [$pdf]);

        try {
            $this->envioConOrigen([$juanito], [$pdf->refresh()]);
            $this->fail('Se aceptó un documento en firma.');
        } catch (FirmaDigitalException $exception) {
            $this->assertStringContainsString('en firma', $exception->getMessage());
        }

        $pdf->update(['estado_firma' => EstadoFirmaOrigen::Rechazado]);
        $this->assertInstanceOf(SolicitudFirma::class, $this->envioConOrigen([$juanito], [$pdf->refresh()]));
    }

    // --- Publicación y sincronización -------------------------------------------

    public function test_sincronizar_publica_envios_anteriores_solo_si_el_origen_no_cambio(): void
    {
        $juanito = $this->firmanteConFirma('Juanito');
        $intacto = $this->pdfEquipo('001-2026');
        $regenerado = $this->pdfEquipo('002-2026');
        $solicitud = $this->envioConOrigen([$juanito], [$intacto, $regenerado]);

        // Simula un envío hecho antes de existir el vínculo y un PDF regenerado después.
        PdfEquipo::query()->update(['estado_firma' => 'sin_firma', 'firma_documento_id' => null]);
        Storage::disk('local')->put($regenerado->url, $this->pdfDePrueba('regenerado'));
        $this->firmar($juanito, $solicitud, 0);
        $this->firmar($juanito, $solicitud, 1);

        $this->artisan('firmas:sincronizar-origen')->assertSuccessful();
        $this->artisan('firmas:sincronizar-origen')->assertSuccessful();

        $this->assertSame(EstadoFirmaOrigen::Firmado, $intacto->refresh()->estado_firma);
        $this->assertSame(EstadoFirmaOrigen::SinFirma, $regenerado->refresh()->estado_firma);
        $this->assertSame($this->pdfDePrueba('regenerado'), Storage::disk('local')->get($regenerado->url));
    }

    // --- Reemplazo del archivo ------------------------------------------------

    public function test_subir_firmado_a_mano_reemplaza_y_marca_el_estado(): void
    {
        $pdf = $this->pdfEquipo();

        app(ReemplazoDocumentoFirmado::class)->subirFirmadoManual($pdf, '%PDF escaneado', confirmado: false);

        $pdf->refresh();
        $this->assertSame(EstadoFirmaOrigen::FirmadoManual, $pdf->estado_firma);
        $this->assertSame('%PDF escaneado', Storage::disk('local')->get($pdf->url));
        $this->assertSame(hash('sha256', '%PDF escaneado'), $pdf->hash_sha256);
    }

    public function test_reemplazar_un_firmado_exige_confirmacion_y_en_firma_esta_bloqueado(): void
    {
        $servicio = app(ReemplazoDocumentoFirmado::class);
        $juanito = $this->firmanteConFirma('Juanito');
        $pdf = $this->pdfEquipo();
        $this->envioConOrigen([$juanito], [$pdf]);

        $this->assertStringContainsString('proceso de firma', (string) $servicio->impedimento($pdf->refresh(), confirmado: true));

        $firmado = $this->pdfEquipo('005-2026');
        $firmado->update(['estado_firma' => EstadoFirmaOrigen::Firmado]);

        $this->assertStringContainsString('Confirma', (string) $servicio->impedimento($firmado->refresh(), confirmado: false));
        $this->assertNull($servicio->impedimento($firmado, confirmado: true));

        $this->expectException(FirmaDigitalException::class);
        $servicio->subirFirmadoManual($firmado, '%PDF otro', confirmado: false);
    }

    // --- Catálogo del asistente -------------------------------------------------

    public function test_el_catalogo_filtra_por_categoria_y_oculta_los_firmados(): void
    {
        $entrega = $this->pdfEquipo('001-2026');
        $firmado = $this->pdfEquipo('002-2026');
        $firmado->update(['estado_firma' => EstadoFirmaOrigen::Firmado]);

        Storage::disk('local')->put('pdf-movimientos/FUB-Ana.pdf', $this->pdfDePrueba());
        $baja = PdfMovimiento::query()->create([
            'tipo' => 'Baja',
            'nombre' => 'Formulario de alta/baja de usuario',
            'url' => 'pdf-movimientos/FUB-Ana.pdf',
            'movimiento_id' => Movimiento::factory()->create(['tipo' => 'Baja'])->id,
            'fecha' => now(),
        ]);

        $catalogo = app(DocumentosFirmables::class);
        $claveEntrega = "pdf_equipo:{$entrega->id}";
        $claveFirmado = "pdf_equipo:{$firmado->id}";
        $claveBaja = "pdf_movimiento:{$baja->id}";

        $this->assertEqualsCanonicalizing([$claveEntrega, $claveBaja], array_keys($catalogo->buscar(null)));
        $this->assertSame([$claveEntrega], array_keys($catalogo->buscar(null, 'equipo_entrega')));
        $this->assertSame([$claveBaja], array_keys($catalogo->buscar(null, 'movimiento_baja')));
        $this->assertSame([], $catalogo->buscar(null, 'movimiento_alta'));
        $this->assertArrayHasKey($claveFirmado, $catalogo->buscar(null, incluirFirmados: true));
        $this->assertStringEndsWith('· Firmado', $catalogo->buscar(null, incluirFirmados: true)[$claveFirmado]);
        $this->assertFalse($catalogo->esElegible($claveFirmado));
        $this->assertTrue($catalogo->esElegible($claveEntrega));
        $this->assertNull($catalogo->resolver('recibo_equipo:1'));
    }
}
