<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Virtual;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class VirtualPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Virtual');
    }

    public function view(AuthUser $authUser, Virtual $virtual): bool
    {
        return $authUser->can('View:Virtual');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Virtual');
    }

    public function update(AuthUser $authUser, Virtual $virtual): bool
    {
        return $authUser->can('Update:Virtual');
    }

    public function delete(AuthUser $authUser, Virtual $virtual): bool
    {
        return $authUser->can('Delete:Virtual');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Virtual');
    }

    public function restore(AuthUser $authUser, Virtual $virtual): bool
    {
        return $authUser->can('Restore:Virtual');
    }

    public function forceDelete(AuthUser $authUser, Virtual $virtual): bool
    {
        return $authUser->can('ForceDelete:Virtual');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Virtual');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Virtual');
    }

    public function replicate(AuthUser $authUser, Virtual $virtual): bool
    {
        return $authUser->can('Replicate:Virtual');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Virtual');
    }
}
