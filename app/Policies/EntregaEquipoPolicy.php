<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\EntregaEquipo;
use Illuminate\Auth\Access\HandlesAuthorization;

class EntregaEquipoPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:EntregaEquipo');
    }

    public function view(AuthUser $authUser, EntregaEquipo $entregaEquipo): bool
    {
        return $authUser->can('View:EntregaEquipo');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:EntregaEquipo');
    }

    public function update(AuthUser $authUser, EntregaEquipo $entregaEquipo): bool
    {
        return $authUser->can('Update:EntregaEquipo');
    }

    public function delete(AuthUser $authUser, EntregaEquipo $entregaEquipo): bool
    {
        return $authUser->can('Delete:EntregaEquipo');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:EntregaEquipo');
    }

    public function restore(AuthUser $authUser, EntregaEquipo $entregaEquipo): bool
    {
        return $authUser->can('Restore:EntregaEquipo');
    }

    public function forceDelete(AuthUser $authUser, EntregaEquipo $entregaEquipo): bool
    {
        return $authUser->can('ForceDelete:EntregaEquipo');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:EntregaEquipo');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:EntregaEquipo');
    }

    public function replicate(AuthUser $authUser, EntregaEquipo $entregaEquipo): bool
    {
        return $authUser->can('Replicate:EntregaEquipo');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:EntregaEquipo');
    }

}