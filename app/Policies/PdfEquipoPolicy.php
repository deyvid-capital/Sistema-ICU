<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PdfEquipo;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class PdfEquipoPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PdfEquipo');
    }

    public function view(AuthUser $authUser, PdfEquipo $pdfEquipo): bool
    {
        return $authUser->can('View:PdfEquipo');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PdfEquipo');
    }

    public function update(AuthUser $authUser, PdfEquipo $pdfEquipo): bool
    {
        return $authUser->can('Update:PdfEquipo');
    }

    public function delete(AuthUser $authUser, PdfEquipo $pdfEquipo): bool
    {
        return $authUser->can('Delete:PdfEquipo');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:PdfEquipo');
    }

    public function restore(AuthUser $authUser, PdfEquipo $pdfEquipo): bool
    {
        return $authUser->can('Restore:PdfEquipo');
    }

    public function forceDelete(AuthUser $authUser, PdfEquipo $pdfEquipo): bool
    {
        return $authUser->can('ForceDelete:PdfEquipo');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PdfEquipo');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PdfEquipo');
    }

    public function replicate(AuthUser $authUser, PdfEquipo $pdfEquipo): bool
    {
        return $authUser->can('Replicate:PdfEquipo');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PdfEquipo');
    }
}
