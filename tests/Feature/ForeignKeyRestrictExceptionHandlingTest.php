<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ForeignKeyRestrictExceptionHandlingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_bulk_query_delete_that_bypasses_model_events_still_gets_a_friendly_message(): void
    {
        $area = Area::factory()->create();
        User::factory()->create(['id_area' => $area->id_area, 'id_cargo' => null]);

        Route::get('/__test/force-delete-bypass', function () use ($area) {
            // A query-builder delete bypasses Eloquent model events entirely,
            // so PreventsForceDeleteWithDependents never runs — only the DB's
            // RESTRICT constraint (and our exception handler) can catch this.
            // Wrapped in a transaction so Postgres rolls back to a savepoint
            // on failure instead of poisoning the whole connection.
            DB::transaction(fn () => Area::whereKey($area->id_area)->delete());

            return 'should not reach here';
        });

        $response = $this->getJson('/__test/force-delete-bypass');

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'No se puede eliminar: todavía existen registros relacionados en otro módulo. Elimínalos o reasígnalos primero.',
        ]);

        $this->assertDatabaseHas(Area::class, ['id_area' => $area->id_area]);
    }
}
