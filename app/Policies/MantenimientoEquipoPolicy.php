<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MantenimientoEquipo;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class MantenimientoEquipoPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MantenimientoEquipo');
    }

    public function view(AuthUser $authUser, MantenimientoEquipo $mantenimientoEquipo): bool
    {
        return $authUser->can('View:MantenimientoEquipo');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MantenimientoEquipo');
    }

    public function update(AuthUser $authUser, MantenimientoEquipo $mantenimientoEquipo): bool
    {
        return $authUser->can('Update:MantenimientoEquipo');
    }

    public function delete(AuthUser $authUser, MantenimientoEquipo $mantenimientoEquipo): bool
    {
        return $authUser->can('Delete:MantenimientoEquipo');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:MantenimientoEquipo');
    }

    public function restore(AuthUser $authUser, MantenimientoEquipo $mantenimientoEquipo): bool
    {
        return $authUser->can('Restore:MantenimientoEquipo');
    }

    public function forceDelete(AuthUser $authUser, MantenimientoEquipo $mantenimientoEquipo): bool
    {
        return $authUser->can('ForceDelete:MantenimientoEquipo');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MantenimientoEquipo');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MantenimientoEquipo');
    }

    public function replicate(AuthUser $authUser, MantenimientoEquipo $mantenimientoEquipo): bool
    {
        return $authUser->can('Replicate:MantenimientoEquipo');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MantenimientoEquipo');
    }
}
