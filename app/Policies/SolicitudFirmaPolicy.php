<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\EstadoSolicitudFirma;
use App\Models\SolicitudFirma;
use App\Models\SolicitudFirmaFirmante;
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
        if ($authUser->can('ViewAny:SolicitudFirma') || $authUser->can('Create:SolicitudFirma')) {
            return true;
        }

        // Firmantes actuales y cualquiera que haya creado o firmado envíos (aunque su firma ya venció).
        return $authUser instanceof User && (
            $authUser->certificadosFirma()->vigentes()->exists()
            || SolicitudFirma::query()->where('creado_por', $authUser->getKey())->exists()
            || SolicitudFirmaFirmante::query()->where('usuario_id', $authUser->getKey())->exists()
        );
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
     * Cancelar un envío en curso: su creador, o un admin con el permiso propio.
     */
    public function cancelar(AuthUser $authUser, SolicitudFirma $solicitudFirma): bool
    {
        return $solicitudFirma->estaEnCurso()
            && (($authUser instanceof User && $solicitudFirma->esCreadaPor($authUser)) || $authUser->can('Cancel:SolicitudFirma'));
    }

    /**
     * Reemplazar un firmante o quitar el orden de un envío en curso: su creador o un admin.
     */
    public function ajustarFirmantes(AuthUser $authUser, SolicitudFirma $solicitudFirma): bool
    {
        return $solicitudFirma->estaEnCurso()
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
