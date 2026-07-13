<?php

namespace Tests\Unit\Models;

use App\Models\AccesoSistema;
use App\Models\User;
use Filament\Support\Exceptions\Halt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuardsAgainstTrashedRelationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_an_assignment_for_a_trashed_user_is_blocked(): void
    {
        $user = User::factory()->create();
        $user->delete();

        $this->expectException(Halt::class);

        AccesoSistema::factory()->create(['usuario_id' => $user->id]);
    }

    public function test_creating_an_assignment_for_a_trashed_user_does_not_persist_the_record(): void
    {
        $user = User::factory()->create();
        $user->delete();

        try {
            AccesoSistema::factory()->create(['usuario_id' => $user->id]);
        } catch (Halt) {
            // Expected: Filament Halt stops the create after showing a notification.
        }

        $this->assertDatabaseMissing('accesos_sistemas', [
            'usuario_id' => $user->id,
        ]);
    }

    public function test_creating_an_assignment_for_an_active_user_is_allowed(): void
    {
        $user = User::factory()->create();

        $accesoSistema = AccesoSistema::factory()->create(['usuario_id' => $user->id]);

        $this->assertDatabaseHas(AccesoSistema::class, ['id' => $accesoSistema->id]);
    }
}
