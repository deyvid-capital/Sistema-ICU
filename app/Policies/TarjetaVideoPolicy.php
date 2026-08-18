<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\TarjetaVideo;
use Illuminate\Auth\Access\HandlesAuthorization;

class TarjetaVideoPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:TarjetaVideo');
    }

    public function view(AuthUser $authUser, TarjetaVideo $tarjetaVideo): bool
    {
        return $authUser->can('View:TarjetaVideo');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:TarjetaVideo');
    }

    public function update(AuthUser $authUser, TarjetaVideo $tarjetaVideo): bool
    {
        return $authUser->can('Update:TarjetaVideo');
    }

    public function delete(AuthUser $authUser, TarjetaVideo $tarjetaVideo): bool
    {
        return $authUser->can('Delete:TarjetaVideo');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:TarjetaVideo');
    }

    public function restore(AuthUser $authUser, TarjetaVideo $tarjetaVideo): bool
    {
        return $authUser->can('Restore:TarjetaVideo');
    }

    public function forceDelete(AuthUser $authUser, TarjetaVideo $tarjetaVideo): bool
    {
        return $authUser->can('ForceDelete:TarjetaVideo');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:TarjetaVideo');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:TarjetaVideo');
    }

    public function replicate(AuthUser $authUser, TarjetaVideo $tarjetaVideo): bool
    {
        return $authUser->can('Replicate:TarjetaVideo');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:TarjetaVideo');
    }

}