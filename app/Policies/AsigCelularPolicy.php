<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\AsigCelular;
use Illuminate\Auth\Access\HandlesAuthorization;

class AsigCelularPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AsigCelular');
    }

    public function view(AuthUser $authUser, AsigCelular $asigCelular): bool
    {
        return $authUser->can('View:AsigCelular');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AsigCelular');
    }

    public function update(AuthUser $authUser, AsigCelular $asigCelular): bool
    {
        return $authUser->can('Update:AsigCelular');
    }

    public function delete(AuthUser $authUser, AsigCelular $asigCelular): bool
    {
        return $authUser->can('Delete:AsigCelular');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:AsigCelular');
    }

    public function restore(AuthUser $authUser, AsigCelular $asigCelular): bool
    {
        return $authUser->can('Restore:AsigCelular');
    }

    public function forceDelete(AuthUser $authUser, AsigCelular $asigCelular): bool
    {
        return $authUser->can('ForceDelete:AsigCelular');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AsigCelular');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AsigCelular');
    }

    public function replicate(AuthUser $authUser, AsigCelular $asigCelular): bool
    {
        return $authUser->can('Replicate:AsigCelular');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AsigCelular');
    }

}