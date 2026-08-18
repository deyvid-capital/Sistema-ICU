<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\AsignacionEquipo;
use Illuminate\Auth\Access\HandlesAuthorization;

class AsignacionEquipoPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AsignacionEquipo');
    }

    public function view(AuthUser $authUser, AsignacionEquipo $asignacionEquipo): bool
    {
        return $authUser->can('View:AsignacionEquipo');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AsignacionEquipo');
    }

    public function update(AuthUser $authUser, AsignacionEquipo $asignacionEquipo): bool
    {
        return $authUser->can('Update:AsignacionEquipo');
    }

    public function delete(AuthUser $authUser, AsignacionEquipo $asignacionEquipo): bool
    {
        return $authUser->can('Delete:AsignacionEquipo');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:AsignacionEquipo');
    }

    public function restore(AuthUser $authUser, AsignacionEquipo $asignacionEquipo): bool
    {
        return $authUser->can('Restore:AsignacionEquipo');
    }

    public function forceDelete(AuthUser $authUser, AsignacionEquipo $asignacionEquipo): bool
    {
        return $authUser->can('ForceDelete:AsignacionEquipo');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AsignacionEquipo');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AsignacionEquipo');
    }

    public function replicate(AuthUser $authUser, AsignacionEquipo $asignacionEquipo): bool
    {
        return $authUser->can('Replicate:AsignacionEquipo');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AsignacionEquipo');
    }

}