<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Las páginas «Tema y colores» y «Marca e identidad» se retiraron (PLAN-002):
 * la apariencia vive en el plugin `capitalsafi/filament-apariencia`. Shield
 * generó `View:TemaColores` y `View:Marca` a partir de esas páginas, así que se
 * borran junto con sus asignaciones a roles y usuarios.
 */
return new class extends Migration
{
    /**
     * @var list<string>
     */
    private const PERMISOS = ['View:TemaColores', 'View:Marca'];

    public function up(): void
    {
        $tablas = config('permission.table_names');

        $ids = DB::table($tablas['permissions'])
            ->whereIn('name', self::PERMISOS)
            ->pluck('id');

        if ($ids->isNotEmpty()) {
            DB::table($tablas['role_has_permissions'])->whereIn('permission_id', $ids)->delete();
            DB::table($tablas['model_has_permissions'])->whereIn('permission_id', $ids)->delete();
            DB::table($tablas['permissions'])->whereIn('id', $ids)->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Las páginas ya no existen: no hay nada que restaurar.
     */
    public function down(): void
    {
        //
    }
};
