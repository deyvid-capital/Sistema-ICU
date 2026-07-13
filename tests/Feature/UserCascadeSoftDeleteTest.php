<?php

namespace Tests\Feature;

use App\Models\AccesoInternet;
use App\Models\AccesoSistema;
use App\Models\AccesoVirtual;
use App\Models\LicenciaUsuario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCascadeSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_soft_deleting_user_cascades_to_related_assignments_and_deactivates_them(): void
    {
        $user = User::factory()->create();
        $accesoSistema = AccesoSistema::factory()->create([
            'usuario_id' => $user->id,
            'estado' => true,
        ]);
        $licenciaUsuario = LicenciaUsuario::factory()->create([
            'usuario_id' => $user->id,
            'estado' => true,
        ]);

        $user->delete();

        $this->assertSoftDeleted($user);
        $this->assertSoftDeleted($accesoSistema);
        $this->assertSoftDeleted($licenciaUsuario);
        $this->assertFalse($accesoSistema->fresh()->estado);
        $this->assertFalse($licenciaUsuario->fresh()->estado);
    }

    public function test_soft_deleting_user_cascades_to_internet_and_virtual_accesses(): void
    {
        $user = User::factory()->create();
        $accesoInternet = AccesoInternet::factory()->create([
            'usuario_id' => $user->id,
            'estado' => true,
        ]);
        $accesoVirtual = AccesoVirtual::factory()->create([
            'internet_id' => $accesoInternet->id,
        ]);

        $user->delete();

        $this->assertSoftDeleted($accesoInternet);
        $this->assertSoftDeleted($accesoVirtual);
        $this->assertFalse($accesoInternet->fresh()->estado);
    }

    public function test_restoring_user_restores_cascaded_assignments_and_reactivates_them(): void
    {
        $user = User::factory()->create();
        $accesoSistema = AccesoSistema::factory()->create([
            'usuario_id' => $user->id,
            'estado' => true,
        ]);

        $user->delete();
        $user->restore();

        $this->assertFalse($user->trashed());
        $this->assertFalse($accesoSistema->fresh()->trashed());
        $this->assertTrue($accesoSistema->fresh()->estado);
    }

    public function test_usuario_relationship_includes_soft_deleted_user_name(): void
    {
        $user = User::factory()->create(['name' => 'Usuario Eliminado']);
        $accesoSistema = AccesoSistema::factory()->create([
            'usuario_id' => $user->id,
        ]);

        $user->delete();
        $accesoSistema->delete();

        $this->assertSame(
            'Usuario Eliminado',
            AccesoSistema::withTrashed()->find($accesoSistema->id)?->usuario?->name,
        );
    }
}
