<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RecepcionEquipo;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class RecepcionEquipoPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RecepcionEquipo');
    }

    public function view(AuthUser $authUser, RecepcionEquipo $recepcionEquipo): bool
    {
        return $authUser->can('View:RecepcionEquipo');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RecepcionEquipo');
    }

    public function update(AuthUser $authUser, RecepcionEquipo $recepcionEquipo): bool
    {
        return $authUser->can('Update:RecepcionEquipo');
    }

    public function delete(AuthUser $authUser, RecepcionEquipo $recepcionEquipo): bool
    {
        return $authUser->can('Delete:RecepcionEquipo');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:RecepcionEquipo');
    }

    public function restore(AuthUser $authUser, RecepcionEquipo $recepcionEquipo): bool
    {
        return $authUser->can('Restore:RecepcionEquipo');
    }

    public function forceDelete(AuthUser $authUser, RecepcionEquipo $recepcionEquipo): bool
    {
        return $authUser->can('ForceDelete:RecepcionEquipo');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RecepcionEquipo');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RecepcionEquipo');
    }

    public function replicate(AuthUser $authUser, RecepcionEquipo $recepcionEquipo): bool
    {
        return $authUser->can('Replicate:RecepcionEquipo');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RecepcionEquipo');
    }
}
