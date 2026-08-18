<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\AzureAd;
use Illuminate\Auth\Access\HandlesAuthorization;

class AzureAdPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AzureAd');
    }

    public function view(AuthUser $authUser, AzureAd $azureAd): bool
    {
        return $authUser->can('View:AzureAd');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AzureAd');
    }

    public function update(AuthUser $authUser, AzureAd $azureAd): bool
    {
        return $authUser->can('Update:AzureAd');
    }

    public function delete(AuthUser $authUser, AzureAd $azureAd): bool
    {
        return $authUser->can('Delete:AzureAd');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:AzureAd');
    }

    public function restore(AuthUser $authUser, AzureAd $azureAd): bool
    {
        return $authUser->can('Restore:AzureAd');
    }

    public function forceDelete(AuthUser $authUser, AzureAd $azureAd): bool
    {
        return $authUser->can('ForceDelete:AzureAd');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AzureAd');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AzureAd');
    }

    public function replicate(AuthUser $authUser, AzureAd $azureAd): bool
    {
        return $authUser->can('Replicate:AzureAd');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AzureAd');
    }

}