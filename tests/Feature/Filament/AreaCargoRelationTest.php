<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Areas\AreaResource;
use App\Filament\Resources\Cargos\CargoResource;
use App\Models\Area;
use App\Models\Cargo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AreaCargoRelationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // User doesn't implement FilamentUser, so Filament's panel Authenticate
        // middleware only allows access when app.env is 'local' (see
        // vendor/filament/filament/src/Http/Middleware/Authenticate.php). Tests
        // run under 'testing', and no Policies exist yet either (tracked
        // separately) — bypass both only for this test to exercise real pages.
        config(['app.env' => 'local']);
        Gate::before(fn () => true);

        $this->actingAs(User::factory()->create());
    }

    public function test_area_has_many_cargos(): void
    {
        $area = Area::factory()->create();
        Cargo::factory()->count(3)->create(['area_id' => $area->id_area]);

        $this->assertSame(3, $area->cargos()->count());
    }

    public function test_area_view_page_loads_with_cargos_relation_manager(): void
    {
        $area = Area::factory()->create();
        Cargo::factory()->create(['area_id' => $area->id_area]);

        $response = $this->get(AreaResource::getUrl('view', ['record' => $area]));

        $response->assertSuccessful();
    }

    public function test_cargo_index_page_loads(): void
    {
        $response = $this->get(CargoResource::getUrl('index'));

        $response->assertSuccessful();
    }
}
