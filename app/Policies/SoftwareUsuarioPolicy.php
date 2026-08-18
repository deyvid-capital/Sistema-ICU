<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\SoftwareUsuario;
use Illuminate\Auth\Access\HandlesAuthorization;

class SoftwareUsuarioPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SoftwareUsuario');
    }

    public function view(AuthUser $authUser, SoftwareUsuario $softwareUsuario): bool
    {
        return $authUser->can('View:SoftwareUsuario');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SoftwareUsuario');
    }

    public function update(AuthUser $authUser, SoftwareUsuario $softwareUsuario): bool
    {
        return $authUser->can('Update:SoftwareUsuario');
    }

    public function delete(AuthUser $authUser, SoftwareUsuario $softwareUsuario): bool
    {
        return $authUser->can('Delete:SoftwareUsuario');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SoftwareUsuario');
    }

    public function restore(AuthUser $authUser, SoftwareUsuario $softwareUsuario): bool
    {
        return $authUser->can('Restore:SoftwareUsuario');
    }

    public function forceDelete(AuthUser $authUser, SoftwareUsuario $softwareUsuario): bool
    {
        return $authUser->can('ForceDelete:SoftwareUsuario');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SoftwareUsuario');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SoftwareUsuario');
    }

    public function replicate(AuthUser $authUser, SoftwareUsuario $softwareUsuario): bool
    {
        return $authUser->can('Replicate:SoftwareUsuario');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SoftwareUsuario');
    }

}