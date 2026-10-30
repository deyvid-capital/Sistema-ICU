<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permisos propios `UploadSigned:{Pdf}`: subir un PDF firmado a mano (escaneo) en su
 * módulo de origen. Están declarados en config/filament-shield.php (custom_permissions)
 * para que aparezcan en la pantalla de roles; aquí se crean y se conceden a super_admin.
 */
return new class extends Migration
{
    /**
     * @var list<string>
     */
    private const PERMISOS = ['UploadSigned:PdfEquipo', 'UploadSigned:PdfCelular', 'UploadSigned:PdfMovimiento'];

    public function up(): void
    {
        $permisos = array_map(
            fn (string $nombre): Permission => Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']),
            self::PERMISOS,
        );

        Role::query()
            ->where('name', config('filament-shield.super_admin.name', 'super_admin'))
            ->where('guard_name', 'web')
            ->first()
            ?->givePermissionTo($permisos);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::query()->whereIn('name', self::PERMISOS)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
