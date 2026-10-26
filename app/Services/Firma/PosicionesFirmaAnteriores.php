<?php

namespace App\Services\Firma;

use App\Enums\EstadoSolicitudFirma;
use App\Models\SolicitudFirma;
use App\Models\SolicitudFirmaDocumento;
use App\Models\SolicitudFirmaFirma;

/**
 * "Usar posiciones de la última vez": para cada documento del sistema, toma las
 * posiciones del último envío del mismo tipo de documento y las asigna a los
 * firmantes actuales según su orden (1º con 1º, 2º con 2º...).
 */
class PosicionesFirmaAnteriores
{
    /**
     * @return list<array{documento_id: int, firmante_id: int, pagina: int, x: float, y: float}>
     */
    public function para(SolicitudFirma $solicitud): array
    {
        $firmantes = $solicitud->firmantes()->get()->values();
        $sugeridas = [];

        foreach ($solicitud->documentos()->whereNotNull('origen_type')->get() as $documento) {
            $anterior = SolicitudFirmaDocumento::query()
                ->where('origen_type', $documento->origen_type)
                ->where('solicitud_firma_id', '!=', $solicitud->id)
                ->whereHas('solicitud', fn ($query) => $query->where('estado', '!=', EstadoSolicitudFirma::Borrador))
                ->whereHas('firmas')
                ->with(['firmas.firmante'])
                ->latest('id')
                ->first();

            if ($anterior === null) {
                continue;
            }

            $recuadros = $anterior->firmas->sortBy(fn (SolicitudFirmaFirma $firma): int => $firma->firmante->orden)->values();

            foreach ($firmantes as $indice => $firmante) {
                $recuadro = $recuadros->get($indice);

                if ($recuadro === null || $recuadro->pagina > $documento->totalPaginas()) {
                    continue;
                }

                $sugeridas[] = [
                    'documento_id' => $documento->id,
                    'firmante_id' => $firmante->id,
                    'pagina' => $recuadro->pagina,
                    'x' => $recuadro->x,
                    'y' => $recuadro->y,
                ];
            }
        }

        return $sugeridas;
    }
}
