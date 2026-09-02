<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AccesoInternet;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class AccesoInternetPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AccesoInternet');
    }

    public function view(AuthUser $authUser, AccesoInternet $accesoInternet): bool
    {
        return $authUser->can('View:AccesoInternet');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AccesoInternet');
    }

    public function update(AuthUser $authUser, AccesoInternet $accesoInternet): bool
    {
        return $authUser->can('Update:AccesoInternet');
    }

    public function delete(AuthUser $authUser, AccesoInternet $accesoInternet): bool
    {
        return $authUser->can('Delete:AccesoInternet');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:AccesoInternet');
    }

    public function restore(AuthUser $authUser, AccesoInternet $accesoInternet): bool
    {
        return $authUser->can('Restore:AccesoInternet');
    }

    public function forceDelete(AuthUser $authUser, AccesoInternet $accesoInternet): bool
    {
        return $authUser->can('ForceDelete:AccesoInternet');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AccesoInternet');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AccesoInternet');
    }
}
