<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\SoftwareCargos\Pages\CreateSoftwareCargo;
use App\Filament\Resources\SoftwareCargos\Pages\EditSoftwareCargo;
use App\Filament\Resources\SoftwareCargos\SoftwareCargoResource;
use App\Models\Cargo;
use App\Models\Software;
use App\Models\SoftwareCargo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SoftwareCargoAsignacionMasivaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        SoftwareCargoResource::skipAuthorization();

        $this->actingAs(User::factory()->conRol()->create());
    }

    public function test_asigna_varios_software_a_un_cargo_en_un_solo_envio(): void
    {
        $cargo = Cargo::factory()->create();
        $software = Software::factory()->count(3)->create();

        Livewire::test(CreateSoftwareCargo::class)
            ->fillForm([
                'cargo_id' => $cargo->id_cargo,
                'software_ids' => $software->pluck('id_software')->all(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(3, SoftwareCargo::query()->where('cargo_id', $cargo->id_cargo)->count());

        foreach ($software as $item) {
            $this->assertDatabaseHas(SoftwareCargo::class, [
                'cargo_id' => $cargo->id_cargo,
                'software_id' => $item->id_software,
            ]);
        }
    }

    public function test_rechaza_software_que_ya_pertenece_al_cargo(): void
    {
        $cargo = Cargo::factory()->create();
        $asignado = Software::factory()->create();
        $nuevo = Software::factory()->create();

        SoftwareCargo::factory()->create([
            'cargo_id' => $cargo->id_cargo,
            'software_id' => $asignado->id_software,
        ]);

        Livewire::test(CreateSoftwareCargo::class)
            ->fillForm([
                'cargo_id' => $cargo->id_cargo,
                'software_ids' => [$asignado->id_software, $nuevo->id_software],
            ])
            ->call('create')
            ->assertHasFormErrors(['software_ids']);

        $this->assertDatabaseMissing(SoftwareCargo::class, [
            'cargo_id' => $cargo->id_cargo,
            'software_id' => $nuevo->id_software,
        ]);
    }

    public function test_el_mismo_software_puede_asignarse_a_otro_cargo(): void
    {
        $cargo = Cargo::factory()->create();
        $otroCargo = Cargo::factory()->create();
        $software = Software::factory()->create();

        SoftwareCargo::factory()->create([
            'cargo_id' => $cargo->id_cargo,
            'software_id' => $software->id_software,
        ]);

        Livewire::test(CreateSoftwareCargo::class)
            ->fillForm([
                'cargo_id' => $otroCargo->id_cargo,
                'software_ids' => [$software->id_software],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(SoftwareCargo::class, [
            'cargo_id' => $otroCargo->id_cargo,
            'software_id' => $software->id_software,
        ]);
    }

    public function test_editar_sigue_guardando_una_sola_relacion(): void
    {
        $cargo = Cargo::factory()->create();
        $otroCargo = Cargo::factory()->create();
        $software = Software::factory()->create();
        $otroSoftware = Software::factory()->create();
        $relacion = SoftwareCargo::factory()->create([
            'cargo_id' => $cargo->id_cargo,
            'software_id' => $software->id_software,
        ]);

        Livewire::test(EditSoftwareCargo::class, ['record' => $relacion->getKey()])
            ->fillForm([
                'cargo_id' => $otroCargo->id_cargo,
                'software_id' => $otroSoftware->id_software,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(SoftwareCargo::class, [
            'id' => $relacion->getKey(),
            'cargo_id' => $otroCargo->id_cargo,
            'software_id' => $otroSoftware->id_software,
        ]);
        $this->assertSame(1, SoftwareCargo::query()->count());
    }
}
