<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SoftwareCargo;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SoftwareCargoPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SoftwareCargo');
    }

    public function view(AuthUser $authUser, SoftwareCargo $softwareCargo): bool
    {
        return $authUser->can('View:SoftwareCargo');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SoftwareCargo');
    }

    public function update(AuthUser $authUser, SoftwareCargo $softwareCargo): bool
    {
        return $authUser->can('Update:SoftwareCargo');
    }

    public function delete(AuthUser $authUser, SoftwareCargo $softwareCargo): bool
    {
        return $authUser->can('Delete:SoftwareCargo');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SoftwareCargo');
    }

    public function restore(AuthUser $authUser, SoftwareCargo $softwareCargo): bool
    {
        return $authUser->can('Restore:SoftwareCargo');
    }

    public function forceDelete(AuthUser $authUser, SoftwareCargo $softwareCargo): bool
    {
        return $authUser->can('ForceDelete:SoftwareCargo');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SoftwareCargo');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SoftwareCargo');
    }
}
