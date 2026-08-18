<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ModeloLaptop;
use Illuminate\Auth\Access\HandlesAuthorization;

class ModeloLaptopPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ModeloLaptop');
    }

    public function view(AuthUser $authUser, ModeloLaptop $modeloLaptop): bool
    {
        return $authUser->can('View:ModeloLaptop');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ModeloLaptop');
    }

    public function update(AuthUser $authUser, ModeloLaptop $modeloLaptop): bool
    {
        return $authUser->can('Update:ModeloLaptop');
    }

    public function delete(AuthUser $authUser, ModeloLaptop $modeloLaptop): bool
    {
        return $authUser->can('Delete:ModeloLaptop');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ModeloLaptop');
    }

    public function restore(AuthUser $authUser, ModeloLaptop $modeloLaptop): bool
    {
        return $authUser->can('Restore:ModeloLaptop');
    }

    public function forceDelete(AuthUser $authUser, ModeloLaptop $modeloLaptop): bool
    {
        return $authUser->can('ForceDelete:ModeloLaptop');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ModeloLaptop');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ModeloLaptop');
    }

    public function replicate(AuthUser $authUser, ModeloLaptop $modeloLaptop): bool
    {
        return $authUser->can('Replicate:ModeloLaptop');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ModeloLaptop');
    }

}