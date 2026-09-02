<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Fondo;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class FondoPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Fondo');
    }

    public function view(AuthUser $authUser, Fondo $fondo): bool
    {
        return $authUser->can('View:Fondo');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Fondo');
    }

    public function update(AuthUser $authUser, Fondo $fondo): bool
    {
        return $authUser->can('Update:Fondo');
    }

    public function delete(AuthUser $authUser, Fondo $fondo): bool
    {
        return $authUser->can('Delete:Fondo');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Fondo');
    }

    public function restore(AuthUser $authUser, Fondo $fondo): bool
    {
        return $authUser->can('Restore:Fondo');
    }

    public function forceDelete(AuthUser $authUser, Fondo $fondo): bool
    {
        return $authUser->can('ForceDelete:Fondo');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Fondo');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Fondo');
    }
}
