<?php

namespace App\Services\Firma;

use App\Enums\EstadoDocumentoFirma;
use App\Enums\EstadoFirma;
use App\Exceptions\FirmaDigitalException;
use App\Models\SolicitudFirmaFirma;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Un firmante rechaza un documento (motivo obligatorio). El documento queda
 * rechazado; los demás documentos del envío siguen su curso.
 */
class RechazarFirma
{
    public function __construct(private AvisosSolicitudFirma $avisos) {}

    /**
     * @throws FirmaDigitalException
     */
    public function __invoke(SolicitudFirmaFirma $firma, User $usuario, string $motivo): void
    {
        $firma->loadMissing(['documento.solicitud', 'firmante']);

        if ($firma->firmante->usuario_id !== $usuario->getKey()) {
            throw FirmaDigitalException::noEsTuFirma();
        }

        if (! $firma->documento->solicitud->estaEnCurso()) {
            throw FirmaDigitalException::solicitudNoEnCurso();
        }

        if (! $firma->estaPendiente() || $firma->documento->estado !== EstadoDocumentoFirma::EnCurso) {
            throw FirmaDigitalException::firmaNoPendiente();
        }

        DB::transaction(function () use ($firma, $usuario, $motivo): void {
            $firma->update(['estado' => EstadoFirma::Rechazado, 'motivo_rechazo' => $motivo]);
            $firma->documento->update(['estado' => EstadoDocumentoFirma::Rechazado]);

            $this->avisos->rechazada($firma, $usuario, $motivo);

            if ($firma->documento->solicitud->finalizarSiCorresponde()) {
                $this->avisos->finalizada($firma->documento->solicitud);
            }
        });
    }
}
