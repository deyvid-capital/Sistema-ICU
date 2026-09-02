<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AsigCargadorCel;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class AsigCargadorCelPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AsigCargadorCel');
    }

    public function view(AuthUser $authUser, AsigCargadorCel $asigCargadorCel): bool
    {
        return $authUser->can('View:AsigCargadorCel');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AsigCargadorCel');
    }

    public function update(AuthUser $authUser, AsigCargadorCel $asigCargadorCel): bool
    {
        return $authUser->can('Update:AsigCargadorCel');
    }

    public function delete(AuthUser $authUser, AsigCargadorCel $asigCargadorCel): bool
    {
        return $authUser->can('Delete:AsigCargadorCel');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:AsigCargadorCel');
    }

    public function restore(AuthUser $authUser, AsigCargadorCel $asigCargadorCel): bool
    {
        return $authUser->can('Restore:AsigCargadorCel');
    }

    public function forceDelete(AuthUser $authUser, AsigCargadorCel $asigCargadorCel): bool
    {
        return $authUser->can('ForceDelete:AsigCargadorCel');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AsigCargadorCel');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AsigCargadorCel');
    }
}
