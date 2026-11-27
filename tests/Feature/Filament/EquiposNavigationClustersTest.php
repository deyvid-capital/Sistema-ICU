<?php

namespace Tests\Feature\Filament;

use App\Filament\Clusters\Asignacion\AsignacionCluster;
use App\Filament\Clusters\Equipos\EquiposCluster;
use App\Filament\Resources\AsignacionCargadors\AsignacionCargadorResource;
use App\Filament\Resources\AsignacionEquipos\AsignacionEquipoResource;
use App\Filament\Resources\AsignacionEquipos\Pages\ListAsignacionEquipos;
use App\Filament\Resources\Cargadors\CargadorResource;
use App\Filament\Resources\EntregaEquipos\EntregaEquipoResource;
use App\Filament\Resources\Equipos\EquipoResource;
use App\Filament\Resources\Equipos\Pages\ListEquipos;
use App\Filament\Resources\Firmantes\FirmanteResource;
use App\Filament\Resources\MantenimientoEquipos\MantenimientoEquipoResource;
use App\Filament\Resources\NotaRecepcionEquipos\NotaRecepcionEquipoResource;
use App\Filament\Resources\NotaSalidaEquipos\NotaSalidaEquipoResource;
use App\Filament\Resources\PdfCelulars\PdfCelularResource;
use App\Filament\Resources\PdfEquipos\PdfEquipoResource;
use App\Filament\Resources\PersonalExternoSoporteTecnicos\PersonalExternoSoporteTecnicoResource;
use App\Filament\Resources\RecepcionEquipos\RecepcionEquipoResource;
use App\Filament\Resources\ReciboEquipos\ReciboEquipoResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class EquiposNavigationClustersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(fn () => true);

        $this->actingAs(User::factory()->create());
    }

    public function test_clusters_del_grupo_equipos_tienen_subnavegacion_acotada(): void
    {
        $this->assertSame('Equipos', EquiposCluster::getNavigationGroup());
        $this->assertSame('Equipos', AsignacionCluster::getNavigationGroup());

        $this->assertSame(EquiposCluster::class, EquipoResource::getCluster());
        $this->assertSame(EquiposCluster::class, CargadorResource::getCluster());
        $this->assertSame(EquiposCluster::class, ReciboEquipoResource::getCluster());
        $this->assertSame(EquiposCluster::class, NotaSalidaEquipoResource::getCluster());
        $this->assertSame(EquiposCluster::class, NotaRecepcionEquipoResource::getCluster());
        $this->assertSame(EquiposCluster::class, PersonalExternoSoporteTecnicoResource::getCluster());

        $this->assertSame(AsignacionCluster::class, AsignacionEquipoResource::getCluster());
        $this->assertSame(AsignacionCluster::class, AsignacionCargadorResource::getCluster());
        $this->assertSame(AsignacionCluster::class, EntregaEquipoResource::getCluster());
        $this->assertSame(AsignacionCluster::class, RecepcionEquipoResource::getCluster());
        $this->assertSame(AsignacionCluster::class, MantenimientoEquipoResource::getCluster());
    }

    public function test_urls_usan_el_slug_del_cluster_correspondiente(): void
    {
        $this->assertStringContainsString('/equipos/', EquipoResource::getUrl('index'));
        $this->assertStringContainsString('asignacion-formularios', AsignacionEquipoResource::getUrl('index'));
    }

    public function test_listados_de_cada_cluster_de_equipos_cargan(): void
    {
        Livewire::test(ListEquipos::class)->assertOk();
        Livewire::test(ListAsignacionEquipos::class)->assertOk();
    }

    public function test_resources_ocultos_no_registran_navegacion(): void
    {
        $this->assertFalse(PdfEquipoResource::shouldRegisterNavigation());
        $this->assertFalse(PdfCelularResource::shouldRegisterNavigation());
        $this->assertFalse(FirmanteResource::shouldRegisterNavigation());
    }
}
