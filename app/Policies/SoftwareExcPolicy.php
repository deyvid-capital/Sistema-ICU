<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SoftwareExc;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SoftwareExcPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SoftwareExc');
    }

    public function view(AuthUser $authUser, SoftwareExc $softwareExc): bool
    {
        return $authUser->can('View:SoftwareExc');
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
}
