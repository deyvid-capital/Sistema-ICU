<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PersonalExternoSoporteTecnico;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class PersonalExternoSoporteTecnicoPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PersonalExternoSoporteTecnico');
    }

    public function view(AuthUser $authUser, PersonalExternoSoporteTecnico $personalExternoSoporte): bool
    {
        return $authUser->can('View:PersonalExternoSoporteTecnico');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PersonalExternoSoporteTecnico');
    }

    public function update(AuthUser $authUser, PersonalExternoSoporteTecnico $personalExternoSoporte): bool
    {
        return $authUser->can('Update:PersonalExternoSoporteTecnico');
    }

    public function delete(AuthUser $authUser, PersonalExternoSoporteTecnico $personalExternoSoporte): bool
    {
        return $authUser->can('Delete:PersonalExternoSoporteTecnico');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:PersonalExternoSoporteTecnico');
    }

    public function restore(AuthUser $authUser, PersonalExternoSoporteTecnico $personalExternoSoporte): bool
    {
        return $authUser->can('Restore:PersonalExternoSoporteTecnico');
    }

    public function forceDelete(AuthUser $authUser, PersonalExternoSoporteTecnico $personalExternoSoporte): bool
    {
        return $authUser->can('ForceDelete:PersonalExternoSoporteTecnico');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PersonalExternoSoporteTecnico');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PersonalExternoSoporteTecnico');
    }
}
