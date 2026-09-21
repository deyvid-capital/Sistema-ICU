<?php

namespace Tests\Feature;

use App\Filament\Resources\Licencias\LicenciaResource;
use App\Filament\Resources\Licencias\Pages\EditLicencia;
use App\Filament\Resources\Licencias\RelationManagers\LicenciasUsuariosRelationManager as LicenciaLicenciasUsuariosRelationManager;
use App\Filament\Resources\LicenciasUsuarios\LicenciaUsuarioResource;
use App\Filament\Resources\LicenciasUsuarios\Pages\CreateLicenciaUsuario;
use App\Filament\Resources\Users\Pages\ManageUserSoftware;
use App\Filament\Resources\Users\RelationManagers\LicenciasUsuariosRelationManager as UserLicenciasUsuariosRelationManager;
use App\Filament\Resources\Users\UserResource;
use App\Models\Licencia;
use App\Models\LicenciaUsuario;
use App\Models\User;
use App\Notifications\CriticalEventNotification;
use App\Services\Movimientos\ProcesarBajaColaborador;
use Filament\Support\Exceptions\Halt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class LicenciaCupoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        LicenciaResource::skipAuthorization();
        LicenciaUsuarioResource::skipAuthorization();
        UserResource::skipAuthorization();
        Gate::before(fn () => true);

        $this->actingAs(User::factory()->create());
    }

    public function test_bloquea_asignar_mas_alla_del_total_desde_el_modelo(): void
    {
        $licencia = Licencia::factory()->create(['total_licencias' => 1]);
        $this->asignar($licencia);

        $this->expectException(Halt::class);

        $this->asignar($licencia);
    }

    public function test_permite_una_asignacion_inactiva_aunque_no_haya_cupo(): void
    {
        $licencia = Licencia::factory()->create(['total_licencias' => 0]);

        $this->asignar($licencia, estado: false);

        $this->assertSame(0, $licencia->fresh()->asignadas);
        $this->assertSame(0, $licencia->fresh()->disponibles);
    }

    public function test_bloquea_asignar_desde_el_formulario_standalone_cuando_no_hay_cupo(): void
    {
        $licencia = Licencia::factory()->create(['total_licencias' => 1]);
        $this->asignar($licencia);
        $usuario = User::factory()->create();

        Livewire::test(CreateLicenciaUsuario::class)
            ->fillForm([
                'usuario_id' => $usuario->id,
                'licencia_id' => $licencia->id,
                'estado' => true,
            ])
            ->call('create')
            ->assertHasFormErrors(['licencia_id']);

        $this->assertSame(1, LicenciaUsuario::query()->where('licencia_id', $licencia->id)->count());
    }

    public function test_bloquea_asignar_desde_el_relation_manager_del_usuario(): void
    {
        $usuario = User::factory()->create();
        $licencia = Licencia::factory()->create(['total_licencias' => 1]);
        $this->asignar($licencia);

        Livewire::test(UserLicenciasUsuariosRelationManager::class, [
            'ownerRecord' => $usuario,
            'pageClass' => ManageUserSoftware::class,
        ])
            ->callTableAction('create', data: [
                'licencia_id' => $licencia->id,
                'estado' => true,
            ])
            ->assertHasTableActionErrors(['licencia_id']);

        $this->assertDatabaseMissing('licencias_usuarios', [
            'usuario_id' => $usuario->id,
            'licencia_id' => $licencia->id,
        ]);
    }

    public function test_deshabilita_asignar_desde_el_relation_manager_de_la_licencia_sin_cupo(): void
    {
        $licencia = Licencia::factory()->create(['total_licencias' => 1]);
        $this->asignar($licencia);

        Livewire::test(LicenciaLicenciasUsuariosRelationManager::class, [
            'ownerRecord' => $licencia->fresh(),
            'pageClass' => EditLicencia::class,
        ])
            ->assertTableActionDisabled('create');
    }

    public function test_permite_asignar_desde_el_relation_manager_del_usuario_cuando_hay_cupo(): void
    {
        $usuario = User::factory()->create();
        $licencia = Licencia::factory()->create(['total_licencias' => 2]);

        Livewire::test(UserLicenciasUsuariosRelationManager::class, [
            'ownerRecord' => $usuario,
            'pageClass' => ManageUserSoftware::class,
        ])
            ->callTableAction('create', data: [
                'licencia_id' => $licencia->id,
                'estado' => true,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('licencias_usuarios', [
            'usuario_id' => $usuario->id,
            'licencia_id' => $licencia->id,
        ]);
    }

    public function test_bloquea_reducir_el_total_por_debajo_de_las_asignadas(): void
    {
        $licencia = Licencia::factory()->create(['total_licencias' => 2]);
        $this->asignar($licencia);
        $this->asignar($licencia);

        Livewire::test(EditLicencia::class, ['record' => $licencia->id])
            ->fillForm(['total_licencias' => 1])
            ->call('save')
            ->assertHasFormErrors(['total_licencias']);

        $this->assertSame(2, $licencia->fresh()->total_licencias);
    }

    public function test_permite_reducir_el_total_despues_de_desasignar(): void
    {
        $licencia = Licencia::factory()->create(['total_licencias' => 2]);
        $activa = $this->asignar($licencia);
        $aLiberar = $this->asignar($licencia);

        try {
            $licencia->update(['total_licencias' => 1]);
            $this->fail('Debió bloquearse la reducción del total.');
        } catch (Halt) {
            // Expected.
        }

        $this->assertSame(2, $licencia->fresh()->total_licencias);

        $aLiberar->update(['estado' => false]);
        $licencia->refresh()->update(['total_licencias' => 1]);

        $this->assertSame(1, $licencia->fresh()->total_licencias);
        $this->assertTrue($activa->fresh()->estado);
    }

    public function test_bloquea_reactivar_una_asignacion_sin_cupo(): void
    {
        $licencia = Licencia::factory()->create(['total_licencias' => 1]);
        $inactiva = $this->asignar($licencia, estado: false);
        $this->asignar($licencia);

        $this->expectException(Halt::class);

        $inactiva->update(['estado' => true]);
    }

    public function test_bloquea_restaurar_una_asignacion_sin_cupo(): void
    {
        $licencia = Licencia::factory()->create(['total_licencias' => 1]);
        $eliminada = $this->asignar($licencia);
        $eliminada->delete();

        $this->asignar($licencia);

        $this->expectException(Halt::class);

        $eliminada->restore();
    }

    public function test_el_ultimo_asiento_notifica_a_los_administradores(): void
    {
        Notification::fake();

        $superAdmin = User::factory()->conRol('super_admin')->create();
        $admin = User::factory()->conRol('admin')->create();
        $actor = User::factory()->create();
        $this->actingAs($actor);

        $licencia = Licencia::factory()->create([
            'nombre_licencia' => 'Office 365 E3',
            'total_licencias' => 1,
        ]);

        $this->asignar($licencia, usuario: $actor);

        Notification::assertSentTo($superAdmin, CriticalEventNotification::class, function (CriticalEventNotification $notification): bool {
            return str_contains($notification->title, 'Cupo agotado: Office 365 E3');
        });
        Notification::assertSentTo($admin, CriticalEventNotification::class);
        Notification::assertNotSentTo($actor, CriticalEventNotification::class);
    }

    public function test_la_baja_del_colaborador_libera_cupo(): void
    {
        $usuario = User::factory()->create();
        $licencia = Licencia::factory()->create(['total_licencias' => 1]);
        $asignacion = $this->asignar($licencia, usuario: $usuario);

        $this->assertSame(0, $licencia->fresh()->disponibles);

        app(ProcesarBajaColaborador::class)->ejecutar($usuario->id, now());

        $this->assertFalse($asignacion->fresh()->estado);
        $this->assertSame(1, $licencia->fresh()->disponibles);
    }

    private function asignar(Licencia $licencia, bool $estado = true, ?User $usuario = null): LicenciaUsuario
    {
        return LicenciaUsuario::factory()->create([
            'usuario_id' => $usuario?->id ?? User::factory(),
            'licencia_id' => $licencia->id,
            'estado' => $estado,
        ]);
    }
}
