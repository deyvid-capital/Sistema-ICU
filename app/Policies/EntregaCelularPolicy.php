<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\EntregaCelular;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class EntregaCelularPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:EntregaCelular');
    }

    public function view(AuthUser $authUser, EntregaCelular $entregaCelular): bool
    {
        return $authUser->can('View:EntregaCelular');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:EntregaCelular');
    }

    public function update(AuthUser $authUser, EntregaCelular $entregaCelular): bool
    {
        return $authUser->can('Update:EntregaCelular');
    }

    public function delete(AuthUser $authUser, EntregaCelular $entregaCelular): bool
    {
        return $authUser->can('Delete:EntregaCelular');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:EntregaCelular');
    }

    public function restore(AuthUser $authUser, EntregaCelular $entregaCelular): bool
    {
        return $authUser->can('Restore:EntregaCelular');
    }

    public function forceDelete(AuthUser $authUser, EntregaCelular $entregaCelular): bool
    {
        return $authUser->can('ForceDelete:EntregaCelular');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:EntregaCelular');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:EntregaCelular');
    }

    public function replicate(AuthUser $authUser, EntregaCelular $entregaCelular): bool
    {
        return $authUser->can('Replicate:EntregaCelular');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:EntregaCelular');
    }
}
