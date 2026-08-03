<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\SitioSharepoints\Pages\EditSitioSharepoint;
use App\Filament\Resources\SitioSharepoints\SitioSharepointResource;
use App\Models\AccesoSharepoint;
use App\Models\SitioSharepoint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogoInactivoDesactivaAsignacionesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        SitioSharepointResource::skipAuthorization();

        $this->actingAs(User::factory()->create());
    }

    public function test_desactivar_un_sitio_desactiva_sus_accesos_activos(): void
    {
        $sitio = SitioSharepoint::factory()->create(['estado' => true]);

        $accesoActivo = AccesoSharepoint::factory()->create([
            'usuario_id' => User::factory()->create()->id,
            'sitio_id' => $sitio->id,
            'estado' => true,
        ]);

        Livewire::test(EditSitioSharepoint::class, ['record' => $sitio->id])
            ->fillForm(['estado' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse($accesoActivo->refresh()->estado);
    }

    public function test_mantener_el_sitio_activo_no_toca_los_accesos(): void
    {
        $sitio = SitioSharepoint::factory()->create(['estado' => true]);

        $acceso = AccesoSharepoint::factory()->create([
            'usuario_id' => User::factory()->create()->id,
            'sitio_id' => $sitio->id,
            'estado' => true,
        ]);

        Livewire::test(EditSitioSharepoint::class, ['record' => $sitio->id])
            ->fillForm(['nombre_sitio' => 'Sitio renombrado'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($acceso->refresh()->estado);
    }

    public function test_desactivar_un_sitio_no_afecta_accesos_de_otros_sitios(): void
    {
        $sitio = SitioSharepoint::factory()->create(['estado' => true]);
        $otroSitio = SitioSharepoint::factory()->create(['estado' => true]);

        $accesoAjeno = AccesoSharepoint::factory()->create([
            'usuario_id' => User::factory()->create()->id,
            'sitio_id' => $otroSitio->id,
            'estado' => true,
        ]);

        Livewire::test(EditSitioSharepoint::class, ['record' => $sitio->id])
            ->fillForm(['estado' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($accesoAjeno->refresh()->estado);
    }
}
