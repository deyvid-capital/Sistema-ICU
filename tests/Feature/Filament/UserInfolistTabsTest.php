<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Users\Pages\ViewUser;
use App\Models\AccesoDaza;
use App\Models\AccesoInternet;
use App\Models\AccesoSharepoint;
use App\Models\AccesoSistema;
use App\Models\AccesoVirtual;
use App\Models\AccUdCompartida;
use App\Models\AsigCelular;
use App\Models\AsignacionEquipo;
use App\Models\AzureAd;
use App\Models\Celular;
use App\Models\Equipo;
use App\Models\LicenciaUsuario;
use App\Models\RecursoVpn;
use App\Models\SoftwareExc;
use App\Models\SoftwareUsuario;
use App\Models\User;
use App\Models\Virtual;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class UserInfolistTabsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(fn () => true);

        $this->actingAs(User::factory()->create());
    }

    public function test_view_page_loads_for_user_without_resources(): void
    {
        $user = User::factory()->create();

        Livewire::test(ViewUser::class, ['record' => $user->id])
            ->assertSuccessful()
            ->assertSee('No tiene')
            ->assertSee('El usuario no tiene accesos DAZA registrados.')
            ->assertSee('El usuario no tiene licencias asignadas.');
    }

    public function test_view_page_shows_every_related_resource(): void
    {
        $user = User::factory()->create();

        AccesoInternet::factory()->create([
            'usuario_id' => $user->id,
            'estado' => true,
        ]);

        AccesoVirtual::factory()->create([
            'usuario_id' => $user->id,
            'vpn_id' => RecursoVpn::factory()->create(['nombre_vpn' => 'VPN-CONTABILIDAD'])->id,
            'virtual_id' => Virtual::factory()->create(['maquina_virtual' => 'VM-FINANZAS'])->id,
        ]);

        AccesoDaza::factory()->create(['usuario_id' => $user->id, 'estado' => true]);
        AccesoSistema::factory()->create(['usuario_id' => $user->id, 'estado' => true]);
        AccesoSharepoint::factory()->create(['usuario_id' => $user->id, 'estado' => true]);
        AccUdCompartida::factory()->create(['usuario_id' => $user->id, 'estado' => true]);
        AzureAd::factory()->create(['usuario_id' => $user->id, 'estado' => true]);
        SoftwareUsuario::factory()->create(['usuario_id' => $user->id, 'estado' => true]);
        SoftwareExc::factory()->create(['usuario_id' => $user->id]);
        LicenciaUsuario::factory()->create(['usuario_id' => $user->id, 'estado' => true]);

        AsignacionEquipo::factory()->create([
            'usuario_id' => $user->id,
            'service_tag' => Equipo::factory()->create()->service_tag,
            'fecha_fin' => null,
        ]);

        AsigCelular::factory()->create([
            'usuario_id' => $user->id,
            'celular_id' => Celular::factory()->create()->id,
            'fecha_fin' => null,
        ]);

        Livewire::test(ViewUser::class, ['record' => $user->id])
            ->assertSuccessful()
            ->assertSee('VPN-CONTABILIDAD')
            ->assertSee('VM-FINANZAS')
            ->assertSee('En uso')
            ->assertDontSee('El usuario no tiene accesos DAZA registrados.')
            ->assertDontSee('El usuario no tiene licencias asignadas.');
    }
}
