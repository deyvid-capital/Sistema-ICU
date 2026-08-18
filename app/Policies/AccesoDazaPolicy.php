<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\AccesoDaza;
use Illuminate\Auth\Access\HandlesAuthorization;

class AccesoDazaPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AccesoDaza');
    }

    public function view(AuthUser $authUser, AccesoDaza $accesoDaza): bool
    {
        return $authUser->can('View:AccesoDaza');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AccesoDaza');
    }

    public function update(AuthUser $authUser, AccesoDaza $accesoDaza): bool
    {
        return $authUser->can('Update:AccesoDaza');
    }

    public function delete(AuthUser $authUser, AccesoDaza $accesoDaza): bool
    {
        return $authUser->can('Delete:AccesoDaza');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:AccesoDaza');
    }

    public function restore(AuthUser $authUser, AccesoDaza $accesoDaza): bool
    {
        return $authUser->can('Restore:AccesoDaza');
    }

    public function forceDelete(AuthUser $authUser, AccesoDaza $accesoDaza): bool
    {
        return $authUser->can('ForceDelete:AccesoDaza');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AccesoDaza');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AccesoDaza');
    }

    public function replicate(AuthUser $authUser, AccesoDaza $accesoDaza): bool
    {
        return $authUser->can('Replicate:AccesoDaza');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AccesoDaza');
    }

}