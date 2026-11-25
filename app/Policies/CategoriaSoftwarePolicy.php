<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CategoriaSoftware;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CategoriaSoftwarePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CategoriaSoftware');
    }

    public function view(AuthUser $authUser, CategoriaSoftware $categoriaSoftware): bool
    {
        return $authUser->can('View:CategoriaSoftware');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CategoriaSoftware');
    }

    public function update(AuthUser $authUser, CategoriaSoftware $categoriaSoftware): bool
    {
        return $authUser->can('Update:CategoriaSoftware');
    }

    public function delete(AuthUser $authUser, CategoriaSoftware $categoriaSoftware): bool
    {
        return $authUser->can('Delete:CategoriaSoftware');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CategoriaSoftware');
    }

    public function restore(AuthUser $authUser, CategoriaSoftware $categoriaSoftware): bool
    {
        return $authUser->can('Restore:CategoriaSoftware');
    }

    public function forceDelete(AuthUser $authUser, CategoriaSoftware $categoriaSoftware): bool
    {
        return $authUser->can('ForceDelete:CategoriaSoftware');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CategoriaSoftware');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CategoriaSoftware');
    }
}
