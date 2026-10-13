<?php

namespace Tests\Feature\Pdf;

use App\Enums\CategoriasSoftware;
use App\Enums\EstadoAprobacionExcepcion;
use App\Enums\MovimientoFormularioPdfTipo;
use App\Models\AccesoDaza;
use App\Models\AccesoInternet;
use App\Models\AccesoSistema;
use App\Models\AccesoVirtual;
use App\Models\Area;
use App\Models\AsignacionEquipo;
use App\Models\Cargo;
use App\Models\Equipo;
use App\Models\Fondo;
use App\Models\ModeloLaptop;
use App\Models\Movimiento;
use App\Models\PdfMovimiento;
use App\Models\RecursoVpn;
use App\Models\Sistema;
use App\Models\Software;
use App\Models\SoftwareExc;
use App\Models\SoftwareUsuario;
use App\Models\User;
use App\Models\Virtual;
use App\Services\Pdf\BrowsershotPdfFactory;
use App\Services\Pdf\GenerateMovimientoFormularioPdf;
use App\Services\Pdf\MovimientoFormularioPdfData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MovimientoFormularioPdfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->mock(BrowsershotPdfFactory::class, function ($mock): void {
            $mock->shouldReceive('fromView')
                ->andReturn('%PDF-1.4 mock-content');
        });
    }

    public function test_mapper_fills_real_user_data_and_uses_dash_for_empty_fields(): void
    {
        $area = Area::factory()->create(['area' => 'Inversiones Alternativas']);
        $cargo = Cargo::factory()->create([
            'cargo' => 'Analista',
            'area_id' => $area->id_area,
        ]);
        $usuario = User::factory()->create([
            'nombres' => 'Cristian Fernando',
            'apellidos' => 'Duran Gonzales',
            'carnet' => '6101010 LP',
            'email' => 'cristian@example.com',
            'id_area' => $area->id_area,
            'id_cargo' => $cargo->id_cargo,
        ]);

        $sistema = Sistema::factory()->create(['nombre_sistema' => 'Bflow']);
        AccesoSistema::factory()->create([
            'usuario_id' => $usuario->id,
            'id_sistema' => $sistema->id_sistema,
            'nivel_acceso' => 'Usuario',
            'estado' => true,
        ]);

        AccesoInternet::factory()->create([
            'usuario_id' => $usuario->id,
            'estado' => true,
        ]);

        $software = Software::factory()->create([
            'nombre_software' => 'Adobe Acrobat',
            'categoria' => CategoriasSoftware::PDF->value,
        ]);
        SoftwareUsuario::factory()->create([
            'usuario_id' => $usuario->id,
            'id_software' => $software->id_software,
            'estado' => true,
        ]);

        $modelo = ModeloLaptop::factory()->create(['nombre' => 'Latitude 3500']);
        $equipo = Equipo::factory()->create([
            'modelo_id' => $modelo->id,
            'nombre_equipo' => 'BOCSIA06',
            'so' => 'Windows 11 Pro',
        ]);
        AsignacionEquipo::factory()->create([
            'usuario_id' => $usuario->id,
            'service_tag' => $equipo->service_tag,
            'fecha_fin' => null,
        ]);

        $movimiento = Movimiento::query()->create([
            'usuario_id' => $usuario->id,
            'tipo' => 'Alta',
            'fecha' => '2026-04-01',
            'cargo_id' => $cargo->id_cargo,
        ]);

        $payload = app(MovimientoFormularioPdfData::class)->for($movimiento);
        $documento = $payload['documento'];

        $this->assertSame('Alta', $documento['tipo']);
        $this->assertSame('01/04/2026', $documento['fecha_alta']);
        $this->assertSame('-', $documento['fecha_baja']);
        $this->assertSame('Cristian Fernando', $documento['usuario']['nombres']);
        $this->assertSame('Duran Gonzales', $documento['usuario']['apellido']);
        $this->assertSame('Analista', $documento['usuario']['cargo']);
        $this->assertSame('Inversiones Alternativas', $documento['usuario']['area']);
        $this->assertSame('Bflow - Usuario', $documento['software']['sistemas']);
        $this->assertSame('Inversiones Alternativas (IA)', $documento['software']['internet']);
        $this->assertSame('Adobe Acrobat', $documento['software']['pdf']);
        $this->assertSame('Latitude 3500', $documento['equipo']['modelo']);
        $this->assertSame('BOCSIA06', $documento['equipo']['nombre']);
        $this->assertSame('-', $documento['software']['navegadores']);
        $this->assertSame('-', $documento['software']['vpn']);
    }

    public function test_el_formulario_de_baja_lista_lo_que_la_cascada_acaba_de_desactivar(): void
    {
        $area = Area::factory()->create(['area' => 'Tecnología']);
        $cargo = Cargo::factory()->create(['area_id' => $area->id_area]);
        $usuario = User::factory()->create([
            'id_area' => $area->id_area,
            'id_cargo' => $cargo->id_cargo,
        ]);

        $sistema = Sistema::factory()->create(['nombre_sistema' => 'Bflow']);
        AccesoSistema::factory()->create([
            'usuario_id' => $usuario->id,
            'id_sistema' => $sistema->id_sistema,
            'nivel_acceso' => 'Usuario',
            'estado' => true,
        ]);
        AccesoInternet::factory()->create(['usuario_id' => $usuario->id, 'estado' => true]);

        $software = Software::factory()->create([
            'nombre_software' => 'Adobe Acrobat',
            'categoria' => CategoriasSoftware::PDF->value,
        ]);
        SoftwareUsuario::factory()->create([
            'usuario_id' => $usuario->id,
            'id_software' => $software->id_software,
            'estado' => true,
        ]);

        $equipo = Equipo::factory()->create(['nombre_equipo' => 'BOCSIA06']);
        AsignacionEquipo::factory()->create([
            'usuario_id' => $usuario->id,
            'service_tag' => $equipo->service_tag,
            'fecha_inicio' => '2026-01-10',
            'fecha_fin' => null,
        ]);

        Movimiento::query()->create([
            'usuario_id' => $usuario->id,
            'tipo' => 'Alta',
            'fecha' => '2026-01-10',
            'cargo_id' => $cargo->id_cargo,
        ]);

        // El observer desactiva todo lo anterior al registrar la Baja.
        $baja = Movimiento::query()->create([
            'usuario_id' => $usuario->id,
            'tipo' => 'Baja',
            'fecha' => '2026-04-30',
            'cargo_id' => $cargo->id_cargo,
        ]);

        $documento = app(MovimientoFormularioPdfData::class)->for($baja)['documento'];

        $this->assertSame('Baja', $documento['tipo']);
        $this->assertSame('30/04/2026', $documento['fecha_baja']);
        $this->assertSame('10/01/2026', $documento['fecha_alta']);
        $this->assertSame('Bflow - Usuario', $documento['software']['sistemas']);
        $this->assertSame('Adobe Acrobat', $documento['software']['pdf']);
        $this->assertSame('Tecnología (T)', $documento['software']['internet']);
        $this->assertSame('BOCSIA06', $documento['equipo']['nombre']);
    }

    public function test_it_generates_and_stores_oficial_pdf_for_movimiento(): void
    {
        $cargo = Cargo::factory()->create();
        $usuario = User::factory()->create([
            'id_cargo' => $cargo->id_cargo,
            'nombres' => 'Ana',
            'apellidos' => 'Pérez',
        ]);

        $movimiento = Movimiento::query()->create([
            'usuario_id' => $usuario->id,
            'tipo' => 'Alta',
            'fecha' => '2026-04-18',
            'cargo_id' => $cargo->id_cargo,
        ]);

        $pdf = app(GenerateMovimientoFormularioPdf::class)
            ->execute($movimiento, MovimientoFormularioPdfTipo::AltaBaja);

        $this->assertInstanceOf(PdfMovimiento::class, $pdf);
        $this->assertSame(MovimientoFormularioPdfTipo::AltaBaja->nombreOficial(), $pdf->nombre);
        $this->assertSame('pdf-movimientos/FU-'.$movimiento->id.'.pdf', $pdf->url);
        $this->assertTrue($movimiento->fresh()->hasDocumentoOficial());
        Storage::disk('public')->assertExists($pdf->url);
    }

    public function test_preview_route_renders_real_user_data(): void
    {
        $cargo = Cargo::factory()->create();
        $usuario = User::factory()->create([
            'nombres' => 'Maria',
            'apellidos' => 'Lopez',
            'id_cargo' => $cargo->id_cargo,
        ]);

        $movimiento = Movimiento::query()->create([
            'usuario_id' => $usuario->id,
            'tipo' => 'Baja',
            'fecha' => '2026-05-10',
            'cargo_id' => $cargo->id_cargo,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('movimientos.preview-alta-baja', $movimiento))
            ->assertOk()
            ->assertSee('Maria', false)
            ->assertSee('Lopez', false)
            ->assertSee('Baja', false)
            ->assertSee('Acceso a DAZA', false)
            ->assertSee('Excepciones', false)
            ->assertDontSee('FortiToken', false)
            ->assertDontSee('Complemento de asignaciones', false);
    }

    public function test_clasifica_por_categoria_y_muestra_daza_y_excepciones(): void
    {
        $area = Area::factory()->create(['area' => 'Riesgos']);
        $cargo = Cargo::factory()->create(['area_id' => $area->id_area]);
        $usuario = User::factory()->create([
            'id_area' => $area->id_area,
            'id_cargo' => $cargo->id_cargo,
        ]);

        SoftwareUsuario::factory()->create([
            'usuario_id' => $usuario->id,
            'estado' => true,
            'id_software' => Software::factory()->create([
                'nombre_software' => 'Adobe Acrobat',
                'categoria' => CategoriasSoftware::PDF->value,
            ])->id_software,
        ]);
        $virtual = Virtual::factory()->create(['maquina_virtual' => 'VM-CONTABILIDAD']);
        $vpn = RecursoVpn::factory()->create(['nombre_vpn' => 'VPN Corporativa']);
        AccesoVirtual::factory()->create([
            'usuario_id' => $usuario->id,
            'virtual_id' => $virtual->id,
            'vpn_id' => $vpn->id,
            'estado' => true,
        ]);

        $fondo = Fondo::factory()->create(['nombre_completo' => 'Fondo Capital']);
        AccesoDaza::factory()->create([
            'usuario_id' => $usuario->id,
            'usuario_daza' => 'cduran',
            'nivel_acceso' => 'Lectura',
            'fondo_id' => $fondo->id_fondo,
            'ambiente' => 'Produccion',
            'estado' => true,
        ]);

        SoftwareExc::factory()->aprobada()->create([
            'usuario_id' => $usuario->id,
            'fecha_fin' => null,
            'software_id' => Software::factory()->create([
                'nombre_software' => 'AnyDesk',
                'categoria' => CategoriasSoftware::SoporteRemoto->value,
            ])->id_software,
        ]);
        SoftwareExc::factory()->create([
            'usuario_id' => $usuario->id,
            'estado_aprobacion' => EstadoAprobacionExcepcion::Pendiente,
            'fecha_fin' => null,
            'software_id' => Software::factory()->create([
                'nombre_software' => 'TeamViewer',
                'categoria' => CategoriasSoftware::SoporteRemoto->value,
            ])->id_software,
        ]);

        $movimiento = Movimiento::query()->create([
            'usuario_id' => $usuario->id,
            'tipo' => 'Alta',
            'fecha' => '2026-04-01',
            'cargo_id' => $cargo->id_cargo,
        ]);

        $documento = app(MovimientoFormularioPdfData::class)->for($movimiento)['documento'];

        $this->assertSame('Adobe Acrobat', $documento['software']['pdf']);
        $this->assertSame('-', $documento['software']['compresion']);
        $this->assertSame('VM-CONTABILIDAD', $documento['software']['escritorio_remoto']);
        $this->assertSame('VPN Corporativa', $documento['software']['vpn']);
        $this->assertSame('AnyDesk', $documento['software']['soporte_remoto']);
        $this->assertStringContainsString('TeamViewer (Pendiente)', $documento['software']['excepciones']);
        $this->assertStringContainsString('AnyDesk (Aprobada)', $documento['software']['excepciones']);
        $this->assertSame('cduran - Lectura - Fondo Capital - Produccion', $documento['software']['daza']);
        $this->assertArrayNotHasKey('complemento', $documento);
    }
}
