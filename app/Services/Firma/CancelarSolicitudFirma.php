<?php

namespace App\Services\Firma;

use App\Enums\EstadoDocumentoFirma;
use App\Enums\EstadoSolicitudFirma;
use App\Exceptions\FirmaDigitalException;
use App\Models\SolicitudFirma;
use App\Models\SolicitudFirmaDocumento;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * El creador (o un admin con Cancel:SolicitudFirma) cancela un envío en curso. Las
 * firmas ya hechas quedan en el historial; los documentos pendientes se cancelan.
 */
class CancelarSolicitudFirma
{
    public function __construct(private AvisosSolicitudFirma $avisos) {}

    /**
     * @throws FirmaDigitalException
     */
    public function __invoke(SolicitudFirma $solicitud, User $usuario, string $motivo): void
    {
        if (! $solicitud->estaEnCurso()) {
            throw FirmaDigitalException::solicitudNoEnCurso();
        }

        DB::transaction(function () use ($solicitud, $usuario, $motivo): void {
            $enCurso = $solicitud->documentos()->where('estado', EstadoDocumentoFirma::EnCurso)->get();

            $enCurso->each(fn (SolicitudFirmaDocumento $documento) => $documento->origenVinculado()?->marcarSinFirma());

            $solicitud->documentos()
                ->whereKey($enCurso->modelKeys())
                ->update(['estado' => EstadoDocumentoFirma::Cancelado]);

            $solicitud->update([
                'estado' => EstadoSolicitudFirma::Cancelada,
                'cancelada_at' => now(),
                'cancelada_por' => $usuario->getKey(),
                'motivo_cancelacion' => $motivo,
            ]);

            $this->avisos->cancelada($solicitud, $usuario, $motivo);
        });
    }
}
