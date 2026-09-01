<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use BezhanSalleh\FilamentShield\Resources\Roles\Pages\EditRole;
use Database\Seeders\RolesPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SeguridadPageTest extends TestCase
{
    use RefreshDatabase;

    private function generarPermisos(): void
    {
        Artisan::call('shield:generate', [
            '--all' => true,
            '--panel' => 'admin',
            '--option' => 'permissions',
            '--ignore-existing-policies' => true,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function crearSuperAdmin(): User
    {
        $this->generarPermisos();

        $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $role->syncPermissions(Permission::query()->get());

        $admin = User::factory()->create();
        $admin->assignRole($role);

        return $admin;
    }

    public function test_super_admin_ve_la_pagina_de_seguridad_con_pestanas(): void
    {
        $admin = $this->crearSuperAdmin();
        $objetivo = User::factory()->create();

        $this->actingAs($admin)
            ->get(UserResource::getUrl('seguridad', ['record' => $objetivo], panel: 'admin'))
            ->assertSuccessful()
            ->assertSee('Acceso')
            ->assertSee('Roles')
            ->assertSee('Sesiones')
            ->assertSee('Notificaciones');
    }

    public function test_usuario_sin_permiso_no_accede_a_la_pagina_de_seguridad(): void
    {
        $role = Role::firstOrCreate(['name' => 'operador', 'guard_name' => 'web']);
        $sinPermiso = User::factory()->create();
        $sinPermiso->assignRole($role);
        $objetivo = User::factory()->create();

        $this->actingAs($sinPermiso)
            ->get(UserResource::getUrl('seguridad', ['record' => $objetivo], panel: 'admin'))
            ->assertForbidden();
    }

    public function test_manage_security_es_permiso_del_recurso_usuario(): void
    {
        $this->generarPermisos();

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->assertContains(
            'ManageSecurity:User',
            FilamentShield::getResourcePermissions(UserResource::class) ?? [],
        );
        $this->assertDatabaseHas('permissions', [
            'name' => 'ManageSecurity:User',
            'guard_name' => 'web',
        ]);
    }

    public function test_seeder_asigna_manage_security_al_super_admin(): void
    {
        $admin = User::factory()->create(['email' => 'admin@admin.com']);

        $this->seed(RolesPermissionsSeeder::class);

        $admin->refresh();
        $admin->unsetRelation('roles');
        $admin->unsetRelation('permissions');

        $this->assertTrue($admin->hasRole('super_admin'));
        $this->assertTrue($admin->hasPermissionTo('ManageSecurity:User'));
        $this->assertTrue($admin->can('manageSecurity', User::class));
    }

    public function test_marcar_permisos_del_recurso_usuario_habilita_seguridad(): void
    {
        $this->generarPermisos();

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $role = Role::firstOrCreate(['name' => 'admin_ti', 'guard_name' => 'web']);
        $role->syncPermissions(FilamentShield::getResourcePermissions(UserResource::class) ?? []);

        $usuario = User::factory()->create();
        $usuario->assignRole($role);
        $objetivo = User::factory()->create();

        $this->assertTrue($usuario->can('manageSecurity', User::class));

        $this->actingAs($usuario)
            ->get(UserResource::getUrl('seguridad', ['record' => $objetivo], panel: 'admin'))
            ->assertSuccessful();
    }

    public function test_guardar_rol_en_shield_conserva_manage_security(): void
    {
        $admin = $this->crearSuperAdmin();
        $role = Role::findByName('super_admin');

        $this->assertTrue($role->hasPermissionTo('ManageSecurity:User'));

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();

        $this->actingAs($admin);

        Livewire::test(EditRole::class, ['record' => $role->getKey()])
            ->call('save')
            ->assertHasNoFormErrors();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertTrue($role->fresh()->hasPermissionTo('ManageSecurity:User'));
        $this->assertTrue($admin->fresh()->can('manageSecurity', User::class));
    }
}
