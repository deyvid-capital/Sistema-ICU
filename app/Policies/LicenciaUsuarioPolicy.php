<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LicenciaUsuario;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class LicenciaUsuarioPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:LicenciaUsuario');
    }

    public function view(AuthUser $authUser, LicenciaUsuario $licenciaUsuario): bool
    {
        return $authUser->can('View:LicenciaUsuario');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:LicenciaUsuario');
    }

    public function update(AuthUser $authUser, LicenciaUsuario $licenciaUsuario): bool
    {
        return $authUser->can('Update:LicenciaUsuario');
    }

    public function delete(AuthUser $authUser, LicenciaUsuario $licenciaUsuario): bool
    {
        return $authUser->can('Delete:LicenciaUsuario');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:LicenciaUsuario');
    }

    public function restore(AuthUser $authUser, LicenciaUsuario $licenciaUsuario): bool
    {
        return $authUser->can('Restore:LicenciaUsuario');
    }

    public function forceDelete(AuthUser $authUser, LicenciaUsuario $licenciaUsuario): bool
    {
        return $authUser->can('ForceDelete:LicenciaUsuario');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:LicenciaUsuario');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:LicenciaUsuario');
    }
}
