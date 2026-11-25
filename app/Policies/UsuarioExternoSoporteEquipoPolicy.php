<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\UsuarioExternoSoporteEquipo;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class UsuarioExternoSoporteEquipoPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:UsuarioExternoSoporteEquipo');
    }

    public function view(AuthUser $authUser, UsuarioExternoSoporteEquipo $usuarioExternoSoporte): bool
    {
        return $authUser->can('View:UsuarioExternoSoporteEquipo');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:UsuarioExternoSoporteEquipo');
    }

    public function update(AuthUser $authUser, UsuarioExternoSoporteEquipo $usuarioExternoSoporte): bool
    {
        return $authUser->can('Update:UsuarioExternoSoporteEquipo');
    }

    public function delete(AuthUser $authUser, UsuarioExternoSoporteEquipo $usuarioExternoSoporte): bool
    {
        return $authUser->can('Delete:UsuarioExternoSoporteEquipo');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:UsuarioExternoSoporteEquipo');
    }

    public function restore(AuthUser $authUser, UsuarioExternoSoporteEquipo $usuarioExternoSoporte): bool
    {
        return $authUser->can('Restore:UsuarioExternoSoporteEquipo');
    }

    public function forceDelete(AuthUser $authUser, UsuarioExternoSoporteEquipo $usuarioExternoSoporte): bool
    {
        return $authUser->can('ForceDelete:UsuarioExternoSoporteEquipo');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:UsuarioExternoSoporteEquipo');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:UsuarioExternoSoporteEquipo');
    }
}
