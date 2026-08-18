<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ReciboEquipo;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReciboEquipoPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ReciboEquipo');
    }

    public function view(AuthUser $authUser, ReciboEquipo $reciboEquipo): bool
    {
        return $authUser->can('View:ReciboEquipo');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ReciboEquipo');
    }

    public function update(AuthUser $authUser, ReciboEquipo $reciboEquipo): bool
    {
        return $authUser->can('Update:ReciboEquipo');
    }

    public function delete(AuthUser $authUser, ReciboEquipo $reciboEquipo): bool
    {
        return $authUser->can('Delete:ReciboEquipo');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ReciboEquipo');
    }

    public function restore(AuthUser $authUser, ReciboEquipo $reciboEquipo): bool
    {
        return $authUser->can('Restore:ReciboEquipo');
    }

    public function forceDelete(AuthUser $authUser, ReciboEquipo $reciboEquipo): bool
    {
        return $authUser->can('ForceDelete:ReciboEquipo');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ReciboEquipo');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ReciboEquipo');
    }

    public function replicate(AuthUser $authUser, ReciboEquipo $reciboEquipo): bool
    {
        return $authUser->can('Replicate:ReciboEquipo');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ReciboEquipo');
    }

}