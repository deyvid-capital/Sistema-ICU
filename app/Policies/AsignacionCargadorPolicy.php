<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AsignacionCargador;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class AsignacionCargadorPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AsignacionCargador');
    }

    public function view(AuthUser $authUser, AsignacionCargador $asignacionCargador): bool
    {
        return $authUser->can('View:AsignacionCargador');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AsignacionCargador');
    }

    public function update(AuthUser $authUser, AsignacionCargador $asignacionCargador): bool
    {
        return $authUser->can('Update:AsignacionCargador');
    }

    public function delete(AuthUser $authUser, AsignacionCargador $asignacionCargador): bool
    {
        return $authUser->can('Delete:AsignacionCargador');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:AsignacionCargador');
    }

    public function restore(AuthUser $authUser, AsignacionCargador $asignacionCargador): bool
    {
        return $authUser->can('Restore:AsignacionCargador');
    }

    public function forceDelete(AuthUser $authUser, AsignacionCargador $asignacionCargador): bool
    {
        return $authUser->can('ForceDelete:AsignacionCargador');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AsignacionCargador');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AsignacionCargador');
    }

    public function replicate(AuthUser $authUser, AsignacionCargador $asignacionCargador): bool
    {
        return $authUser->can('Replicate:AsignacionCargador');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AsignacionCargador');
    }
}
