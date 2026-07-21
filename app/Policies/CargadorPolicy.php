<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Cargador;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CargadorPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Cargador');
    }

    public function view(AuthUser $authUser, Cargador $cargador): bool
    {
        return $authUser->can('View:Cargador');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Cargador');
    }

    public function update(AuthUser $authUser, Cargador $cargador): bool
    {
        return $authUser->can('Update:Cargador');
    }

    public function delete(AuthUser $authUser, Cargador $cargador): bool
    {
        return $authUser->can('Delete:Cargador');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Cargador');
    }

    public function restore(AuthUser $authUser, Cargador $cargador): bool
    {
        return $authUser->can('Restore:Cargador');
    }

    public function forceDelete(AuthUser $authUser, Cargador $cargador): bool
    {
        return $authUser->can('ForceDelete:Cargador');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Cargador');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Cargador');
    }

    public function replicate(AuthUser $authUser, Cargador $cargador): bool
    {
        return $authUser->can('Replicate:Cargador');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Cargador');
    }
}
