<?php

namespace Tests\Feature;

use App\Enums\CategoriasSoftware;
use App\Models\AccesoSharepoint;
use App\Models\AccesoSistema;
use App\Models\AccesoVirtual;
use App\Models\AccUdCompartida;
use App\Models\Cargo;
use App\Models\Licencia;
use App\Models\LicenciaUsuario;
use App\Models\RecursoVpn;
use App\Models\Sistema;
use App\Models\SitioSharepoint;
use App\Models\Software;
use App\Models\SoftwareUsuario;
use App\Models\User;
use App\Models\UsuarioFortitoken;
use App\Models\Virtual;
use Database\Seeders\CatalogoPersonalSeeder;
use Database\Seeders\InventarioAccesosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventarioAccesosSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_carga_licencias_software_sistemas_y_maquinas_del_csv(): void
    {
        $this->sembrarPersonalYAdmin();
        $this->seed(InventarioAccesosSeeder::class);

        $this->assertSame(28, Software::query()->count());
        $this->assertSame(521, SoftwareUsuario::query()->count());
        $this->assertSame(18, Licencia::query()->count());
        $this->assertSame(187, LicenciaUsuario::query()->count());
        $this->assertSame(20, Sistema::query()->count());
        $this->assertSame(169, AccesoSistema::query()->count());
        $this->assertSame(9, SitioSharepoint::query()->count());
        $this->assertSame(110, AccesoSharepoint::query()->count());
        $this->assertSame(32, AccUdCompartida::query()->count());
        $this->assertSame(16, Virtual::query()->count());
        $this->assertSame(20, AccesoVirtual::query()->count());
        $this->assertSame(5, RecursoVpn::query()->count());
        $this->assertSame(0, UsuarioFortitoken::query()->count());

        $fortiClient = Software::query()->where('nombre_software', 'FortiClient')->first();
        $this->assertSame(CategoriasSoftware::SoftwareVPN->value, $fortiClient?->categoria);

        $deyvid = User::query()->where('email', 'deyvid@capitalsafi.com')->first();
        $this->assertNotNull($deyvid);
        $this->assertTrue(
            SoftwareUsuario::query()
                ->where('usuario_id', $deyvid->id)
                ->where('id_software', $fortiClient?->id_software)
                ->exists(),
        );

        $acceso = AccesoVirtual::query()->where('usuario_id', $deyvid->id)->first();
        $this->assertSame('VM-Sistemas', $acceso?->virtual?->maquina_virtual);
        $this->assertNull($acceso?->vpn_id);

        $intranet = SitioSharepoint::query()->where('nombre_sitio', 'Intranet')->first();
        $this->assertSame(
            'https://pendiente-sharepoint.local/sites/intranet',
            $intranet?->url,
        );

        $exchange = Licencia::query()->where('nombre_licencia', 'Exchange Online (Plan 1)')->first();
        $web = User::query()->where('email', 'web@capitalsafi.com')->first();
        $this->assertNotNull($web);
        $this->assertFalse($web->acceso_habilitado);
        $this->assertSame('Marketing', $web->area?->area);
        $this->assertTrue(
            LicenciaUsuario::query()
                ->where('usuario_id', $web->id)
                ->where('licencia_id', $exchange?->id)
                ->exists(),
        );
        $this->assertSame(7, $exchange?->total_licencias);

        $wolfram = User::query()->where('email', 'wolfram@capitalsafi.com')->first();
        $tenant = Sistema::query()->where('nombre_sistema', 'Tenant')->first();
        $accesoTenant = AccesoSistema::query()
            ->where('usuario_id', $wolfram?->id)
            ->where('id_sistema', $tenant?->id_sistema)
            ->first();
        $this->assertSame('Exchange Administrador', $accesoTenant?->nivel_acceso);
        $this->assertStringContainsString('Fabric Administrator', (string) $accesoTenant?->observaciones);

        $this->assertTrue(
            RecursoVpn::query()->where('nombre_vpn', 'Capital + Safi (Producción)')->exists(),
        );
    }

    public function test_volver_a_sembrar_no_duplica_registros(): void
    {
        $this->sembrarPersonalYAdmin();
        $this->seed(InventarioAccesosSeeder::class);
        $this->seed(InventarioAccesosSeeder::class);

        $this->assertSame(28, Software::query()->count());
        $this->assertSame(521, SoftwareUsuario::query()->count());
        $this->assertSame(187, LicenciaUsuario::query()->count());
        $this->assertSame(169, AccesoSistema::query()->count());
        $this->assertSame(20, AccesoVirtual::query()->count());
        $this->assertSame(1, User::query()->where('email', 'web@capitalsafi.com')->count());
    }

    private function sembrarPersonalYAdmin(): void
    {
        $this->seed(CatalogoPersonalSeeder::class);

        $adminCargo = Cargo::query()->where('cargo', 'Pasante IT')->first()
            ?? Cargo::query()->first();

        User::query()->firstOrCreate(
            ['email' => 'ti@capitalsafi.com'],
            [
                'name' => 'admin',
                'nombres' => 'Admin',
                'apellidos' => 'ICU',
                'password' => '12345678',
                'id_area' => $adminCargo?->area_id,
                'id_cargo' => $adminCargo?->id_cargo,
                'acceso_habilitado' => true,
            ],
        );
    }
}
