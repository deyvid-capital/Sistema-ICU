<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\EstadoSolicitudFirma;
use App\Models\SolicitudFirma;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Además de los permisos Shield, el creador y los firmantes de un envío siempre
 * pueden verlo: firmar no depende de un rol sino de estar en la lista.
 */
class SolicitudFirmaPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SolicitudFirma')
            || $authUser->can('Create:SolicitudFirma')
            || ($authUser instanceof User && $authUser->certificadosFirma()->vigentes()->exists());
    }

    public function view(AuthUser $authUser, SolicitudFirma $solicitudFirma): bool
    {
        return $authUser->can('View:SolicitudFirma')
            || ($authUser instanceof User && ($solicitudFirma->esCreadaPor($authUser) || $solicitudFirma->tieneFirmante($authUser)));
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SolicitudFirma');
    }

    /**
     * Un envío no se edita: se cancela y se crea otro.
     */
    public function update(AuthUser $authUser, SolicitudFirma $solicitudFirma): bool
    {
        return false;
    }

    /**
     * Ubicar los recuadros y enviar: solo borradores, por su creador o un admin.
     */
    public function prepararEnvio(AuthUser $authUser, SolicitudFirma $solicitudFirma): bool
    {
        return $solicitudFirma->estado === EstadoSolicitudFirma::Borrador
            && (($authUser instanceof User && $solicitudFirma->esCreadaPor($authUser)) || $authUser->can('Update:SolicitudFirma'));
    }

    /**
     * Solo se eliminan borradores (nunca enviados), por su creador o un admin.
     */
    public function delete(AuthUser $authUser, SolicitudFirma $solicitudFirma): bool
    {
        return $solicitudFirma->estado === EstadoSolicitudFirma::Borrador
            && (($authUser instanceof User && $solicitudFirma->esCreadaPor($authUser)) || $authUser->can('Delete:SolicitudFirma'));
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function restore(AuthUser $authUser, SolicitudFirma $solicitudFirma): bool
    {
        return false;
    }

    public function forceDelete(AuthUser $authUser, SolicitudFirma $solicitudFirma): bool
    {
        return false;
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return false;
    }
}
