<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\NotaSalidaEquipo;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class NotaSalidaEquipoPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:NotaSalidaEquipo');
    }

    public function view(AuthUser $authUser, NotaSalidaEquipo $notaSalidaEquipo): bool
    {
        return $authUser->can('View:NotaSalidaEquipo');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:NotaSalidaEquipo');
    }

    public function update(AuthUser $authUser, NotaSalidaEquipo $notaSalidaEquipo): bool
    {
        return $authUser->can('Update:NotaSalidaEquipo');
    }

    public function delete(AuthUser $authUser, NotaSalidaEquipo $notaSalidaEquipo): bool
    {
        return $authUser->can('Delete:NotaSalidaEquipo');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:NotaSalidaEquipo');
    }

    public function restore(AuthUser $authUser, NotaSalidaEquipo $notaSalidaEquipo): bool
    {
        return $authUser->can('Restore:NotaSalidaEquipo');
    }

    public function forceDelete(AuthUser $authUser, NotaSalidaEquipo $notaSalidaEquipo): bool
    {
        return $authUser->can('ForceDelete:NotaSalidaEquipo');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:NotaSalidaEquipo');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:NotaSalidaEquipo');
    }
}
