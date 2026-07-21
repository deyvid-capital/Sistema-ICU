<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AccesoVirtual;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class AccesoVirtualPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AccesoVirtual');
    }

    public function view(AuthUser $authUser, AccesoVirtual $accesoVirtual): bool
    {
        return $authUser->can('View:AccesoVirtual');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AccesoVirtual');
    }

    public function update(AuthUser $authUser, AccesoVirtual $accesoVirtual): bool
    {
        return $authUser->can('Update:AccesoVirtual');
    }

    public function delete(AuthUser $authUser, AccesoVirtual $accesoVirtual): bool
    {
        return $authUser->can('Delete:AccesoVirtual');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:AccesoVirtual');
    }

    public function restore(AuthUser $authUser, AccesoVirtual $accesoVirtual): bool
    {
        return $authUser->can('Restore:AccesoVirtual');
    }

    public function forceDelete(AuthUser $authUser, AccesoVirtual $accesoVirtual): bool
    {
        return $authUser->can('ForceDelete:AccesoVirtual');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AccesoVirtual');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AccesoVirtual');
    }

    public function replicate(AuthUser $authUser, AccesoVirtual $accesoVirtual): bool
    {
        return $authUser->can('Replicate:AccesoVirtual');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AccesoVirtual');
    }
}
