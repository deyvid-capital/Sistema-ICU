<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\PermissionMatrix;
use App\Filament\Resources\Roles\RoleResource;
use BezhanSalleh\FilamentShield\Resources\Roles\Pages\CreateRole as BaseCreateRole;
use BezhanSalleh\FilamentShield\Support\Utils;
use Override;

class CreateRole extends BaseCreateRole
{
    protected static string $resource = RoleResource::class;

    /**
     * @see EditRole::mutateFormDataBeforeSave()
     */
    #[Override]
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $mutated = parent::mutateFormDataBeforeCreate($data);

        $this->permissions = PermissionMatrix::permissionNamesFromFormState($this->data);

        return $mutated;
    }

    #[Override]
    protected function afterCreate(): void
    {
        $this->record->syncPermissions(
            PermissionMatrix::resolvePermissions(
                names: $this->permissions,
                guard: $this->record->guard_name ?: Utils::getFilamentAuthGuard(),
            )
        );
    }
}
