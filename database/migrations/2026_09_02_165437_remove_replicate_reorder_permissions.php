<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Elimina los permisos `Replicate:*` y `Reorder:*`.
 *
 * La aplicación no usa `ReplicateAction` ni tablas `reorderable()`, por lo que
 * esas habilidades nunca se evalúan. Se quitaron también de
 * `config/filament-shield.php` (`policies.methods`) y de las policies.
 *
 * Las filas de `role_has_permissions` y `model_has_permissions` se borran en
 * cascada: la migración de Spatie declara `cascadeOnDelete()` en ambas claves
 * foráneas.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table(config('permission.table_names.permissions'))
            ->where('name', 'like', 'Replicate:%')
            ->orWhere('name', 'like', 'Reorder:%')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Sin reversa: los permisos se regeneran con
     * `artisan shield:generate --all --panel=admin --option=permissions`
     * después de devolver `replicate`/`reorder` a la configuración.
     */
    public function down(): void
    {
        //
    }
};
