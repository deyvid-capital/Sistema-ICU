<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\PdfCelular;
use Illuminate\Auth\Access\HandlesAuthorization;

class PdfCelularPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PdfCelular');
    }

    public function view(AuthUser $authUser, PdfCelular $pdfCelular): bool
    {
        return $authUser->can('View:PdfCelular');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PdfCelular');
    }

    public function update(AuthUser $authUser, PdfCelular $pdfCelular): bool
    {
        return $authUser->can('Update:PdfCelular');
    }

    public function delete(AuthUser $authUser, PdfCelular $pdfCelular): bool
    {
        return $authUser->can('Delete:PdfCelular');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:PdfCelular');
    }

    public function restore(AuthUser $authUser, PdfCelular $pdfCelular): bool
    {
        return $authUser->can('Restore:PdfCelular');
    }

    public function forceDelete(AuthUser $authUser, PdfCelular $pdfCelular): bool
    {
        return $authUser->can('ForceDelete:PdfCelular');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PdfCelular');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PdfCelular');
    }

    public function replicate(AuthUser $authUser, PdfCelular $pdfCelular): bool
    {
        return $authUser->can('Replicate:PdfCelular');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PdfCelular');
    }

}