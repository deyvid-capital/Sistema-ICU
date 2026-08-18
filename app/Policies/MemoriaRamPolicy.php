<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\MemoriaRam;
use Illuminate\Auth\Access\HandlesAuthorization;

class MemoriaRamPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MemoriaRam');
    }

    public function view(AuthUser $authUser, MemoriaRam $memoriaRam): bool
    {
        return $authUser->can('View:MemoriaRam');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MemoriaRam');
    }

    public function update(AuthUser $authUser, MemoriaRam $memoriaRam): bool
    {
        return $authUser->can('Update:MemoriaRam');
    }

    public function delete(AuthUser $authUser, MemoriaRam $memoriaRam): bool
    {
        return $authUser->can('Delete:MemoriaRam');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:MemoriaRam');
    }

    public function restore(AuthUser $authUser, MemoriaRam $memoriaRam): bool
    {
        return $authUser->can('Restore:MemoriaRam');
    }

    public function forceDelete(AuthUser $authUser, MemoriaRam $memoriaRam): bool
    {
        return $authUser->can('ForceDelete:MemoriaRam');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MemoriaRam');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MemoriaRam');
    }

    public function replicate(AuthUser $authUser, MemoriaRam $memoriaRam): bool
    {
        return $authUser->can('Replicate:MemoriaRam');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MemoriaRam');
    }

}