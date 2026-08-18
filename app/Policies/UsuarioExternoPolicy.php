<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\UsuarioExterno;
use Illuminate\Auth\Access\HandlesAuthorization;

class UsuarioExternoPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:UsuarioExterno');
    }

    public function view(AuthUser $authUser, UsuarioExterno $usuarioExterno): bool
    {
        return $authUser->can('View:UsuarioExterno');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:UsuarioExterno');
    }

    public function update(AuthUser $authUser, UsuarioExterno $usuarioExterno): bool
    {
        return $authUser->can('Update:UsuarioExterno');
    }

    public function delete(AuthUser $authUser, UsuarioExterno $usuarioExterno): bool
    {
        return $authUser->can('Delete:UsuarioExterno');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:UsuarioExterno');
    }

    public function restore(AuthUser $authUser, UsuarioExterno $usuarioExterno): bool
    {
        return $authUser->can('Restore:UsuarioExterno');
    }

    public function forceDelete(AuthUser $authUser, UsuarioExterno $usuarioExterno): bool
    {
        return $authUser->can('ForceDelete:UsuarioExterno');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:UsuarioExterno');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:UsuarioExterno');
    }

    public function replicate(AuthUser $authUser, UsuarioExterno $usuarioExterno): bool
    {
        return $authUser->can('Replicate:UsuarioExterno');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:UsuarioExterno');
    }

}