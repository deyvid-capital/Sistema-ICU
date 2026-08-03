<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\AccesoSharepoints\AccesoSharepointResource;
use App\Filament\Resources\AccesoSharepoints\Pages\CreateAccesoSharepoint;
use App\Filament\Resources\AccesoVirtuals\AccesoVirtualResource;
use App\Filament\Resources\AccesoVirtuals\Pages\CreateAccesoVirtual;
use App\Filament\Resources\LicenciasUsuarios\LicenciaUsuarioResource;
use App\Filament\Resources\LicenciasUsuarios\Pages\CreateLicenciaUsuario;
use App\Filament\Resources\SoftwareUsuarios\Pages\CreateSoftwareUsuario;
use App\Filament\Resources\SoftwareUsuarios\SoftwareUsuarioResource;
use App\Models\AccesoInternet;
use App\Models\AccesoSharepoint;
use App\Models\AccesoVirtual;
use App\Models\Licencia;
use App\Models\LicenciaUsuario;
use App\Models\RecursoVpn;
use App\Models\SitioSharepoint;
use App\Models\Software;
use App\Models\SoftwareUsuario;
use App\Models\User;
use App\Models\Virtual;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InactiveCatalogAssignmentWarningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        SoftwareUsuarioResource::skipAuthorization();
        LicenciaUsuarioResource::skipAuthorization();
        AccesoSharepointResource::skipAuthorization();
        AccesoVirtualResource::skipAuthorization();

        $this->actingAs(User::factory()->create());
    }

    public function test_it_warns_but_still_assigns_an_inactive_software(): void
    {
        $usuario = User::factory()->create();
        $software = Software::factory()->create([
            'nombre_software' => 'Adobe Acrobat',
            'estado' => false,
        ]);

        Livewire::test(CreateSoftwareUsuario::class)
            ->fillForm([
                'usuario_id' => $usuario->id,
                'id_software' => $software->id_software,
                'estado' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified('Recurso en estado no activo');

        $this->assertDatabaseHas(SoftwareUsuario::class, [
            'usuario_id' => $usuario->id,
            'id_software' => $software->id_software,
        ]);
    }

    public function test_it_does_not_warn_when_the_software_is_active(): void
    {
        $usuario = User::factory()->create();
        $software = Software::factory()->create(['estado' => true]);

        Livewire::test(CreateSoftwareUsuario::class)
            ->fillForm([
                'usuario_id' => $usuario->id,
                'id_software' => $software->id_software,
                'estado' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotNotified('Recurso en estado no activo');
    }

    public function test_it_warns_but_still_assigns_an_inactive_licencia(): void
    {
        $usuario = User::factory()->create();
        $licencia = Licencia::factory()->create(['estado' => false]);

        Livewire::test(CreateLicenciaUsuario::class)
            ->fillForm([
                'usuario_id' => $usuario->id,
                'licencia_id' => $licencia->id,
                'estado' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified('Recurso en estado no activo');

        $this->assertDatabaseHas(LicenciaUsuario::class, [
            'usuario_id' => $usuario->id,
            'licencia_id' => $licencia->id,
        ]);
    }

    public function test_it_warns_but_still_assigns_an_inactive_sitio_sharepoint(): void
    {
        $usuario = User::factory()->create();
        $sitio = SitioSharepoint::factory()->create(['estado' => false]);

        Livewire::test(CreateAccesoSharepoint::class)
            ->fillForm([
                'usuario_id' => $usuario->id,
                'sitio_id' => $sitio->id,
                'estado' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified('Recurso en estado no activo');

        $this->assertDatabaseHas(AccesoSharepoint::class, [
            'usuario_id' => $usuario->id,
            'sitio_id' => $sitio->id,
        ]);
    }

    public function test_it_groups_both_inactive_resources_of_an_acceso_virtual_in_one_warning(): void
    {
        $accesoInternet = AccesoInternet::factory()->create();
        $virtual = Virtual::factory()->create([
            'maquina_virtual' => 'VM-CONTA-01',
            'estado' => false,
        ]);
        $vpn = RecursoVpn::factory()->create([
            'nombre_vpn' => 'VPN Corporativa',
            'estado' => false,
        ]);

        Livewire::test(CreateAccesoVirtual::class)
            ->fillForm([
                'internet_id' => $accesoInternet->id,
                'virtual_id' => $virtual->id,
                'vpn_id' => $vpn->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified('Recurso en estado no activo');

        $this->assertDatabaseHas(AccesoVirtual::class, [
            'internet_id' => $accesoInternet->id,
            'virtual_id' => $virtual->id,
            'vpn_id' => $vpn->id,
        ]);

        $acceso = AccesoVirtual::query()->latest('id')->first();

        $this->assertSame(
            'La asignación se guardó, pero la máquina virtual «VM-CONTA-01» y el recurso VPN «VPN Corporativa» están en estado no activo.',
            $this->invokeWarningBody($acceso),
        );
    }

    public function test_it_warns_only_about_the_inactive_resource_of_an_acceso_virtual(): void
    {
        $accesoInternet = AccesoInternet::factory()->create();
        $virtual = Virtual::factory()->create(['estado' => true]);
        $vpn = RecursoVpn::factory()->create([
            'nombre_vpn' => 'VPN Contingencia',
            'estado' => false,
        ]);

        Livewire::test(CreateAccesoVirtual::class)
            ->fillForm([
                'internet_id' => $accesoInternet->id,
                'virtual_id' => $virtual->id,
                'vpn_id' => $vpn->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified('Recurso en estado no activo');

        $acceso = AccesoVirtual::query()->latest('id')->first();

        $this->assertSame(
            'La asignación se guardó, pero el recurso VPN «VPN Contingencia» está en estado no activo.',
            $this->invokeWarningBody($acceso),
        );
    }

    private function invokeWarningBody(AccesoVirtual $acceso): string
    {
        $inactivos = [];

        foreach (['virtual' => ['la máquina virtual', 'maquina_virtual'], 'recursoVpn' => ['el recurso VPN', 'nombre_vpn']] as $relation => [$label, $attribute]) {
            $related = $acceso->{$relation};

            if ($related && ! $related->estado) {
                $inactivos[] = "{$label} «{$related->{$attribute}}»";
            }
        }

        $method = new \ReflectionMethod($acceso, 'buildInactiveCatalogWarningBody');

        return $method->invoke($acceso, $inactivos);
    }
}
