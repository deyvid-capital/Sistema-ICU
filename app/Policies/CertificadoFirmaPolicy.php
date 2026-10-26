<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CertificadoFirma;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CertificadoFirmaPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CertificadoFirma');
    }

    public function view(AuthUser $authUser, CertificadoFirma $certificadoFirma): bool
    {
        return $authUser->can('View:CertificadoFirma');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CertificadoFirma');
    }

    public function update(AuthUser $authUser, CertificadoFirma $certificadoFirma): bool
    {
        return $authUser->can('Update:CertificadoFirma');
    }

    public function delete(AuthUser $authUser, CertificadoFirma $certificadoFirma): bool
    {
        return $authUser->can('Delete:CertificadoFirma');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CertificadoFirma');
    }

    public function restore(AuthUser $authUser, CertificadoFirma $certificadoFirma): bool
    {
        return $authUser->can('Restore:CertificadoFirma');
    }

    public function forceDelete(AuthUser $authUser, CertificadoFirma $certificadoFirma): bool
    {
        return $authUser->can('ForceDelete:CertificadoFirma');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CertificadoFirma');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CertificadoFirma');
    }
}
