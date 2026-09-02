<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RecepcionCelular;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class RecepcionCelularPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RecepcionCelular');
    }

    public function view(AuthUser $authUser, RecepcionCelular $recepcionCelular): bool
    {
        return $authUser->can('View:RecepcionCelular');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RecepcionCelular');
    }

    public function update(AuthUser $authUser, RecepcionCelular $recepcionCelular): bool
    {
        return $authUser->can('Update:RecepcionCelular');
    }

    public function delete(AuthUser $authUser, RecepcionCelular $recepcionCelular): bool
    {
        return $authUser->can('Delete:RecepcionCelular');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:RecepcionCelular');
    }

    public function restore(AuthUser $authUser, RecepcionCelular $recepcionCelular): bool
    {
        return $authUser->can('Restore:RecepcionCelular');
    }

    public function forceDelete(AuthUser $authUser, RecepcionCelular $recepcionCelular): bool
    {
        return $authUser->can('ForceDelete:RecepcionCelular');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RecepcionCelular');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RecepcionCelular');
    }
}
