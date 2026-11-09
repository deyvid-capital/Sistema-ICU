<?php

namespace Tests\Feature\Filament;

use App\Enums\EstadoCertificadoFirma;
use App\Enums\EstadoDocumentoFirma;
use App\Enums\EstadoEquipo;
use App\Enums\EstadoFirma;
use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\AltasBajasChart;
use App\Filament\Widgets\EntregasRecepcionesChart;
use App\Filament\Widgets\EquiposPorEstadoChart;
use App\Filament\Widgets\GarantiasPorVencerTable;
use App\Filament\Widgets\MisFirmasPendientesTable;
use App\Filament\Widgets\ResumenGeneralStats;
use App\Filament\Widgets\UsoLicenciasChart;
use App\Filament\Widgets\UsuariosPanelWidget;
use App\Models\CertificadoFirma;
use App\Models\EntregaEquipo;
use App\Models\Equipo;
use App\Models\Licencia;
use App\Models\LicenciaUsuario;
use App\Models\Movimiento;
use App\Models\RecepcionEquipo;
use App\Models\Software;
use App\Models\SolicitudFirma;
use App\Models\SolicitudFirmaDocumento;
use App\Models\SolicitudFirmaFirma;
use App\Models\SolicitudFirmaFirmante;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DashboardWidgetsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Permisos que shield:generate crea para los widgets (el super_admin los recibe en RolesPermissionsSeeder).
     */
    private const PERMISOS_DE_WIDGETS = [
        'View:ResumenGeneralStats',
        'View:EquiposPorEstadoChart',
        'View:AltasBajasChart',
        'View:UsoLicenciasChart',
        'View:EntregasRecepcionesChart',
        'View:GarantiasPorVencerTable',
        'View:UsuariosPanelWidget',
    ];

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->givePermissionTo(collect(self::PERMISOS_DE_WIDGETS)
            ->map(fn (string $nombre): Permission => Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web'])));

        $this->admin = User::factory()->create();
        $this->admin->assignRole($superAdmin);

        $this->actingAs($this->admin);
    }

    public function test_el_dashboard_propio_carga_para_el_super_admin(): void
    {
        $this->get(Dashboard::getUrl(panel: 'admin'))->assertOk();
    }

    public function test_los_stats_cuentan_equipos_asignados_y_disponibles(): void
    {
        Equipo::factory()->count(2)->create(['estado' => EstadoEquipo::Asignado]);
        Equipo::factory()->count(3)->create(['estado' => EstadoEquipo::SinAsignar]);
        Equipo::factory()->create(['estado' => EstadoEquipo::Robo]);

        Livewire::test(ResumenGeneralStats::class)
            ->assertSeeText('Equipos asignados')
            ->assertSeeText('33% de 6 equipos')
            ->assertSeeText('Equipos disponibles')
            ->assertSeeText('50% listos para asignar');
    }

    public function test_los_stats_suman_solo_los_cupos_de_licencias_activas(): void
    {
        $activa = Licencia::factory()->create(['estado' => true, 'total_licencias' => 10]);
        Licencia::factory()->create(['estado' => false, 'total_licencias' => 50]);
        $this->asignarLicencia($activa, 3);

        Livewire::test(ResumenGeneralStats::class)
            ->assertSeeText('Total de licencias')
            ->assertSeeText('3 asignadas · 7 libres');
    }

    public function test_los_stats_cuentan_software_y_autorizados(): void
    {
        Software::factory()->count(2)->create(['estado' => true]);
        Software::factory()->create(['estado' => false]);

        Livewire::test(ResumenGeneralStats::class)
            ->assertSeeText('Total de software')
            ->assertSeeText('2 autorizados');
    }

    public function test_alta_y_baja_dependen_del_ultimo_movimiento_del_usuario(): void
    {
        $sigueDeAlta = User::factory()->create();
        Movimiento::factory()->alta()->create(['usuario_id' => $sigueDeAlta->id, 'fecha' => now()->subMonths(2)]);

        $dadoDeBaja = User::factory()->create();
        Movimiento::factory()->alta()->create(['usuario_id' => $dadoDeBaja->id, 'fecha' => now()->subMonths(3)]);
        Movimiento::factory()->baja()->create(['usuario_id' => $dadoDeBaja->id, 'fecha' => now()->subMonth()]);

        Livewire::test(ResumenGeneralStats::class)
            ->assertSeeText('Usuarios')
            ->assertSeeText('1 de alta · 1 de baja');
    }

    public function test_los_stats_cuentan_firmas_activas_y_avisan_las_que_vencen_pronto(): void
    {
        CertificadoFirma::factory()->activo()->create(['vigente_hasta' => now()->addDays(10)]);
        CertificadoFirma::factory()->activo()->create(['vigente_hasta' => now()->addYear()]);
        CertificadoFirma::factory()->activo()->create(['estado' => EstadoCertificadoFirma::Revocado]);

        Livewire::test(ResumenGeneralStats::class)
            ->assertSeeText('Firmas digitales activas')
            ->assertSeeText('1 vencen en 30 días');
    }

    public function test_el_chart_de_estados_agrupa_los_equipos_y_queda_vacio_sin_datos(): void
    {
        $this->assertSame([], $this->datosDe(Livewire::test(EquiposPorEstadoChart::class)->instance()));

        Equipo::factory()->count(2)->create(['estado' => EstadoEquipo::Asignado]);
        Equipo::factory()->create(['estado' => EstadoEquipo::EnMantenimiento]);

        $datos = $this->datosDe(Livewire::test(EquiposPorEstadoChart::class)->instance());

        $this->assertSame(['Asignado', 'En mantenimiento'], $datos['labels']);
        $this->assertSame([2, 1], $datos['datasets'][0]['data']);
    }

    public function test_el_chart_de_altas_y_bajas_cuenta_por_mes(): void
    {
        $usuario = User::factory()->create();
        Movimiento::factory()->alta()->create(['usuario_id' => $usuario->id, 'fecha' => now()->startOfMonth()]);
        Movimiento::factory()->baja()->create(['usuario_id' => $usuario->id, 'fecha' => now()->startOfMonth()->addDay()]);
        Movimiento::factory()->alta()->create(['usuario_id' => User::factory()->create()->id, 'fecha' => now()->subMonthNoOverflow()->startOfMonth()]);

        $datos = $this->datosDe(Livewire::test(AltasBajasChart::class)->instance());

        $this->assertCount(12, $datos['labels']);
        $this->assertSame([1, 1], array_slice($datos['datasets'][0]['data'], -2));
        $this->assertSame([0, 1], array_slice($datos['datasets'][1]['data'], -2));
    }

    public function test_el_filtro_de_periodo_cambia_los_meses_y_un_valor_invalido_vuelve_a_12(): void
    {
        $seis = $this->datosDe(Livewire::test(AltasBajasChart::class)->set('filter', '6')->instance());
        $this->assertCount(6, $seis['labels']);

        $anio = $this->datosDe(Livewire::test(AltasBajasChart::class)->set('filter', 'anio')->instance());
        $this->assertCount(now()->month, $anio['labels']);

        $invalido = $this->datosDe(Livewire::test(AltasBajasChart::class)->set('filter', "1'; DROP TABLE movimientos; --")->instance());
        $this->assertCount(12, $invalido['labels']);
    }

    public function test_el_chart_de_entregas_y_recepciones_cuenta_por_mes(): void
    {
        EntregaEquipo::factory()->count(2)->create(['fecha' => now()->startOfMonth()]);
        RecepcionEquipo::factory()->create(['fecha' => now()->startOfMonth()]);
        EntregaEquipo::factory()->create(['fecha' => now()->subYears(2)]);

        $datos = $this->datosDe(Livewire::test(EntregasRecepcionesChart::class)->instance());

        $this->assertSame(2, array_sum($datos['datasets'][0]['data']));
        $this->assertSame(1, array_sum($datos['datasets'][1]['data']));
        $this->assertSame(2, last($datos['datasets'][0]['data']));
    }

    public function test_el_chart_de_licencias_separa_asignadas_libres_y_exceso(): void
    {
        $normal = Licencia::factory()->create(['nombre_licencia' => 'A-Office', 'estado' => true, 'total_licencias' => 5]);
        $excedida = Licencia::factory()->create(['nombre_licencia' => 'B-Adobe', 'estado' => true, 'total_licencias' => 2]);
        Licencia::factory()->create(['nombre_licencia' => 'C-Inactiva', 'estado' => false]);

        $this->asignarLicencia($normal, 2);
        $this->asignarLicencia($excedida, 2);
        DB::table('licencias')->where('id', $excedida->id)->update(['total_licencias' => 1]);

        $datos = $this->datosDe(Livewire::test(UsoLicenciasChart::class)->instance());

        $this->assertSame(['A-Office', 'B-Adobe'], $datos['labels']);
        $this->assertSame([2, 1], $datos['datasets'][0]['data']);
        $this->assertSame([3, 0], $datos['datasets'][1]['data']);
        $this->assertSame('Sobre el cupo', $datos['datasets'][2]['label']);
        $this->assertSame([0, 1], $datos['datasets'][2]['data']);
    }

    public function test_la_tabla_de_garantias_solo_muestra_las_que_vencen_en_90_dias(): void
    {
        $pronto = Equipo::factory()->create(['venc_garantia' => today()->addDays(20)]);
        $lejos = Equipo::factory()->create(['venc_garantia' => today()->addDays(200)]);
        $vencida = Equipo::factory()->create(['venc_garantia' => today()->subDay()]);

        Livewire::test(GarantiasPorVencerTable::class)
            ->assertCanSeeTableRecords([$pronto])
            ->assertCanNotSeeTableRecords([$lejos, $vencida]);
    }

    public function test_mis_firmas_pendientes_muestra_solo_lo_que_el_usuario_debe_firmar(): void
    {
        $mia = $this->solicitudConFirmaDe($this->admin, EstadoFirma::Pendiente);
        $yaFirmada = $this->solicitudConFirmaDe($this->admin, EstadoFirma::Firmado);
        $deOtro = $this->solicitudConFirmaDe(User::factory()->create(), EstadoFirma::Pendiente);

        Livewire::test(MisFirmasPendientesTable::class)
            ->assertCanSeeTableRecords([$mia])
            ->assertCanNotSeeTableRecords([$yaFirmada, $deOtro]);
    }

    public function test_mis_firmas_pendientes_se_oculta_a_quien_no_firma(): void
    {
        $this->actingAs(User::factory()->create());
        $this->assertFalse(MisFirmasPendientesTable::canView());

        $firmante = User::factory()->create();
        $this->solicitudConFirmaDe($firmante, EstadoFirma::Pendiente);
        $this->actingAs($firmante);
        $this->assertTrue(MisFirmasPendientesTable::canView());
    }

    public function test_los_widgets_con_permiso_se_ocultan_a_un_usuario_sin_rol(): void
    {
        $this->actingAs(User::factory()->create());

        $this->assertFalse(ResumenGeneralStats::canView());
        $this->assertFalse(EquiposPorEstadoChart::canView());
        $this->assertFalse(GarantiasPorVencerTable::canView());
        $this->assertFalse(UsuariosPanelWidget::canView());
    }

    public function test_el_super_admin_ve_los_widgets_con_permiso(): void
    {
        $this->assertTrue(ResumenGeneralStats::canView());
        $this->assertTrue(UsoLicenciasChart::canView());
        $this->assertTrue(UsuariosPanelWidget::canView());
    }

    /**
     * @return array<string, mixed>
     */
    private function datosDe(Widget $widget): array
    {
        return (fn (): array => $this->getData())->call($widget);
    }

    private function asignarLicencia(Licencia $licencia, int $cantidad): void
    {
        User::factory()->count($cantidad)->create()->each(
            fn (User $usuario) => LicenciaUsuario::factory()->create([
                'licencia_id' => $licencia->id,
                'usuario_id' => $usuario->id,
                'estado' => true,
            ]),
        );
    }

    private function solicitudConFirmaDe(User $usuario, EstadoFirma $estado): SolicitudFirma
    {
        $solicitud = SolicitudFirma::factory()->enCurso()->create();
        $documento = SolicitudFirmaDocumento::factory()->create([
            'solicitud_firma_id' => $solicitud->id,
            'estado' => EstadoDocumentoFirma::EnCurso,
        ]);
        $firmante = SolicitudFirmaFirmante::query()->create([
            'solicitud_firma_id' => $solicitud->id,
            'usuario_id' => $usuario->id,
            'orden' => 1,
        ]);

        SolicitudFirmaFirma::query()->create([
            'documento_id' => $documento->id,
            'firmante_id' => $firmante->id,
            'campo' => 'firma_1',
            'pagina' => 1,
            'x' => 10,
            'y' => 10,
            'ancho' => 150,
            'alto' => 50,
            'estado' => $estado,
        ]);

        return $solicitud;
    }
}
