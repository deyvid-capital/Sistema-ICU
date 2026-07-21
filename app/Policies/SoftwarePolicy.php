<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Software;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SoftwarePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Software');
    }

    public function view(AuthUser $authUser, Software $software): bool
    {
        return $authUser->can('View:Software');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Software');
    }

    public function update(AuthUser $authUser, Software $software): bool
    {
        return $authUser->can('Update:Software');
    }

    public function delete(AuthUser $authUser, Software $software): bool
    {
        return $authUser->can('Delete:Software');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Software');
    }

    public function restore(AuthUser $authUser, Software $software): bool
    {
        return $authUser->can('Restore:Software');
    }

    public function forceDelete(AuthUser $authUser, Software $software): bool
    {
        return $authUser->can('ForceDelete:Software');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Software');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Software');
    }

    public function replicate(AuthUser $authUser, Software $software): bool
    {
        return $authUser->can('Replicate:Software');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Software');
    }
}
