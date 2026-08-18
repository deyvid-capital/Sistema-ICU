<?php

namespace Tests\Feature\Models;

use App\Models\AccesoSharepoint;
use App\Models\AzureAd;
use App\Models\SoftwareUsuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TogglesEstadoOnSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_soft_deleting_sets_estado_to_false(): void
    {
        $softwareUsuario = SoftwareUsuario::factory()->create(['estado' => true]);

        $softwareUsuario->delete();

        $this->assertTrue($softwareUsuario->trashed());
        $this->assertFalse($softwareUsuario->fresh()->estado);
    }

    public function test_restoring_sets_estado_back_to_true(): void
    {
        $accesoSharepoint = AccesoSharepoint::factory()->create(['estado' => true]);
        $accesoSharepoint->delete();

        $accesoSharepoint->restore();

        $this->assertFalse($accesoSharepoint->trashed());
        $this->assertTrue($accesoSharepoint->fresh()->estado);
    }

    public function test_force_deleting_does_not_touch_estado(): void
    {
        $azureAd = AzureAd::factory()->create(['estado' => true]);

        $azureAd->forceDelete();

        $this->assertDatabaseMissing('cuentas_azure_ad', ['id' => $azureAd->id]);
    }
}
