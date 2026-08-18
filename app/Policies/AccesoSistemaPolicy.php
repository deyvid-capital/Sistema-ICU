<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\AccesoSistema;
use Illuminate\Auth\Access\HandlesAuthorization;

class AccesoSistemaPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AccesoSistema');
    }

    public function view(AuthUser $authUser, AccesoSistema $accesoSistema): bool
    {
        return $authUser->can('View:AccesoSistema');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AccesoSistema');
    }

    public function update(AuthUser $authUser, AccesoSistema $accesoSistema): bool
    {
        return $authUser->can('Update:AccesoSistema');
    }

    public function delete(AuthUser $authUser, AccesoSistema $accesoSistema): bool
    {
        return $authUser->can('Delete:AccesoSistema');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:AccesoSistema');
    }

    public function restore(AuthUser $authUser, AccesoSistema $accesoSistema): bool
    {
        return $authUser->can('Restore:AccesoSistema');
    }

    public function forceDelete(AuthUser $authUser, AccesoSistema $accesoSistema): bool
    {
        return $authUser->can('ForceDelete:AccesoSistema');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AccesoSistema');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AccesoSistema');
    }

    public function replicate(AuthUser $authUser, AccesoSistema $accesoSistema): bool
    {
        return $authUser->can('Replicate:AccesoSistema');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AccesoSistema');
    }

}