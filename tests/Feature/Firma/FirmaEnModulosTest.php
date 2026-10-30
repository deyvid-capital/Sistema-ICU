<?php

namespace Tests\Feature\Firma;

use App\Enums\EquipoFormularioPdfTipo;
use App\Enums\EstadoFirmaOrigen;
use App\Filament\Resources\EntregaEquipos\EntregaEquipoResource;
use App\Filament\Resources\EntregaEquipos\Pages\EditEntregaEquipo;
use App\Filament\Resources\EntregaEquipos\Pages\ListEntregaEquipos;
use App\Filament\Resources\PdfEquipos\Pages\ListPdfEquipos;
use App\Filament\Resources\PdfEquipos\PdfEquipoResource;
use App\Filament\Resources\SolicitudesFirma\Pages\CreateSolicitudFirma;
use App\Filament\Resources\SolicitudesFirma\SolicitudFirmaResource;
use App\Jobs\GenerateEquipoFormularioPdfJob;
use App\Models\EntregaEquipo;
use App\Models\PdfEquipo;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FirmaEnModulosTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        EntregaEquipoResource::skipAuthorization();
        PdfEquipoResource::skipAuthorization();
        SolicitudFirmaResource::skipAuthorization();

        $this->usuario = User::factory()->create();
        $this->actingAs($this->usuario);
    }

    private function entregaConPdf(EstadoFirmaOrigen $estado = EstadoFirmaOrigen::SinFirma, string $numero = '001-2026'): EntregaEquipo
    {
        $entrega = EntregaEquipo::factory()->create(['numero' => $numero]);
        $ruta = EquipoFormularioPdfTipo::Entrega->pdfRelativePath($numero);
        Storage::disk('local')->put($ruta, '%PDF-1.4 original');

        PdfEquipo::factory()->create([
            'nombre' => EquipoFormularioPdfTipo::Entrega->nombreOficial(),
            'documento_type' => EntregaEquipo::class,
            'documento_id' => $entrega->id,
            'url' => $ruta,
            'estado_firma' => $estado,
        ]);

        return $entrega;
    }

    private function permitir(string $permiso): void
    {
        $this->usuario->givePermissionTo(Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']));
    }

    public function test_enviar_a_firmar_abre_el_asistente_con_el_documento(): void
    {
        $entrega = $this->entregaConPdf();
        $firmada = $this->entregaConPdf(EstadoFirmaOrigen::Firmado, '002-2026');
        $clave = 'pdf_equipo:'.$entrega->documentoOficial()->id;

        Livewire::test(ListEntregaEquipos::class)
            ->assertActionVisible(TestAction::make('enviarAFirmar')->table($entrega))
            ->assertActionHasUrl(TestAction::make('enviarAFirmar')->table($entrega), SolicitudFirmaResource::getUrl('create', ['documentos' => $clave]))
            ->assertActionHidden(TestAction::make('enviarAFirmar')->table($firmada));

        Livewire::withQueryParams(['documentos' => $clave.',pdf_equipo:'.$firmada->documentoOficial()->id])
            ->test(CreateSolicitudFirma::class)
            ->assertSchemaStateSet(['documentos_sistema' => [$clave]]);
    }

    public function test_la_tabla_muestra_el_estado_de_firma(): void
    {
        $firmada = $this->entregaConPdf(EstadoFirmaOrigen::Firmado);

        Livewire::test(ListEntregaEquipos::class)
            ->assertTableColumnFormattedStateSet('estado_firma_pdf', 'Firmado', $firmada)
            ->filterTable('estado_firma', EstadoFirmaOrigen::Firmado->value)
            ->assertCanSeeTableRecords([$firmada]);
    }

    public function test_subir_firmado_a_mano_exige_permiso(): void
    {
        $entrega = $this->entregaConPdf();

        Livewire::test(ListEntregaEquipos::class)
            ->assertActionHidden(TestAction::make('subirFirmado')->table($entrega));

        $this->permitir('UploadSigned:PdfEquipo');

        Livewire::test(ListEntregaEquipos::class)
            ->callAction(TestAction::make('subirFirmado')->table($entrega), [
                'archivo' => UploadedFile::fake()->createWithContent('escaneo.pdf', '%PDF-1.4 escaneo firmado'),
            ])
            ->assertHasNoFormErrors()
            ->assertNotified('Documento firmado a mano guardado');

        $pdf = $entrega->documentoOficial();
        $this->assertSame(EstadoFirmaOrigen::FirmadoManual, $pdf->estado_firma);
        $this->assertSame('%PDF-1.4 escaneo firmado', Storage::disk('local')->get($pdf->url));
    }

    public function test_regenerar_un_firmado_exige_confirmar_que_se_pierden_las_firmas(): void
    {
        Bus::fake([GenerateEquipoFormularioPdfJob::class]);
        $firmada = $this->entregaConPdf(EstadoFirmaOrigen::Firmado);
        $sinFirma = $this->entregaConPdf(numero: '003-2026');

        Livewire::test(ListEntregaEquipos::class)
            ->callAction(TestAction::make('generarPdf')->table($firmada))
            ->assertHasFormErrors(['confirmar_reemplazo_firmado' => 'accepted']);

        Bus::assertNotDispatched(GenerateEquipoFormularioPdfJob::class);

        Livewire::test(ListEntregaEquipos::class)
            ->callAction(TestAction::make('generarPdf')->table($firmada), ['confirmar_reemplazo_firmado' => true])
            ->assertHasNoFormErrors()
            ->callAction(TestAction::make('generarPdf')->table($sinFirma))
            ->assertHasNoFormErrors();

        Bus::assertDispatched(GenerateEquipoFormularioPdfJob::class, fn (GenerateEquipoFormularioPdfJob $job): bool => $job->record->is($firmada) && $job->reemplazarFirmado);
        Bus::assertDispatched(GenerateEquipoFormularioPdfJob::class, fn (GenerateEquipoFormularioPdfJob $job): bool => $job->record->is($sinFirma) && ! $job->reemplazarFirmado);
    }

    public function test_guardar_y_generar_no_regenera_un_firmado(): void
    {
        Bus::fake([GenerateEquipoFormularioPdfJob::class]);
        $firmada = $this->entregaConPdf(EstadoFirmaOrigen::Firmado);

        Livewire::test(EditEntregaEquipo::class, ['record' => $firmada->getKey()])
            ->call('saveAndGeneratePdf')
            ->assertNotified('Se guardaron los cambios, pero el PDF no se regeneró');

        Bus::assertNotDispatched(GenerateEquipoFormularioPdfJob::class);
    }

    public function test_enviar_a_firmar_varios_desde_la_tabla_de_pdf(): void
    {
        $primera = $this->entregaConPdf()->documentoOficial();
        $segunda = $this->entregaConPdf(numero: '004-2026')->documentoOficial();
        $firmada = $this->entregaConPdf(EstadoFirmaOrigen::Firmado, '005-2026')->documentoOficial();

        Livewire::test(ListPdfEquipos::class)
            ->selectTableRecords([$primera, $segunda, $firmada])
            ->callAction(TestAction::make('enviarAFirmar')->table()->bulk())
            ->assertRedirect(SolicitudFirmaResource::getUrl('create', ['documentos' => "pdf_equipo:{$primera->id},pdf_equipo:{$segunda->id}"]));
    }
}
