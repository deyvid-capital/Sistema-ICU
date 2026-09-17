<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SoftwareExc;
use App\Models\User;
use App\Support\Seguridad\ProteccionSuperAdmin;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SoftwareExcPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        if ($authUser instanceof User && $authUser->hasRole(ProteccionSuperAdmin::nombreRol())) {
            return true;
        }

        if ($authUser->can('ViewAny:SoftwareExc')) {
            return true;
        }

        return SoftwareExc::query()->where('aprobador_id', $authUser->getKey())->exists();
    }

    public function view(AuthUser $authUser, SoftwareExc $softwareExc): bool
    {
        if ($authUser instanceof User && $authUser->hasRole(ProteccionSuperAdmin::nombreRol())) {
            return true;
        }

        if ($authUser->can('View:SoftwareExc')) {
            return true;
        }

        return $authUser->getKey() === $softwareExc->aprobador_id;
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SoftwareExc');
    }

    public function update(AuthUser $authUser, SoftwareExc $softwareExc): bool
    {
        return $authUser->can('Update:SoftwareExc');
    }

    public function delete(AuthUser $authUser, SoftwareExc $softwareExc): bool
    {
        return $authUser->can('Delete:SoftwareExc');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SoftwareExc');
    }

    public function restore(AuthUser $authUser, SoftwareExc $softwareExc): bool
    {
        return $authUser->can('Restore:SoftwareExc');
    }

    public function forceDelete(AuthUser $authUser, SoftwareExc $softwareExc): bool
    {
        return $authUser->can('ForceDelete:SoftwareExc');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SoftwareExc');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SoftwareExc');
    }

    public function approve(AuthUser $authUser, SoftwareExc $softwareExc): bool
    {
        return $authUser instanceof User && $softwareExc->puedeDecidir($authUser);
    }

    public function reject(AuthUser $authUser, SoftwareExc $softwareExc): bool
    {
        return $authUser instanceof User && $softwareExc->puedeDecidir($authUser);
    }
}
