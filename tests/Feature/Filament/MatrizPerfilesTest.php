<?php

namespace Tests\Feature\Filament;

use App\Enums\EstadoAprobacionExcepcion;
use App\Filament\Widgets\UsuariosPanel\AccesosTab;
use App\Filament\Widgets\UsuariosPanel\UsuariosTab;
use App\Models\AccesoInternet;
use App\Models\AccesoSharepoint;
use App\Models\AccesoSistema;
use App\Models\AccesoVirtual;
use App\Models\AccUdCompartida;
use App\Models\Area;
use App\Models\AzureAd;
use App\Models\Cargo;
use App\Models\Licencia;
use App\Models\LicenciaUsuario;
use App\Models\Movimiento;
use App\Models\RecursoVpn;
use App\Models\Sistema;
use App\Models\SitioSharepoint;
use App\Models\Software;
use App\Models\SoftwareArea;
use App\Models\SoftwareExc;
use App\Models\SoftwareUsuario;
use App\Models\User;
use App\Models\Virtual;
use App\Support\MatrizPerfiles\MatrizPerfiles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use OpenSpout\Reader\XLSX\Reader;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MatrizPerfilesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Area $area;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->givePermissionTo(Permission::firstOrCreate(['name' => 'View:UsuariosPanelWidget', 'guard_name' => 'web']));

        $this->area = Area::factory()->create(['area' => 'Auditoría Interna']);

        $this->admin = User::factory()->create(['id_cargo' => null]);
        $this->admin->assignRole($superAdmin);

        $this->actingAs($this->admin);
    }

    public function test_cada_cargo_sale_una_vez_y_une_los_accesos_de_sus_personas_sin_repetir(): void
    {
        $cargo = $this->cargo('Auditor Senior');
        $primero = $this->usuarioDe($cargo);
        $segundo = $this->usuarioDe($cargo);

        $viafirma = Sistema::factory()->create(['nombre_sistema' => 'Viafirma']);
        $eflow = Sistema::factory()->create(['nombre_sistema' => 'Eflow']);

        $this->accesoASistema($primero, $viafirma, 'Usuario');
        $this->accesoASistema($primero, $eflow, 'Usuario');
        $this->accesoASistema($segundo, $viafirma, 'Usuario');

        $filas = $this->filasPorCargo();

        $this->assertCount(1, array_filter(array_keys($filas), fn (string $nombre): bool => $nombre === 'Auditor Senior'));
        $this->assertSame('Eflow - Usuario | Viafirma - Usuario', $filas['Auditor Senior']['Sistemas']);
    }

    public function test_las_columnas_de_accesos_usan_el_separador_y_guion_si_no_hay_datos(): void
    {
        $cargo = $this->cargo('Analista de Riesgos');
        $usuario = $this->usuarioDe($cargo);

        $sitio = SitioSharepoint::factory()->create(['nombre_sitio' => 'Riesgos']);
        AccesoSharepoint::factory()->create(['usuario_id' => $usuario->id, 'sitio_id' => $sitio->id, 'estado' => true]);

        $vpn = RecursoVpn::factory()->create(['nombre_vpn' => 'VPN Oficina']);
        $virtual = Virtual::factory()->create(['maquina_virtual' => 'VM-RIESGOS']);
        AccesoVirtual::factory()->create(['usuario_id' => $usuario->id, 'virtual_id' => $virtual->id, 'vpn_id' => $vpn->id, 'estado' => true]);

        AccUdCompartida::factory()->create(['usuario_id' => $usuario->id, 'unidad_compartida' => '\\\\srv\\riesgos', 'estado' => true]);
        AccesoInternet::factory()->create(['usuario_id' => $usuario->id, 'nivel_acceso' => 'Usuario Estándar', 'estado' => true]);

        $fila = $this->filasPorCargo()['Analista de Riesgos'];

        $this->assertSame('-', $fila['Sistemas']);
        $this->assertSame('Riesgos', $fila['Sitios SharePoint']);
        $this->assertSame('Auditoría Interna (AI)', $fila['Internet']);
        $this->assertSame('VM-RIESGOS', $fila['Escritorio remoto']);
        $this->assertSame('\\\\srv\\riesgos', $fila['Unidad compartida']);
        $this->assertSame('VPN Oficina', $fila['VPN']);
    }

    public function test_no_cuentan_las_asignaciones_inactivas_ni_las_personas_de_baja(): void
    {
        $sistema = Sistema::factory()->create(['nombre_sistema' => 'SIGMA']);

        $cargo = $this->cargo('Contadora');
        $activa = $this->usuarioDe($cargo);
        AccesoSistema::factory()->create(['usuario_id' => $activa->id, 'id_sistema' => $sistema->id_sistema, 'nivel_acceso' => 'Lector', 'estado' => false]);

        $cargoDeBaja = $this->cargo('Encargado Temporal de Mensajeria');
        $deBaja = $this->usuarioDe($cargoDeBaja);
        Movimiento::factory()->create(['usuario_id' => $deBaja->id, 'cargo_id' => $cargoDeBaja->id_cargo, 'tipo' => 'Baja', 'fecha' => now()->subDay()]);

        $filas = $this->filasPorCargo();

        $this->assertSame('-', $filas['Contadora']['Sistemas']);
        $this->assertArrayNotHasKey('Encargado Temporal de Mensajeria', $filas);
    }

    public function test_internet_sale_vacio_sin_acceso_activo(): void
    {
        $usuario = $this->usuarioDe($this->cargo('Asistente Legal'));
        AccesoInternet::factory()->create(['usuario_id' => $usuario->id, 'estado' => false]);

        $this->assertSame('-', $this->filasPorCargo()['Asistente Legal']['Internet']);
    }

    public function test_el_software_sale_del_usuario_y_su_area_sin_las_excepciones(): void
    {
        $cargo = $this->cargo('Auditor Interno');
        $usuario = $this->usuarioDe($cargo);

        $this->softwareDelArea('Chrome', 'navegadores');
        $this->softwareDelArea('Firefox', 'navegadores');
        $this->softwareDelArea('FortiClient', 'vpn');
        $this->softwareDelArea('Inactivo', 'pdf', activo: false);

        $asignado = Software::factory()->create(['nombre_software' => 'Asignado', 'categoria' => 'navegadores', 'estado' => true]);
        SoftwareUsuario::factory()->create(['usuario_id' => $usuario->id, 'id_software' => $asignado->id_software, 'estado' => true]);

        $excepcion = Software::factory()->create(['nombre_software' => 'AnyDesk', 'categoria' => 'soporte_remoto', 'estado' => true]);
        SoftwareExc::factory()->create([
            'usuario_id' => $usuario->id,
            'software_id' => $excepcion->id_software,
            'estado_aprobacion' => EstadoAprobacionExcepcion::Aprobada,
            'fecha_fin' => null,
        ]);

        $office = Licencia::factory()->create(['nombre_licencia' => 'Office 365 E3', 'estado' => true]);
        LicenciaUsuario::factory()->create(['usuario_id' => $usuario->id, 'licencia_id' => $office->id, 'estado' => true]);

        AzureAd::factory()->create(['usuario_id' => $usuario->id, 'mfa' => true, 'estado' => true]);

        $matriz = new MatrizPerfiles;
        $cabeceras = $matriz->cabeceras();
        $fila = $this->filasPorCargo($matriz)['Auditor Interno'];

        $this->assertSame(
            ['Cargo', 'Sistemas', 'Sitios SharePoint', 'Internet', 'Escritorio remoto', 'Unidad compartida', 'VPN',
                'Navegadores', 'Microsoft', 'Autenticación multifactor', 'Software VPN'],
            $cabeceras,
        );
        $this->assertSame('Asignado | Chrome | Firefox', $fila['Navegadores']);
        $this->assertSame('Office 365 E3', $fila['Microsoft']);
        $this->assertSame('Microsoft Authenticator', $fila['Autenticación multifactor']);
        $this->assertSame('FortiClient', $fila['Software VPN']);
        $this->assertNotContains('PDF', $cabeceras);
        $this->assertNotContains('Soporte remoto', $cabeceras);
    }

    public function test_la_accion_descarga_un_excel_con_la_matriz(): void
    {
        $cargo = $this->cargo('Gerente General');
        $usuario = $this->usuarioDe($cargo);
        $this->accesoASistema($usuario, Sistema::factory()->create(['nombre_sistema' => 'ChatGPT']), 'Miembro');

        $componente = Livewire::test(UsuariosTab::class)
            ->assertActionExists('matrizDePerfiles')
            ->callAction('matrizDePerfiles');

        $descarga = $componente->effects['download'] ?? null;

        $this->assertNotNull($descarga, 'La acción no produjo ninguna descarga.');
        $this->assertStringEndsWith('.xlsx', $descarga['name']);

        $filas = $this->leerExcel(base64_decode($descarga['content']));

        $this->assertSame(['Cargo', 'Sistemas'], array_slice($filas[0], 0, 2));
        $this->assertSame(['Gerente General', 'ChatGPT - Miembro'], array_slice($filas[1], 0, 2));
    }

    public function test_la_accion_esta_en_todas_las_pestanas(): void
    {
        Livewire::test(AccesosTab::class)->assertActionExists('matrizDePerfiles');
    }

    private function cargo(string $nombre): Cargo
    {
        return Cargo::factory()->create(['cargo' => $nombre, 'area_id' => $this->area->id_area]);
    }

    private function usuarioDe(Cargo $cargo): User
    {
        return User::factory()->create(['id_cargo' => $cargo->id_cargo, 'id_area' => $this->area->id_area]);
    }

    private function accesoASistema(User $usuario, Sistema $sistema, string $nivel): void
    {
        AccesoSistema::factory()->create([
            'usuario_id' => $usuario->id,
            'id_sistema' => $sistema->id_sistema,
            'nivel_acceso' => $nivel,
            'estado' => true,
        ]);
    }

    private function softwareDelArea(string $nombre, string $categoria, bool $activo = true): void
    {
        $software = Software::factory()->create(['nombre_software' => $nombre, 'categoria' => $categoria, 'estado' => $activo]);

        SoftwareArea::factory()->create(['software_id' => $software->id_software, 'area_id' => $this->area->id_area]);
    }

    /**
     * @return array<string, array<string, string>> cargo => [cabecera => celda]
     */
    private function filasPorCargo(?MatrizPerfiles $matriz = null): array
    {
        $matriz ??= new MatrizPerfiles;
        $cabeceras = $matriz->cabeceras();

        return collect($matriz->filas())
            ->mapWithKeys(fn (array $fila): array => [$fila[0] => array_combine($cabeceras, $fila)])
            ->all();
    }

    /**
     * @return list<list<mixed>>
     */
    private function leerExcel(string $contenido): array
    {
        $ruta = tempnam(sys_get_temp_dir(), 'matriz').'.xlsx';
        file_put_contents($ruta, $contenido);

        $reader = new Reader;
        $reader->open($ruta);

        $filas = [];

        foreach ($reader->getSheetIterator() as $hoja) {
            foreach ($hoja->getRowIterator() as $fila) {
                $filas[] = $fila->toArray();
            }
        }

        $reader->close();
        unlink($ruta);

        return $filas;
    }
}
