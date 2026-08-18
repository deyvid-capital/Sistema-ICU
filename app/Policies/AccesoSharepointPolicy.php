<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\AccesoSharepoint;
use Illuminate\Auth\Access\HandlesAuthorization;

class AccesoSharepointPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AccesoSharepoint');
    }

    public function view(AuthUser $authUser, AccesoSharepoint $accesoSharepoint): bool
    {
        return $authUser->can('View:AccesoSharepoint');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AccesoSharepoint');
    }

    public function update(AuthUser $authUser, AccesoSharepoint $accesoSharepoint): bool
    {
        return $authUser->can('Update:AccesoSharepoint');
    }

    public function delete(AuthUser $authUser, AccesoSharepoint $accesoSharepoint): bool
    {
        return $authUser->can('Delete:AccesoSharepoint');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:AccesoSharepoint');
    }

    public function restore(AuthUser $authUser, AccesoSharepoint $accesoSharepoint): bool
    {
        return $authUser->can('Restore:AccesoSharepoint');
    }

    public function forceDelete(AuthUser $authUser, AccesoSharepoint $accesoSharepoint): bool
    {
        return $authUser->can('ForceDelete:AccesoSharepoint');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AccesoSharepoint');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AccesoSharepoint');
    }

    public function replicate(AuthUser $authUser, AccesoSharepoint $accesoSharepoint): bool
    {
        return $authUser->can('Replicate:AccesoSharepoint');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AccesoSharepoint');
    }

}