<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\PermissionMatrix;
use App\Filament\Resources\Roles\RoleResource;
use BezhanSalleh\FilamentShield\Resources\Roles\Pages\EditRole as BaseEditRole;
use BezhanSalleh\FilamentShield\Support\Utils;
use Override;

class EditRole extends BaseEditRole
{
    protected static string $resource = RoleResource::class;

    /**
     * Los permisos se leen del state crudo de Livewire, no del array
     * dehidratado: las secciones que los filtros ocultan no se dehidratan.
     */
    #[Override]
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $mutated = parent::mutateFormDataBeforeSave($data);

        $this->permissions = PermissionMatrix::permissionNamesFromFormState($this->data);

        return $mutated;
    }

    /**
     * El paquete hace un `firstOrCreate` por permiso (casi 500 queries en el
     * rol de super admin). Aquí se resuelven en 1-2.
     */
    #[Override]
    protected function afterSave(): void
    {
        $this->record->syncPermissions(
            PermissionMatrix::resolvePermissions(
                names: $this->permissions,
                guard: $this->record->guard_name ?: Utils::getFilamentAuthGuard(),
            )
        );
    }
}
