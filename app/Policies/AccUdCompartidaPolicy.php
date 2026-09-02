<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AccUdCompartida;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class AccUdCompartidaPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AccUdCompartida');
    }

    public function view(AuthUser $authUser, AccUdCompartida $accUdCompartida): bool
    {
        return $authUser->can('View:AccUdCompartida');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AccUdCompartida');
    }

    public function update(AuthUser $authUser, AccUdCompartida $accUdCompartida): bool
    {
        return $authUser->can('Update:AccUdCompartida');
    }

    public function delete(AuthUser $authUser, AccUdCompartida $accUdCompartida): bool
    {
        return $authUser->can('Delete:AccUdCompartida');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:AccUdCompartida');
    }

    public function restore(AuthUser $authUser, AccUdCompartida $accUdCompartida): bool
    {
        return $authUser->can('Restore:AccUdCompartida');
    }

    public function forceDelete(AuthUser $authUser, AccUdCompartida $accUdCompartida): bool
    {
        return $authUser->can('ForceDelete:AccUdCompartida');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AccUdCompartida');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AccUdCompartida');
    }
}
