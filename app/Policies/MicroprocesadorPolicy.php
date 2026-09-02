<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Microprocesador;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class MicroprocesadorPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Microprocesador');
    }

    public function view(AuthUser $authUser, Microprocesador $microprocesador): bool
    {
        return $authUser->can('View:Microprocesador');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Microprocesador');
    }

    public function update(AuthUser $authUser, Microprocesador $microprocesador): bool
    {
        return $authUser->can('Update:Microprocesador');
    }

    public function delete(AuthUser $authUser, Microprocesador $microprocesador): bool
    {
        return $authUser->can('Delete:Microprocesador');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Microprocesador');
    }

    public function restore(AuthUser $authUser, Microprocesador $microprocesador): bool
    {
        return $authUser->can('Restore:Microprocesador');
    }

    public function forceDelete(AuthUser $authUser, Microprocesador $microprocesador): bool
    {
        return $authUser->can('ForceDelete:Microprocesador');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Microprocesador');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Microprocesador');
    }
}
