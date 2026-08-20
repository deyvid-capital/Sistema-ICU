<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SeguridadPageTest extends TestCase
{
    use RefreshDatabase;

    private function crearSuperAdmin(): User
    {
        // Genera permisos del panel y crea super_admin con todos ellos.
        Artisan::call('shield:generate', [
            '--all' => true,
            '--panel' => 'admin',
            '--option' => 'permissions',
            '--ignore-existing-policies' => true,
        ]);

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
}
