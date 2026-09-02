<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SitioSharepoint;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SitioSharepointPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SitioSharepoint');
    }

    public function view(AuthUser $authUser, SitioSharepoint $sitioSharepoint): bool
    {
        return $authUser->can('View:SitioSharepoint');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SitioSharepoint');
    }

    public function update(AuthUser $authUser, SitioSharepoint $sitioSharepoint): bool
    {
        return $authUser->can('Update:SitioSharepoint');
    }

    public function delete(AuthUser $authUser, SitioSharepoint $sitioSharepoint): bool
    {
        return $authUser->can('Delete:SitioSharepoint');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SitioSharepoint');
    }

    public function restore(AuthUser $authUser, SitioSharepoint $sitioSharepoint): bool
    {
        return $authUser->can('Restore:SitioSharepoint');
    }

    public function forceDelete(AuthUser $authUser, SitioSharepoint $sitioSharepoint): bool
    {
        return $authUser->can('ForceDelete:SitioSharepoint');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SitioSharepoint');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SitioSharepoint');
    }
}
