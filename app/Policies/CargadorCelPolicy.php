<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CargadorCel;
use Illuminate\Auth\Access\HandlesAuthorization;

class CargadorCelPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CargadorCel');
    }

    public function view(AuthUser $authUser, CargadorCel $cargadorCel): bool
    {
        return $authUser->can('View:CargadorCel');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CargadorCel');
    }

    public function update(AuthUser $authUser, CargadorCel $cargadorCel): bool
    {
        return $authUser->can('Update:CargadorCel');
    }

    public function delete(AuthUser $authUser, CargadorCel $cargadorCel): bool
    {
        return $authUser->can('Delete:CargadorCel');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CargadorCel');
    }

    public function restore(AuthUser $authUser, CargadorCel $cargadorCel): bool
    {
        return $authUser->can('Restore:CargadorCel');
    }

    public function forceDelete(AuthUser $authUser, CargadorCel $cargadorCel): bool
    {
        return $authUser->can('ForceDelete:CargadorCel');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CargadorCel');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CargadorCel');
    }

    public function replicate(AuthUser $authUser, CargadorCel $cargadorCel): bool
    {
        return $authUser->can('Replicate:CargadorCel');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CargadorCel');
    }

}