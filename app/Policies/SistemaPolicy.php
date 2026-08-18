<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Sistema;
use Illuminate\Auth\Access\HandlesAuthorization;

class SistemaPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Sistema');
    }

    public function view(AuthUser $authUser, Sistema $sistema): bool
    {
        return $authUser->can('View:Sistema');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Sistema');
    }

    public function update(AuthUser $authUser, Sistema $sistema): bool
    {
        return $authUser->can('Update:Sistema');
    }

    public function delete(AuthUser $authUser, Sistema $sistema): bool
    {
        return $authUser->can('Delete:Sistema');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Sistema');
    }

    public function restore(AuthUser $authUser, Sistema $sistema): bool
    {
        return $authUser->can('Restore:Sistema');
    }

    public function forceDelete(AuthUser $authUser, Sistema $sistema): bool
    {
        return $authUser->can('ForceDelete:Sistema');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Sistema');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Sistema');
    }

    public function replicate(AuthUser $authUser, Sistema $sistema): bool
    {
        return $authUser->can('Replicate:Sistema');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Sistema');
    }

}