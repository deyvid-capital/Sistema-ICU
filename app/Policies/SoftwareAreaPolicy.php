<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SoftwareArea;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SoftwareAreaPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SoftwareArea');
    }

    public function view(AuthUser $authUser, SoftwareArea $softwareArea): bool
    {
        return $authUser->can('View:SoftwareArea');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SoftwareArea');
    }

    public function update(AuthUser $authUser, SoftwareArea $softwareArea): bool
    {
        return $authUser->can('Update:SoftwareArea');
    }

    public function delete(AuthUser $authUser, SoftwareArea $softwareArea): bool
    {
        return $authUser->can('Delete:SoftwareArea');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SoftwareArea');
    }

    public function restore(AuthUser $authUser, SoftwareArea $softwareArea): bool
    {
        return $authUser->can('Restore:SoftwareArea');
    }

    public function forceDelete(AuthUser $authUser, SoftwareArea $softwareArea): bool
    {
        return $authUser->can('ForceDelete:SoftwareArea');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SoftwareArea');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SoftwareArea');
    }

    public function replicate(AuthUser $authUser, SoftwareArea $softwareArea): bool
    {
        return $authUser->can('Replicate:SoftwareArea');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SoftwareArea');
    }
}
