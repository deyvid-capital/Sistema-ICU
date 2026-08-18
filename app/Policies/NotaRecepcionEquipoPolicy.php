<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\NotaRecepcionEquipo;
use Illuminate\Auth\Access\HandlesAuthorization;

class NotaRecepcionEquipoPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:NotaRecepcionEquipo');
    }

    public function view(AuthUser $authUser, NotaRecepcionEquipo $notaRecepcionEquipo): bool
    {
        return $authUser->can('View:NotaRecepcionEquipo');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:NotaRecepcionEquipo');
    }

    public function update(AuthUser $authUser, NotaRecepcionEquipo $notaRecepcionEquipo): bool
    {
        return $authUser->can('Update:NotaRecepcionEquipo');
    }

    public function delete(AuthUser $authUser, NotaRecepcionEquipo $notaRecepcionEquipo): bool
    {
        return $authUser->can('Delete:NotaRecepcionEquipo');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:NotaRecepcionEquipo');
    }

    public function restore(AuthUser $authUser, NotaRecepcionEquipo $notaRecepcionEquipo): bool
    {
        return $authUser->can('Restore:NotaRecepcionEquipo');
    }

    public function forceDelete(AuthUser $authUser, NotaRecepcionEquipo $notaRecepcionEquipo): bool
    {
        return $authUser->can('ForceDelete:NotaRecepcionEquipo');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:NotaRecepcionEquipo');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:NotaRecepcionEquipo');
    }

    public function replicate(AuthUser $authUser, NotaRecepcionEquipo $notaRecepcionEquipo): bool
    {
        return $authUser->can('Replicate:NotaRecepcionEquipo');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:NotaRecepcionEquipo');
    }

}