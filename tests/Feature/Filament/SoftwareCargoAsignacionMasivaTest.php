<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\SoftwareAreas\Pages\CreateSoftwareArea;
use App\Filament\Resources\SoftwareAreas\Pages\EditSoftwareArea;
use App\Filament\Resources\SoftwareAreas\SoftwareAreaResource;
use App\Models\Area;
use App\Models\Software;
use App\Models\SoftwareArea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SoftwareAreaAsignacionMasivaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        SoftwareAreaResource::skipAuthorization();

        $this->actingAs(User::factory()->conRol()->create());
    }

    public function test_asigna_varios_software_a_un_area_en_un_solo_envio(): void
    {
        $area = Area::factory()->create();
        $software = Software::factory()->count(3)->create();

        Livewire::test(CreateSoftwareArea::class)
            ->fillForm([
                'area_id' => $area->id_area,
                'software_ids' => $software->pluck('id_software')->all(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(3, SoftwareArea::query()->where('area_id', $area->id_area)->count());

        foreach ($software as $item) {
            $this->assertDatabaseHas(SoftwareArea::class, [
                'area_id' => $area->id_area,
                'software_id' => $item->id_software,
            ]);
        }
    }

    public function test_rechaza_software_que_ya_pertenece_al_area(): void
    {
        $area = Area::factory()->create();
        $asignado = Software::factory()->create();
        $nuevo = Software::factory()->create();

        SoftwareArea::factory()->create([
            'area_id' => $area->id_area,
            'software_id' => $asignado->id_software,
        ]);

        Livewire::test(CreateSoftwareArea::class)
            ->fillForm([
                'area_id' => $area->id_area,
                'software_ids' => [$asignado->id_software, $nuevo->id_software],
            ])
            ->call('create')
            ->assertHasFormErrors(['software_ids']);

        $this->assertDatabaseMissing(SoftwareArea::class, [
            'area_id' => $area->id_area,
            'software_id' => $nuevo->id_software,
        ]);
    }

    public function test_el_mismo_software_puede_asignarse_a_otra_area(): void
    {
        $area = Area::factory()->create();
        $otraArea = Area::factory()->create();
        $software = Software::factory()->create();

        SoftwareArea::factory()->create([
            'area_id' => $area->id_area,
            'software_id' => $software->id_software,
        ]);

        Livewire::test(CreateSoftwareArea::class)
            ->fillForm([
                'area_id' => $otraArea->id_area,
                'software_ids' => [$software->id_software],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(SoftwareArea::class, [
            'area_id' => $otraArea->id_area,
            'software_id' => $software->id_software,
        ]);
    }

    public function test_editar_sigue_guardando_una_sola_relacion(): void
    {
        $area = Area::factory()->create();
        $otraArea = Area::factory()->create();
        $software = Software::factory()->create();
        $otroSoftware = Software::factory()->create();
        $relacion = SoftwareArea::factory()->create([
            'area_id' => $area->id_area,
            'software_id' => $software->id_software,
        ]);

        Livewire::test(EditSoftwareArea::class, ['record' => $relacion->getKey()])
            ->fillForm([
                'area_id' => $otraArea->id_area,
                'software_id' => $otroSoftware->id_software,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(SoftwareArea::class, [
            'id' => $relacion->getKey(),
            'area_id' => $otraArea->id_area,
            'software_id' => $otroSoftware->id_software,
        ]);
        $this->assertSame(1, SoftwareArea::query()->count());
    }
}
