<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Firmante;
use Illuminate\Auth\Access\HandlesAuthorization;

class FirmantePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Firmante');
    }

    public function view(AuthUser $authUser, Firmante $firmante): bool
    {
        return $authUser->can('View:Firmante');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Firmante');
    }

    public function update(AuthUser $authUser, Firmante $firmante): bool
    {
        return $authUser->can('Update:Firmante');
    }

    public function delete(AuthUser $authUser, Firmante $firmante): bool
    {
        return $authUser->can('Delete:Firmante');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Firmante');
    }

    public function restore(AuthUser $authUser, Firmante $firmante): bool
    {
        return $authUser->can('Restore:Firmante');
    }

    public function forceDelete(AuthUser $authUser, Firmante $firmante): bool
    {
        return $authUser->can('ForceDelete:Firmante');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Firmante');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Firmante');
    }

    public function replicate(AuthUser $authUser, Firmante $firmante): bool
    {
        return $authUser->can('Replicate:Firmante');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Firmante');
    }

}