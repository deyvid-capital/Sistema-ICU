<?php

namespace Tests\Feature\Models;

use App\Models\AccesoVirtual;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccesoVirtualEstadoTest extends TestCase
{
    use RefreshDatabase;

    public function test_soft_deleting_sets_estado_to_false(): void
    {
        $accesoVirtual = AccesoVirtual::factory()->create(['estado' => true]);

        $accesoVirtual->delete();

        $this->assertTrue($accesoVirtual->trashed());
        $this->assertFalse($accesoVirtual->fresh()->estado);
    }

    public function test_restoring_sets_estado_back_to_true(): void
    {
        $accesoVirtual = AccesoVirtual::factory()->create(['estado' => true]);
        $accesoVirtual->delete();

        $accesoVirtual->restore();

        $this->assertFalse($accesoVirtual->trashed());
        $this->assertTrue($accesoVirtual->fresh()->estado);
    }
}
