<?php

namespace Tests\Unit\Models;

use App\Models\Area;
use App\Models\Software;
use App\Models\SoftwareUsuario;
use App\Models\User;
use Filament\Support\Exceptions\Halt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreventsForceDeleteWithDependentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_force_deleting_an_area_with_users_is_blocked(): void
    {
        $area = Area::factory()->create();
        User::factory()->create(['id_area' => $area->id_area, 'id_cargo' => null]);

        $this->expectException(Halt::class);

        $area->forceDelete();
    }

    public function test_force_deleting_an_area_without_dependents_is_allowed(): void
    {
        $area = Area::factory()->create();

        $area->forceDelete();

        $this->assertDatabaseMissing(Area::class, ['id_area' => $area->id_area]);
    }

    public function test_force_deleting_is_blocked_even_when_the_only_dependent_is_soft_deleted(): void
    {
        $software = Software::factory()->create();
        $softwareUsuario = SoftwareUsuario::factory()->create(['id_software' => $software->id_software]);
        $softwareUsuario->delete();

        $this->expectException(Halt::class);

        $software->forceDelete();
    }

    public function test_force_deleting_a_software_without_dependents_is_allowed(): void
    {
        $software = Software::factory()->create();

        $software->forceDelete();

        $this->assertDatabaseMissing(Software::class, ['id_software' => $software->id_software]);
    }
}
