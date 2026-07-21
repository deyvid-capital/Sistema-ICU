<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Licencia;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class LicenciaPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Licencia');
    }

    public function view(AuthUser $authUser, Licencia $licencia): bool
    {
        return $authUser->can('View:Licencia');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Licencia');
    }

    public function update(AuthUser $authUser, Licencia $licencia): bool
    {
        return $authUser->can('Update:Licencia');
    }

    public function delete(AuthUser $authUser, Licencia $licencia): bool
    {
        return $authUser->can('Delete:Licencia');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Licencia');
    }

    public function restore(AuthUser $authUser, Licencia $licencia): bool
    {
        return $authUser->can('Restore:Licencia');
    }

    public function forceDelete(AuthUser $authUser, Licencia $licencia): bool
    {
        return $authUser->can('ForceDelete:Licencia');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Licencia');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Licencia');
    }

    public function replicate(AuthUser $authUser, Licencia $licencia): bool
    {
        return $authUser->can('Replicate:Licencia');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Licencia');
    }
}
