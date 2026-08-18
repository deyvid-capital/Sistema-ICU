<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Movimiento;
use Illuminate\Auth\Access\HandlesAuthorization;

class MovimientoPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Movimiento');
    }

    public function view(AuthUser $authUser, Movimiento $movimiento): bool
    {
        return $authUser->can('View:Movimiento');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Movimiento');
    }

    public function update(AuthUser $authUser, Movimiento $movimiento): bool
    {
        return $authUser->can('Update:Movimiento');
    }

    public function delete(AuthUser $authUser, Movimiento $movimiento): bool
    {
        return $authUser->can('Delete:Movimiento');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Movimiento');
    }

    public function restore(AuthUser $authUser, Movimiento $movimiento): bool
    {
        return $authUser->can('Restore:Movimiento');
    }

    public function forceDelete(AuthUser $authUser, Movimiento $movimiento): bool
    {
        return $authUser->can('ForceDelete:Movimiento');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Movimiento');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Movimiento');
    }

    public function replicate(AuthUser $authUser, Movimiento $movimiento): bool
    {
        return $authUser->can('Replicate:Movimiento');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Movimiento');
    }

}