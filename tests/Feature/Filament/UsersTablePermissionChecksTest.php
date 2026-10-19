<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UsersTablePermissionChecksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        UserResource::skipAuthorization(false);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        foreach (['ViewAny:User', 'View:User', 'Update:User', 'Delete:User', 'ManageSecurity:User'] as $permiso) {
            Permission::findOrCreate($permiso, 'web');
        }
    }

    private function usuarioConPermisos(array $permisos): User
    {
        $role = Role::findOrCreate('rol-'.md5(implode(',', $permisos)), 'web');
        $role->syncPermissions($permisos);

        return User::factory()->create()->assignRole($role);
    }

    public function test_con_manage_security_ve_las_acciones_de_seguridad(): void
    {
        $actor = $this->usuarioConPermisos(['ViewAny:User', 'View:User', 'Update:User', 'ManageSecurity:User']);
        $objetivo = User::factory()->create();

        $this->actingAs($actor);

        Livewire::test(ListUsers::class)
            ->assertActionVisible(TestAction::make('seguridad')->table($objetivo))
            ->assertActionVisible(TestAction::make('toggleAcceso')->table($objetivo));
    }

    public function test_sin_manage_security_no_ve_las_acciones_de_seguridad(): void
    {
        $actor = $this->usuarioConPermisos(['ViewAny:User', 'View:User', 'Update:User']);
        $objetivo = User::factory()->create();

        $this->actingAs($actor);

        Livewire::test(ListUsers::class)
            ->assertActionHidden(TestAction::make('seguridad')->table($objetivo))
            ->assertActionHidden(TestAction::make('toggleAcceso')->table($objetivo));
    }

    public function test_sin_permiso_de_borrar_no_ve_eliminar(): void
    {
        $actor = $this->usuarioConPermisos(['ViewAny:User', 'View:User', 'Update:User']);
        $objetivo = User::factory()->create();

        $this->actingAs($actor);

        Livewire::test(ListUsers::class)
            ->assertActionHidden(TestAction::make('delete')->table($objetivo));
    }

    public function test_manage_security_se_evalua_una_vez_y_no_por_fila(): void
    {
        $actor = $this->usuarioConPermisos(['ViewAny:User', 'View:User', 'Update:User', 'ManageSecurity:User']);
        User::factory()->count(25)->create();

        $this->actingAs($actor);

        $evaluaciones = 0;
        Gate::after(function ($user, string $ability) use (&$evaluaciones): void {
            if ($ability === 'manageSecurity') {
                $evaluaciones++;
            }
        });

        Livewire::test(ListUsers::class)->assertSuccessful();

        $this->assertLessThanOrEqual(3, $evaluaciones);
    }
}
