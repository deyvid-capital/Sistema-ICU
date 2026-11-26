<?php

namespace Tests\Feature\Pdf;

use App\Enums\EstadoFirmaOrigen;
use App\Enums\PermisoDocumentoCompartido;
use App\Enums\UsuarioExternoFormularioPdfTipo;
use App\Exceptions\UsuarioExternoFormularioPdfException;
use App\Filament\Resources\SolicitudesFirma\SolicitudFirmaResource;
use App\Filament\Resources\UsuariosExternos\Pages\CreateUsuarioExterno;
use App\Filament\Resources\UsuariosExternos\Pages\ListUsuariosExternos;
use App\Filament\Resources\UsuariosExternos\Pages\ViewUsuarioExterno;
use App\Filament\Resources\UsuariosExternos\UsuarioExternoResource;
use App\Jobs\GenerateUsuarioExternoFormularioPdfJob;
use App\Models\PdfUsuarioExterno;
use App\Models\SitioSharepoint;
use App\Models\User;
use App\Models\UsuarioExterno;
use App\Services\Firma\DocumentosFirmables;
use App\Services\Pdf\BrowsershotPdfFactory;
use App\Services\Pdf\GenerateUsuarioExternoFormularioPdf;
use App\Services\Pdf\UsuarioExternoFormularioPdfData;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class UsuarioExternoFormularioPdfTest extends TestCase
{
    use RefreshDatabase;

    protected User $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Carbon::setTestNow(Carbon::parse('2026-11-26 10:00', 'America/La_Paz'));

        $this->mock(BrowsershotPdfFactory::class, function ($mock): void {
            $mock->shouldReceive('fromView')->andReturn('%PDF-1.4 mock-content');
        });

        UsuarioExternoResource::skipAuthorization();
        SolicitudFirmaResource::skipAuthorization();

        $this->usuario = User::factory()->create();
        $this->actingAs($this->usuario);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_el_numero_es_correlativo_por_anio_y_cuenta_los_eliminados(): void
    {
        $primero = UsuarioExterno::factory()->create();
        $segundo = UsuarioExterno::factory()->create();
        $segundo->delete();
        $tercero = UsuarioExterno::factory()->create();

        $this->assertSame('001-2026', $primero->numero);
        $this->assertSame('002-2026', $segundo->numero);
        $this->assertSame('003-2026', $tercero->numero);

        Carbon::setTestNow(Carbon::parse('2027-01-02 09:00', 'America/La_Paz'));

        $this->assertSame('001-2027', UsuarioExterno::factory()->create()->numero);
    }

    public function test_el_numero_no_se_puede_elegir_desde_el_formulario(): void
    {
        UsuarioExterno::factory()->create();

        Livewire::test(CreateUsuarioExterno::class)
            ->fillForm([
                'numero' => '999-2026',
                'nombre_contacto' => 'Javier Villegas',
                'empresa' => 'Consultor independiente',
                'cargo' => 'Consultor externo',
                'correo' => 'javier@consultora.com',
                'responsable_id' => User::factory()->create()->id,
                'fecha_inicio' => '2026-11-25',
                'fecha_fin' => '2026-12-31',
                'motivo' => 'Auditoría',
                'permiso' => PermisoDocumentoCompartido::Edicion->value,
                'onedrive' => 'Carpeta Auditoría',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame('002-2026', UsuarioExterno::query()->where('correo', 'javier@consultora.com')->value('numero'));
        $this->assertDatabaseMissing(UsuarioExterno::class, ['numero' => '999-2026']);
    }

    public function test_los_datos_del_formulario_salen_del_registro(): void
    {
        $responsable = User::factory()->create(['name' => 'Cecilia Arandia']);
        $sitio = SitioSharepoint::factory()->create(['nombre_sitio' => 'Sostenibilidad e Impacto']);
        $usuarioExterno = $this->usuarioExterno([
            'responsable_id' => $responsable->id,
            'sitio_sharepoint_id' => $sitio->id,
            'onedrive' => 'Carpeta Auditoría',
            'fecha_fin' => '2026-12-31',
        ]);

        $documento = app(UsuarioExternoFormularioPdfData::class)->for($usuarioExterno)['documento'];

        $this->assertSame('001-2026', $documento['numero']);
        $this->assertSame('Alta', $documento['tipo']);
        $this->assertSame('Cecilia Arandia', $documento['solicitante']);
        $this->assertSame('Javier Villegas', $documento['externo']['nombre']);
        $this->assertSame('Consultor externo', $documento['externo']['cargo']);
        $this->assertSame('25/11/2026', $documento['externo']['fecha_inicio']);
        $this->assertSame('31/12/2026', $documento['externo']['fecha_fin']);
        $this->assertSame('Sostenibilidad e Impacto / Carpeta Auditoría', $documento['sitio']);
        $this->assertSame('Edición', $documento['permiso']);
    }

    public function test_genera_y_regenera_el_pdf_oficial_en_el_mismo_registro(): void
    {
        $usuarioExterno = $this->usuarioExterno();

        $pdf = app(GenerateUsuarioExternoFormularioPdf::class)->execute($usuarioExterno);

        $this->assertInstanceOf(PdfUsuarioExterno::class, $pdf);
        $this->assertSame(UsuarioExternoFormularioPdfTipo::Formulario->nombreOficial(), $pdf->nombre);
        $this->assertSame('pdf-usuarios-externos/UsuarioExterno-001-2026.pdf', $pdf->url);
        $this->assertTrue($usuarioExterno->fresh()->hasDocumentoOficial());
        Storage::disk('local')->assertExists($pdf->url);

        $regenerado = app(GenerateUsuarioExternoFormularioPdf::class)->execute($usuarioExterno->fresh());

        $this->assertTrue($regenerado->is($pdf));
        $this->assertSame(1, PdfUsuarioExterno::query()->count());
    }

    public function test_no_regenera_un_pdf_firmado_sin_confirmacion(): void
    {
        $usuarioExterno = $this->usuarioExternoConPdf(EstadoFirmaOrigen::Firmado);

        $this->expectException(UsuarioExternoFormularioPdfException::class);

        app(GenerateUsuarioExternoFormularioPdf::class)->execute($usuarioExterno);
    }

    public function test_no_genera_si_el_responsable_esta_en_la_papelera(): void
    {
        $responsable = User::factory()->create();
        $usuarioExterno = $this->usuarioExterno(['responsable_id' => $responsable->id]);
        $responsable->delete();

        try {
            app(GenerateUsuarioExternoFormularioPdf::class)->execute($usuarioExterno->fresh());
            $this->fail('Se esperaba un error de validación.');
        } catch (UsuarioExternoFormularioPdfException $exception) {
            $this->assertContains('El usuario solicitante (responsable interno) no existe o está eliminado.', $exception->errors);
        }

        $this->assertSame(0, PdfUsuarioExterno::query()->count());
    }

    public function test_la_vista_previa_muestra_los_datos_reales_y_exige_permiso(): void
    {
        $usuarioExterno = $this->usuarioExterno();

        $this->get(route('usuarios-externos.preview-formulario', $usuarioExterno))->assertForbidden();

        Gate::before(fn () => true);

        $this->get(route('usuarios-externos.preview-formulario', $usuarioExterno))
            ->assertOk()
            ->assertSee('Formulario de Usuario Externo')
            ->assertSee('001-2026')
            ->assertSee('Javier Villegas')
            ->assertSee('Subgerente de Tecnología de la Información')
            ->assertDontSee('NOTA');
    }

    public function test_generar_encola_el_pdf_y_ver_solo_aparece_si_existe(): void
    {
        Bus::fake([GenerateUsuarioExternoFormularioPdfJob::class]);
        $sinPdf = $this->usuarioExterno();
        $conPdf = $this->usuarioExternoConPdf();

        Livewire::test(ListUsuariosExternos::class)
            ->assertActionVisible(TestAction::make('vistaPrevia')->table($sinPdf))
            ->assertActionHidden(TestAction::make('viewDocument')->table($sinPdf))
            ->assertActionVisible(TestAction::make('viewDocument')->table($conPdf))
            ->callAction(TestAction::make('generarPdf')->table($sinPdf))
            ->assertNotified('Generando PDF…');

        Bus::assertDispatched(GenerateUsuarioExternoFormularioPdfJob::class, fn (GenerateUsuarioExternoFormularioPdfJob $job): bool => $job->record->is($sinPdf));
    }

    public function test_la_ficha_muestra_el_pdf_solo_cuando_esta_generado(): void
    {
        $sinPdf = $this->usuarioExterno();
        $conPdf = $this->usuarioExternoConPdf();

        Livewire::test(ViewUsuarioExterno::class, ['record' => $sinPdf->getKey()])
            ->assertDontSee('archivos/pdf/pdf_usuario_externo');

        Livewire::test(ViewUsuarioExterno::class, ['record' => $conPdf->getKey()])
            ->assertSee(route('archivos.pdf.ver', ['tipo' => 'pdf_usuario_externo', 'id' => $conPdf->documentoOficial()->id]));
    }

    public function test_se_puede_enviar_a_firmar_y_aparece_en_el_catalogo_de_firmables(): void
    {
        $usuarioExterno = $this->usuarioExternoConPdf();
        $pdf = $usuarioExterno->documentoOficial();
        $clave = 'pdf_usuario_externo:'.$pdf->id;

        $this->assertSame($clave, DocumentosFirmables::clave($pdf));
        $this->assertArrayHasKey($clave, app(DocumentosFirmables::class)->buscar(null, 'usuarioexterno_formulario'));
        $this->assertArrayNotHasKey($clave, app(DocumentosFirmables::class)->buscar(null, 'movimiento_alta'));

        Livewire::test(ListUsuariosExternos::class)
            ->assertActionVisible(TestAction::make('enviarAFirmar')->table($usuarioExterno))
            ->assertActionHasUrl(TestAction::make('enviarAFirmar')->table($usuarioExterno), SolicitudFirmaResource::getUrl('create', ['documentos' => $clave]));
    }

    public function test_el_pdf_se_sirve_a_quien_puede_ver_el_usuario_externo(): void
    {
        $pdf = $this->usuarioExternoConPdf()->documentoOficial();
        $ruta = route('archivos.pdf.ver', ['tipo' => 'pdf_usuario_externo', 'id' => $pdf->id]);

        $this->get($ruta)->assertForbidden();

        Gate::before(fn () => true);

        $this->get($ruta)->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function usuarioExterno(array $datos = []): UsuarioExterno
    {
        return UsuarioExterno::factory()->create([
            'nombre_contacto' => 'Javier Villegas',
            'empresa' => 'Consultor independiente',
            'cargo' => 'Consultor externo',
            'correo' => 'javier@consultora.com',
            'fecha_inicio' => '2026-11-25',
            'fecha_fin' => null,
            'permiso' => PermisoDocumentoCompartido::Edicion,
            'onedrive' => 'Carpeta',
            ...$datos,
        ]);
    }

    private function usuarioExternoConPdf(EstadoFirmaOrigen $estado = EstadoFirmaOrigen::SinFirma): UsuarioExterno
    {
        $usuarioExterno = UsuarioExterno::factory()->create();
        $ruta = UsuarioExternoFormularioPdfTipo::Formulario->pdfRelativePath($usuarioExterno);
        Storage::disk('local')->put($ruta, '%PDF-1.4 original');

        $pdf = $usuarioExterno->pdfs()->create([
            'nombre' => UsuarioExternoFormularioPdfTipo::Formulario->nombreOficial(),
            'url' => $ruta,
            'fecha' => '2026-11-26',
        ]);
        $pdf->forceFill(['estado_firma' => $estado])->save();

        return $usuarioExterno;
    }
}
