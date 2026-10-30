<?php

namespace Tests\Feature\Archivos;

use App\Enums\EquipoFormularioPdfTipo;
use App\Models\AppearanceSetting;
use App\Models\EntregaEquipo;
use App\Models\MantenimientoEquipo;
use App\Models\PdfEquipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AlmacenamientoConfidencialTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
    }

    private function pdf(): PdfEquipo
    {
        $ruta = EquipoFormularioPdfTipo::Entrega->pdfRelativePath('001-2026');
        Storage::disk('local')->put($ruta, '%PDF-1.7 confidencial');

        return PdfEquipo::factory()->create([
            'nombre' => EquipoFormularioPdfTipo::Entrega->nombreOficial(),
            'documento_type' => EntregaEquipo::class,
            'documento_id' => EntregaEquipo::factory(),
            'url' => $ruta,
        ]);
    }

    private function usuarioCon(string ...$permisos): User
    {
        $usuario = User::factory()->create();

        foreach ($permisos as $permiso) {
            $usuario->givePermissionTo(Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']));
        }

        return $usuario;
    }

    // --- Ruta protegida de PDF ---------------------------------------------------

    public function test_el_enlace_del_pdf_es_la_ruta_protegida(): void
    {
        $pdf = $this->pdf();

        $this->assertSame(route('archivos.pdf.ver', ['tipo' => 'pdf_equipo', 'id' => $pdf->id]), $pdf->storedDocumentUrl());
    }

    public function test_sin_sesion_o_sin_permiso_no_se_ve(): void
    {
        $pdf = $this->pdf();
        $url = route('archivos.pdf.ver', ['tipo' => 'pdf_equipo', 'id' => $pdf->id]);

        $this->get($url)->assertRedirect();
        $this->actingAs($this->usuarioCon())->get($url)->assertForbidden();
    }

    public function test_se_ve_con_permiso_del_pdf_o_del_registro_al_que_pertenece(): void
    {
        $pdf = $this->pdf();

        $this->actingAs($this->usuarioCon('View:PdfEquipo'))
            ->get(route('archivos.pdf.ver', ['tipo' => 'pdf_equipo', 'id' => $pdf->id]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline; filename=FUE-001-2026.pdf');

        $this->actingAs($this->usuarioCon('View:EntregaEquipo'))
            ->get(route('archivos.pdf.descargar', ['tipo' => 'pdf_equipo', 'id' => $pdf->id]))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename=FUE-001-2026.pdf');
    }

    public function test_un_archivo_que_falta_responde_404(): void
    {
        $pdf = $this->pdf();
        Storage::disk('local')->delete($pdf->url);

        $this->actingAs($this->usuarioCon('View:PdfEquipo'))
            ->get(route('archivos.pdf.ver', ['tipo' => 'pdf_equipo', 'id' => $pdf->id]))
            ->assertNotFound();
    }

    // --- Discos por grupo --------------------------------------------------------

    public function test_la_marca_va_al_disco_publico_y_las_capturas_al_confidencial(): void
    {
        $marca = AppearanceSetting::create(['tema' => 'safi']);
        $logo = $marca->addMedia(UploadedFile::fake()->image('logo.png', 20, 20))->toMediaCollection('logo_claro');

        $mantenimiento = MantenimientoEquipo::factory()->create();
        $captura = $mantenimiento->addMedia(UploadedFile::fake()->image('antes.png', 20, 20))->toMediaCollection('cap_antes');

        $this->assertSame('public', $logo->disk);
        $this->assertSame('local', $captura->disk);
    }

    // --- archivos:mover --------------------------------------------------------

    public function test_mover_lleva_los_pdf_del_disco_publico_al_confidencial(): void
    {
        $pdf = PdfEquipo::factory()->create(['url' => 'pdf-equipos/entrega/FUE-009-2026.pdf']);
        Storage::disk('public')->put($pdf->url, '%PDF publico');

        $this->artisan('archivos:mover', ['--simular' => true])->assertSuccessful();
        Storage::disk('public')->assertExists($pdf->url);

        $this->artisan('archivos:mover')->assertSuccessful();

        Storage::disk('public')->assertMissing($pdf->url);
        $this->assertSame('%PDF publico', Storage::disk('local')->get($pdf->url));
    }

    public function test_mover_borra_duplicados_identicos_y_deja_los_distintos(): void
    {
        $identico = PdfEquipo::factory()->create(['url' => 'pdf-equipos/a.pdf']);
        $distinto = PdfEquipo::factory()->create(['url' => 'pdf-equipos/b.pdf']);

        foreach (['public', 'local'] as $disco) {
            Storage::disk($disco)->put($identico->url, '%PDF igual');
        }

        Storage::disk('public')->put($distinto->url, '%PDF viejo');
        Storage::disk('local')->put($distinto->url, '%PDF nuevo');

        $this->artisan('archivos:mover')
            ->expectsOutputToContain('difiere')
            ->assertSuccessful();

        Storage::disk('public')->assertMissing($identico->url);
        Storage::disk('public')->assertExists($distinto->url);
        $this->assertSame('%PDF nuevo', Storage::disk('local')->get($distinto->url));
    }

    public function test_mover_lleva_la_marca_heredada_al_disco_publico(): void
    {
        Storage::disk('local')->put('apariencia/logo.png', 'png');
        AppearanceSetting::create(['tema' => 'safi', 'logo_claro' => 'apariencia/logo.png']);

        $this->artisan('archivos:mover')->assertSuccessful();

        Storage::disk('local')->assertMissing('apariencia/logo.png');
        Storage::disk('public')->assertExists('apariencia/logo.png');
    }
}
