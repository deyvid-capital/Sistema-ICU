<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesPermissionsSeeder extends Seeder
{
    /**
     * Roles de negocio a crear (además de super_admin). Editable: agrega aquí
     * los que necesites y se crearán vacíos, para asignarles permisos luego
     * desde el recurso de Roles o el formulario de usuario.
     *
     * @var list<string>
     */
    private array $roles = [
        'admin_ti',
        'operador',
        'auditor',
    ];

    /**
     * Permisos mínimos que se conceden a un rol para que sea útil desde el
     * primer despliegue. Es aditivo e idempotente: nunca usa syncPermissions,
     * para no borrar lo que se haya asignado a mano desde el panel de Roles.
     *
     * @var array<string, list<string>>
     */
    private array $permisosPorRol = [
        'auditor' => [
            'ViewAny:Activity',
            'View:Activity',
        ],
    ];

    public function run(): void
    {
        // 1. Genera los permisos (Ability:Model) del panel admin. No regenera policies.
        Artisan::call('shield:generate', [
            '--all' => true,
            '--panel' => 'admin',
            '--option' => 'permissions',
            '--ignore-existing-policies' => true,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 2. super_admin recibe TODOS los permisos (único rol con permisos por ahora).
        $superAdminName = config('filament-shield.super_admin.name', 'super_admin');
        $superAdmin = Role::firstOrCreate([
            'name' => $superAdminName,
            'guard_name' => 'web',
        ]);
        $superAdmin->syncPermissions(Permission::query()->get());

        // 3. Roles de negocio. Se crean vacíos salvo los de $permisosPorRol.
        foreach ($this->roles as $rol) {
            $role = Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);

            $permisos = $this->permisosPorRol[$rol] ?? [];

            if ($permisos !== []) {
                $role->givePermissionTo(Permission::query()->whereIn('name', $permisos)->get());
            }
        }

        // 4. Única asignación de rol en el seeder: super_admin al admin sembrado.
        User::query()->where('email', 'ti@capitalsafi.com')->first()?->assignRole($superAdminName);
    }
}
