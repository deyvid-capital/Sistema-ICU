<?php

namespace Tests\Feature\Firma;

use App\Enums\EstadoDocumentoFirma;
use App\Enums\EstadoFirma;
use App\Enums\EstadoSolicitudFirma;
use App\Exceptions\FirmaDigitalException;
use App\Filament\Resources\SolicitudesFirma\Pages\ConfigurarPosicionesFirma;
use App\Filament\Resources\SolicitudesFirma\SolicitudFirmaResource;
use App\Models\PdfEquipo;
use App\Models\SolicitudFirma;
use App\Models\User;
use App\Notifications\Firma\FirmaSolicitadaNotification;
use App\Services\Firma\CrearSolicitudFirma;
use App\Services\Firma\EnviarSolicitudFirma;
use App\Services\Firma\GuardarPosicionesFirma;
use App\Services\Firma\PosicionesFirmaAnteriores;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Feature\Firma\Concerns\FakeFirmador;
use Tests\TestCase;

class PosicionesYEnvioFirmaTest extends TestCase
{
    use FakeFirmador;
    use RefreshDatabase;

    private User $creador;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
        Notification::fake();
        $this->fakeFirmador();

        SolicitudFirmaResource::skipAuthorization(false);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $rol = Role::firstOrCreate(['name' => 'operador', 'guard_name' => 'web']);
        $rol->givePermissionTo(Permission::firstOrCreate(['name' => 'Create:SolicitudFirma', 'guard_name' => 'web']));
        $this->creador = User::factory()->create();
        $this->creador->assignRole($rol);
        $this->actingAs($this->creador);
    }

    private function firmante(string $nombre): User
    {
        $usuario = $this->titular(['name' => $nombre]);
        $this->certificadoActivo($usuario);

        return $usuario;
    }

    /**
     * @param  list<User>  $firmantes
     * @param  list<array{nombre: string, contenido: string, origen?: mixed}>|null  $documentos
     */
    private function borrador(array $firmantes, ?array $documentos = null, bool $ordenObligatorio = false): SolicitudFirma
    {
        return app(CrearSolicitudFirma::class)(
            $this->creador,
            ['asunto' => 'Acta de entrega', 'orden_obligatorio' => $ordenObligatorio],
            $documentos ?? [['nombre' => 'acta.pdf', 'contenido' => $this->pdfDePrueba(paginas: 2)]],
            array_map(fn (User $usuario): string => (string) $usuario->id, $firmantes),
        );
    }

    /**
     * @return list<array{documento_id: int, firmante_id: int, pagina: int, x: float, y: float}>
     */
    private function posicionesValidas(SolicitudFirma $solicitud): array
    {
        $posiciones = [];

        foreach ($solicitud->documentos as $documento) {
            foreach ($solicitud->firmantes as $indice => $firmante) {
                $posiciones[] = [
                    'documento_id' => $documento->id,
                    'firmante_id' => $firmante->id,
                    'pagina' => 1,
                    'x' => 20 + $indice * (config('firma.sello.ancho') + 10),
                    'y' => 80,
                ];
            }
        }

        return $posiciones;
    }

    // --- Guardar posiciones ------------------------------------------------

    public function test_guarda_un_recuadro_por_firmante_y_documento(): void
    {
        $solicitud = $this->borrador([$this->firmante('Juanito'), $this->firmante('Carlos')]);

        app(GuardarPosicionesFirma::class)($solicitud, $this->posicionesValidas($solicitud));

        $firmas = $solicitud->documentos()->sole()->firmas()->orderBy('x')->get();
        $this->assertCount(2, $firmas);
        $this->assertSame(EstadoFirma::Pendiente, $firmas[0]->estado);
        $this->assertSame('firma_'.$firmas[0]->firmante_id, $firmas[0]->campo);
        $this->assertSame(
            [20.0, 80.0, (float) config('firma.sello.ancho'), (float) config('firma.sello.alto')],
            [$firmas[0]->x, $firmas[0]->y, $firmas[0]->ancho, $firmas[0]->alto],
        );
    }

    public function test_guardar_de_nuevo_reemplaza_las_posiciones(): void
    {
        $solicitud = $this->borrador([$this->firmante('Juanito')]);
        $posiciones = $this->posicionesValidas($solicitud);

        app(GuardarPosicionesFirma::class)($solicitud, $posiciones);
        $posiciones[0]['pagina'] = 2;
        app(GuardarPosicionesFirma::class)($solicitud, $posiciones);

        $this->assertSame(2, $solicitud->documentos()->sole()->firmas()->sole()->pagina);
    }

    public function test_rechaza_un_recuadro_fuera_de_la_pagina(): void
    {
        $solicitud = $this->borrador([$this->firmante('Juanito')]);
        $posiciones = $this->posicionesValidas($solicitud);
        $posiciones[0]['x'] = 612 - config('firma.sello.ancho') + 10; // sale 10 pt por la derecha

        $this->expectExceptionObject(FirmaDigitalException::posicionFueraDePagina('Juanito', 'acta.pdf'));

        app(GuardarPosicionesFirma::class)($solicitud, $posiciones);
    }

    public function test_rechaza_una_pagina_que_no_existe(): void
    {
        $solicitud = $this->borrador([$this->firmante('Juanito')]);
        $posiciones = $this->posicionesValidas($solicitud);
        $posiciones[0]['pagina'] = 3;

        $this->expectExceptionObject(FirmaDigitalException::posicionInvalida());

        app(GuardarPosicionesFirma::class)($solicitud, $posiciones);
    }

    public function test_rechaza_recuadros_superpuestos(): void
    {
        $solicitud = $this->borrador([$this->firmante('Juanito'), $this->firmante('Carlos')]);
        $posiciones = $this->posicionesValidas($solicitud);
        $posiciones[1]['x'] = $posiciones[0]['x'] + config('firma.sello.ancho') / 2;

        $this->expectExceptionObject(FirmaDigitalException::posicionesSuperpuestas('acta.pdf', 1));

        app(GuardarPosicionesFirma::class)($solicitud, $posiciones);
    }

    public function test_rechaza_documentos_o_firmantes_de_otro_envio(): void
    {
        $solicitud = $this->borrador([$this->firmante('Juanito')]);
        $otra = $this->borrador([$this->firmante('Carlos')]);
        $posiciones = $this->posicionesValidas($solicitud);
        $posiciones[0]['documento_id'] = $otra->documentos()->sole()->id;

        $this->expectExceptionObject(FirmaDigitalException::posicionInvalida());

        app(GuardarPosicionesFirma::class)($solicitud, $posiciones);
    }

    // --- Enviar ------------------------------------------------------------

    public function test_enviar_exige_todas_las_posiciones(): void
    {
        $solicitud = $this->borrador([$this->firmante('Juanito'), $this->firmante('Carlos')]);
        app(GuardarPosicionesFirma::class)($solicitud, array_slice($this->posicionesValidas($solicitud), 0, 1));

        $this->expectException(FirmaDigitalException::class);
        $this->expectExceptionMessage('Falta ubicar la firma de: Carlos en acta.pdf');

        app(EnviarSolicitudFirma::class)($solicitud);
    }

    public function test_enviar_prepara_los_pdf_y_avisa_a_todos_en_orden_libre(): void
    {
        $juanito = $this->firmante('Juanito');
        $carlos = $this->firmante('Carlos');
        $solicitud = $this->borrador([$juanito, $carlos]);
        app(GuardarPosicionesFirma::class)($solicitud, $this->posicionesValidas($solicitud));

        app(EnviarSolicitudFirma::class)($solicitud);

        $solicitud->refresh();
        $documento = $solicitud->documentos()->sole();
        $this->assertSame(EstadoSolicitudFirma::EnCurso, $solicitud->estado);
        $this->assertNotNull($solicitud->enviada_at);
        $this->assertSame(EstadoDocumentoFirma::EnCurso, $documento->estado);
        $this->assertStringEndsWith('/v0.pdf', $documento->archivo_actual);
        $this->assertSame('%PDF-preparado', Storage::disk('local')->get($documento->archivo_actual));
        $this->assertSame(hash('sha256', '%PDF-preparado'), $documento->hash_actual);
        $this->assertSame(0, $documento->versiones()->sole()->version);

        // Los campos se crean con la posición guardada.
        Http::assertSent(function (Request $request): bool {
            if (! str_ends_with($request->url(), '/pdf/preparar')) {
                return false;
            }

            $campos = json_decode((string) $this->campoMultipart($request, 'campos'), true);

            return count($campos) === 2
                && $campos[0]['ancho'] == config('firma.sello.ancho')
                && $campos[0]['alto'] == config('firma.sello.alto');
        });

        Notification::assertSentTo([$juanito, $carlos], FirmaSolicitadaNotification::class);
    }

    public function test_con_orden_obligatorio_solo_se_avisa_al_primero(): void
    {
        $pedro = $this->firmante('Pedro');
        $carlos = $this->firmante('Carlos');
        $solicitud = $this->borrador([$pedro, $carlos], ordenObligatorio: true);
        app(GuardarPosicionesFirma::class)($solicitud, $this->posicionesValidas($solicitud));

        app(EnviarSolicitudFirma::class)($solicitud);

        Notification::assertSentTo($pedro, FirmaSolicitadaNotification::class);
        Notification::assertNotSentTo($carlos, FirmaSolicitadaNotification::class);
    }

    public function test_si_el_firmador_falla_el_envio_sigue_en_borrador(): void
    {
        $solicitud = $this->borrador([$this->firmante('Juanito')]);
        app(GuardarPosicionesFirma::class)($solicitud, $this->posicionesValidas($solicitud));
        Http::fake(['*' => Http::failedConnection()]);

        try {
            app(EnviarSolicitudFirma::class)($solicitud);
            $this->fail('Se esperaba FirmaDigitalException.');
        } catch (FirmaDigitalException $exception) {
            $this->assertSame(FirmaDigitalException::firmadorNoDisponible()->getMessage(), $exception->getMessage());
        }

        $this->assertSame(EstadoSolicitudFirma::Borrador, $solicitud->refresh()->estado);
        Notification::assertNothingSent();
    }

    public function test_un_envio_ya_enviado_no_cambia_sus_posiciones(): void
    {
        $solicitud = $this->borrador([$this->firmante('Juanito')]);
        app(GuardarPosicionesFirma::class)($solicitud, $this->posicionesValidas($solicitud));
        app(EnviarSolicitudFirma::class)($solicitud);

        $this->expectExceptionObject(FirmaDigitalException::solicitudNoEsBorrador());

        app(GuardarPosicionesFirma::class)($solicitud->refresh(), $this->posicionesValidas($solicitud));
    }

    // --- Posiciones de la última vez ----------------------------------------

    public function test_sugiere_las_posiciones_del_ultimo_envio_del_mismo_tipo(): void
    {
        Storage::disk('public')->put('pdf-equipos/a.pdf', $this->pdfDePrueba());
        Storage::disk('public')->put('pdf-equipos/b.pdf', $this->pdfDePrueba());
        $documento = fn (string $url): array => [
            'nombre' => basename($url),
            'contenido' => $this->pdfDePrueba(),
            'origen' => PdfEquipo::factory()->create(['url' => $url]),
        ];

        $anterior = $this->borrador([$this->firmante('Juanito')], [$documento('pdf-equipos/a.pdf')]);
        $posiciones = $this->posicionesValidas($anterior);
        $posiciones[0]['x'] = 333;
        app(GuardarPosicionesFirma::class)($anterior, $posiciones);
        app(EnviarSolicitudFirma::class)($anterior);

        $nueva = $this->borrador([$this->firmante('Carlos')], [$documento('pdf-equipos/b.pdf')]);
        $sugeridas = app(PosicionesFirmaAnteriores::class)->para($nueva);

        $this->assertCount(1, $sugeridas);
        $this->assertSame($nueva->firmantes()->sole()->id, $sugeridas[0]['firmante_id']);
        $this->assertEquals(333, $sugeridas[0]['x']);
    }

    // --- Pantalla ------------------------------------------------------------

    public function test_la_pantalla_carga_el_canvas_con_los_datos_del_envio(): void
    {
        $solicitud = $this->borrador([$this->firmante('Juanito')]);

        $this->get(SolicitudFirmaResource::getUrl('posiciones', ['record' => $solicitud], panel: 'admin'))
            ->assertSuccessful()
            ->assertSee('canvas-firmas', escape: false)
            ->assertSee('acta.pdf');
    }

    public function test_guardar_y_enviar_desde_la_pantalla(): void
    {
        $juanito = $this->firmante('Juanito');
        $solicitud = $this->borrador([$juanito]);

        Livewire::test(ConfigurarPosicionesFirma::class, ['record' => $solicitud->getRouteKey()])
            ->set('posiciones', $this->posicionesValidas($solicitud))
            ->callAction('guardar')
            ->assertNotified('Posiciones guardadas')
            ->callAction('enviar')
            ->assertNotified('Envío realizado')
            ->assertRedirect(SolicitudFirmaResource::getUrl('view', ['record' => $solicitud]));

        $this->assertSame(EstadoSolicitudFirma::EnCurso, $solicitud->refresh()->estado);
        Notification::assertSentTo($juanito, FirmaSolicitadaNotification::class);
    }

    public function test_enviar_incompleto_desde_la_pantalla_muestra_el_error(): void
    {
        $solicitud = $this->borrador([$this->firmante('Juanito')]);

        Livewire::test(ConfigurarPosicionesFirma::class, ['record' => $solicitud->getRouteKey()])
            ->callAction('enviar')
            ->assertNotified('No se pudo enviar');

        $this->assertSame(EstadoSolicitudFirma::Borrador, $solicitud->refresh()->estado);
    }

    public function test_solo_el_creador_ubica_las_firmas_y_solo_en_borrador(): void
    {
        $juanito = $this->firmante('Juanito');
        $solicitud = $this->borrador([$juanito]);
        $url = SolicitudFirmaResource::getUrl('posiciones', ['record' => $solicitud], panel: 'admin');

        $this->actingAs($juanito)->get($url)->assertForbidden();

        $solicitud->update(['estado' => EstadoSolicitudFirma::EnCurso]);
        $this->actingAs($this->creador)->get($url)->assertForbidden();
    }

    public function test_el_correo_al_firmante_lleva_el_enlace_y_no_los_pdf(): void
    {
        $solicitud = $this->borrador([$this->firmante('Juanito')]);
        $solicitud->update(['mensaje' => 'Revisar la cláusula 3']);

        $correo = (new FirmaSolicitadaNotification($solicitud))->toMail($this->creador);

        $this->assertSame([], $correo->attachments);
        $this->assertSame(SolicitudFirmaResource::getUrl('view', ['record' => $solicitud], panel: 'admin'), $correo->actionUrl);
        $this->assertStringContainsString('Revisar la cláusula 3', implode("\n", $correo->introLines));
    }
}
