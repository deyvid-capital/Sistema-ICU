<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Celular;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CelularPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Celular');
    }

    public function view(AuthUser $authUser, Celular $celular): bool
    {
        return $authUser->can('View:Celular');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Celular');
    }

    public function update(AuthUser $authUser, Celular $celular): bool
    {
        return $authUser->can('Update:Celular');
    }

    public function delete(AuthUser $authUser, Celular $celular): bool
    {
        return $authUser->can('Delete:Celular');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Celular');
    }

    public function restore(AuthUser $authUser, Celular $celular): bool
    {
        return $authUser->can('Restore:Celular');
    }

    public function forceDelete(AuthUser $authUser, Celular $celular): bool
    {
        return $authUser->can('ForceDelete:Celular');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Celular');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Celular');
    }
}
