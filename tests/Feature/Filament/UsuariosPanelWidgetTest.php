<?php

namespace Tests\Feature\Filament;

use App\Filament\Widgets\UsuariosPanel\AccesosTab;
use App\Filament\Widgets\UsuariosPanel\LicenciasTab;
use App\Filament\Widgets\UsuariosPanel\SoftwareTab;
use App\Filament\Widgets\UsuariosPanel\UsuariosTab;
use App\Filament\Widgets\UsuariosPanelWidget;
use App\Models\AccesoSistema;
use App\Models\Area;
use App\Models\Licencia;
use App\Models\LicenciaUsuario;
use App\Models\Movimiento;
use App\Models\Sistema;
use App\Models\Software;
use App\Models\SoftwareUsuario;
use App\Models\User;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UsuariosPanelWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->givePermissionTo(Permission::firstOrCreate(['name' => 'View:UsuariosPanelWidget', 'guard_name' => 'web']));

        $this->admin = User::factory()->create(['name' => 'Admin Sistema']);
        $this->admin->assignRole($superAdmin);

        $this->actingAs($this->admin);
    }

    public function test_el_panel_abre_en_usuarios_y_cambia_de_pestana(): void
    {
        Livewire::test(UsuariosPanelWidget::class)
            ->assertSeeLivewire(UsuariosTab::class)
            ->set('pestana', 'licencias')
            ->assertViewHas('componente', LicenciasTab::class)
            ->set('pestana', 'accesos')
            ->assertViewHas('componente', AccesosTab::class);
    }

    public function test_una_pestana_desconocida_vuelve_a_usuarios(): void
    {
        Livewire::test(UsuariosPanelWidget::class)
            ->set('pestana', '../../config')
            ->assertViewHas('activa', 'usuarios')
            ->assertViewHas('componente', UsuariosTab::class);
    }

    public function test_un_solo_permiso_cubre_el_panel_y_sus_pestanas(): void
    {
        $this->assertArrayHasKey(UsuariosPanelWidget::class, FilamentShield::getWidgets());
        $this->assertArrayNotHasKey(UsuariosTab::class, FilamentShield::getWidgets());
        $this->assertFalse(UsuariosTab::isDiscovered());

        $this->assertTrue(UsuariosTab::canView());

        $this->actingAs(User::factory()->create());

        $this->assertFalse(UsuariosPanelWidget::canView());
        $this->assertFalse(UsuariosTab::canView());
        $this->assertFalse(AccesosTab::canView());
    }

    public function test_la_pestana_usuarios_muestra_a_todos_sin_importar_el_movimiento(): void
    {
        $deAlta = $this->usuarioConMovimiento('Alta');
        $deBaja = $this->usuarioConMovimiento('Baja');
        $sinMovimiento = User::factory()->create();

        Livewire::test(UsuariosTab::class)
            ->assertCanSeeTableRecords([$deAlta, $deBaja, $sinMovimiento])
            ->assertTableColumnStateSet('ultimoMovimiento.tipo', 'Alta', $deAlta)
            ->assertTableColumnStateSet('ultimoMovimiento.tipo', 'Baja', $deBaja);
    }

    public function test_la_pestana_usuarios_filtra_por_movimiento(): void
    {
        $deAlta = $this->usuarioConMovimiento('Alta');
        $deBaja = $this->usuarioConMovimiento('Baja');

        Livewire::test(UsuariosTab::class)
            ->filterTable('movimiento', 'Baja')
            ->assertCanSeeTableRecords([$deBaja])
            ->assertCanNotSeeTableRecords([$deAlta]);
    }

    public function test_la_pestana_usuarios_busca_por_correo_y_filtra_por_area(): void
    {
        $area = Area::factory()->create();
        $buscado = User::factory()->create(['email' => 'buscado@capitalsafi.com', 'id_area' => $area->id_area]);
        $otro = User::factory()->create(['email' => 'otro@capitalsafi.com']);

        Livewire::test(UsuariosTab::class)
            ->searchTable('buscado@')
            ->assertCanSeeTableRecords([$buscado])
            ->assertCanNotSeeTableRecords([$otro]);

        Livewire::test(UsuariosTab::class)
            ->filterTable('id_area', [$area->id_area])
            ->assertCanSeeTableRecords([$buscado])
            ->assertCanNotSeeTableRecords([$otro]);
    }

    public function test_la_pestana_usuarios_muestra_y_filtra_el_2fa_del_sistema(): void
    {
        $conDosFactores = User::factory()->create();
        $conDosFactores->breezySessions()->create([
            'two_factor_secret' => 'SECRETO-DE-PRUEBA',
            'two_factor_confirmed_at' => now(),
        ]);
        $sinConfirmar = User::factory()->create();
        $sinConfirmar->breezySessions()->create(['two_factor_secret' => 'SECRETO-DE-PRUEBA']);

        Livewire::test(UsuariosTab::class)
            ->assertTableColumnStateSet('dos_factores', true, $conDosFactores)
            ->assertTableColumnStateSet('dos_factores', false, $sinConfirmar)
            ->filterTable('dos_factores', true)
            ->assertCanSeeTableRecords([$conDosFactores])
            ->assertCanNotSeeTableRecords([$sinConfirmar]);
    }

    public function test_la_pestana_usuarios_muestra_el_rol(): void
    {
        Livewire::test(UsuariosTab::class)
            ->assertTableColumnStateSet('roles.name', ['super_admin'], $this->admin);
    }

    public function test_la_pestana_licencias_muestra_solo_las_activas_y_filtra_por_licencia(): void
    {
        $office = Licencia::factory()->create(['nombre_licencia' => 'Office 365', 'estado' => true]);
        $adobe = Licencia::factory()->create(['nombre_licencia' => 'Adobe Pro', 'estado' => true]);

        $usuario = User::factory()->create();
        LicenciaUsuario::factory()->create(['usuario_id' => $usuario->id, 'licencia_id' => $office->id, 'estado' => true]);
        LicenciaUsuario::factory()->create(['usuario_id' => $usuario->id, 'licencia_id' => $adobe->id, 'estado' => false]);

        $otro = User::factory()->create();
        LicenciaUsuario::factory()->create(['usuario_id' => $otro->id, 'licencia_id' => $adobe->id, 'estado' => true]);

        Livewire::test(LicenciasTab::class)
            ->assertTableColumnStateSet('licencias', ['Office 365'], $usuario)
            ->filterTable('licencia', [$office->id])
            ->assertCanSeeTableRecords([$usuario])
            ->assertCanNotSeeTableRecords([$otro]);
    }

    public function test_la_pestana_licencias_busca_por_nombre_de_licencia(): void
    {
        $office = Licencia::factory()->create(['nombre_licencia' => 'Office 365', 'estado' => true]);
        $usuario = User::factory()->create();
        LicenciaUsuario::factory()->create(['usuario_id' => $usuario->id, 'licencia_id' => $office->id, 'estado' => true]);
        $sinLicencia = User::factory()->create();

        Livewire::test(LicenciasTab::class)
            ->searchTable('office')
            ->assertCanSeeTableRecords([$usuario])
            ->assertCanNotSeeTableRecords([$sinLicencia]);
    }

    public function test_la_pestana_software_muestra_el_software_activo(): void
    {
        $software = Software::factory()->create(['nombre_software' => 'AutoCAD']);
        $usuario = User::factory()->create();
        SoftwareUsuario::factory()->create(['usuario_id' => $usuario->id, 'id_software' => $software->id_software, 'estado' => true]);
        $otro = User::factory()->create();

        Livewire::test(SoftwareTab::class)
            ->assertTableColumnStateSet('software', ['AutoCAD'], $usuario)
            ->filterTable('software', [$software->id_software])
            ->assertCanSeeTableRecords([$usuario])
            ->assertCanNotSeeTableRecords([$otro]);
    }

    public function test_la_pestana_accesos_muestra_badges_por_tipo_y_filtra_por_tipo(): void
    {
        $sistema = Sistema::factory()->create(['nombre_sistema' => 'SIGMA']);
        $conAcceso = User::factory()->create();
        AccesoSistema::factory()->create(['usuario_id' => $conAcceso->id, 'id_sistema' => $sistema->id_sistema, 'estado' => true]);
        $sinAcceso = User::factory()->create();

        Livewire::test(AccesosTab::class)
            ->assertTableColumnStateSet('sistemas', ['SIGMA'], $conAcceso)
            ->filterTable('tipo_acceso', ['sistemas', 'no-existe'])
            ->assertCanSeeTableRecords([$conAcceso])
            ->assertCanNotSeeTableRecords([$sinAcceso]);

        Livewire::test(AccesosTab::class)
            ->searchTable('sigma')
            ->assertCanSeeTableRecords([$conAcceso])
            ->assertCanNotSeeTableRecords([$sinAcceso]);
    }

    public function test_mostrar_mas_renderiza_los_badges_que_superan_el_limite(): void
    {
        $usuario = User::factory()->create();
        $nombres = ['Adobe Pro', 'AutoCAD', 'Office 365', 'Project', 'Visio'];

        foreach ($nombres as $nombre) {
            $licencia = Licencia::factory()->create(['nombre_licencia' => $nombre, 'estado' => true]);
            LicenciaUsuario::factory()->create(['usuario_id' => $usuario->id, 'licencia_id' => $licencia->id, 'estado' => true]);
        }

        Livewire::test(LicenciasTab::class)
            ->assertTableColumnStateSet('licencias', $nombres, $usuario)
            ->assertSee('Visio')
            ->assertSee('Mostrar 2 más')
            ->assertSee('Mostrar 2 menos')
            ->assertSeeHtml('x-show="! isLimited"');
    }

    public function test_los_tipos_de_acceso_poco_usados_arrancan_ocultos_pero_se_pueden_activar(): void
    {
        Livewire::test(AccesosTab::class)
            ->assertCanRenderTableColumn('sistemas')
            ->assertCanNotRenderTableColumn('fortitoken')
            ->toggleAllTableColumns()
            ->assertCanRenderTableColumn('fortitoken');
    }

    private function usuarioConMovimiento(string $tipo): User
    {
        $usuario = User::factory()->create();
        Movimiento::factory()->create(['usuario_id' => $usuario->id, 'tipo' => $tipo, 'fecha' => now()->subDay()]);

        return $usuario;
    }
}
