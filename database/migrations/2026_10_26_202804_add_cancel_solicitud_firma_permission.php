<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permiso propio `Cancel:SolicitudFirma`: cancelar envíos a firmar de otros
 * usuarios (el creador siempre puede cancelar los suyos). Shield no genera
 * permisos que no salgan de una policy estándar, así que se crea aquí y se
 * concede a super_admin si ya existe.
 */
return new class extends Migration
{
    public function up(): void
    {
        $permiso = Permission::firstOrCreate(['name' => 'Cancel:SolicitudFirma', 'guard_name' => 'web']);

        Role::query()
            ->where('name', config('filament-shield.super_admin.name', 'super_admin'))
            ->where('guard_name', 'web')
            ->first()
            ?->givePermissionTo($permiso);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::query()->where('name', 'Cancel:SolicitudFirma')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
