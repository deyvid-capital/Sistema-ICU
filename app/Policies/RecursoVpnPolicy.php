<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\RecursoVpn;
use Illuminate\Auth\Access\HandlesAuthorization;

class RecursoVpnPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RecursoVpn');
    }

    public function view(AuthUser $authUser, RecursoVpn $recursoVpn): bool
    {
        return $authUser->can('View:RecursoVpn');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RecursoVpn');
    }

    public function update(AuthUser $authUser, RecursoVpn $recursoVpn): bool
    {
        return $authUser->can('Update:RecursoVpn');
    }

    public function delete(AuthUser $authUser, RecursoVpn $recursoVpn): bool
    {
        return $authUser->can('Delete:RecursoVpn');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:RecursoVpn');
    }

    public function restore(AuthUser $authUser, RecursoVpn $recursoVpn): bool
    {
        return $authUser->can('Restore:RecursoVpn');
    }

    public function forceDelete(AuthUser $authUser, RecursoVpn $recursoVpn): bool
    {
        return $authUser->can('ForceDelete:RecursoVpn');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RecursoVpn');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RecursoVpn');
    }

    public function replicate(AuthUser $authUser, RecursoVpn $recursoVpn): bool
    {
        return $authUser->can('Replicate:RecursoVpn');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RecursoVpn');
    }

}