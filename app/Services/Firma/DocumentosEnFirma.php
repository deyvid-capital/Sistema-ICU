<?php

namespace App\Services\Firma;

use App\Enums\EstadoDocumentoFirma;
use App\Models\SolicitudFirma;
use App\Models\SolicitudFirmaDocumento;
use Illuminate\Database\Eloquent\Model;

/**
 * ¿Un documento del sistema se está firmando? Mientras tanto no se regenera su
 * PDF: lo firmado debe coincidir con lo que el sistema muestra como oficial.
 */
class DocumentosEnFirma
{
    /**
     * @param  Model  $registro  El PDF (PdfEquipo, PdfCelular, PdfMovimiento) o el registro que lo genera (entrega, movimiento...).
     */
    public function envioEnCurso(Model $registro): ?SolicitudFirma
    {
        $pdfs = method_exists($registro, 'pdfs') ? $registro->pdfs()->get() : collect([$registro]);

        if ($pdfs->isEmpty()) {
            return null;
        }

        return SolicitudFirmaDocumento::query()
            ->where('estado', EstadoDocumentoFirma::EnCurso)
            ->where('origen_type', $pdfs->first()->getMorphClass())
            ->whereIn('origen_id', $pdfs->modelKeys())
            ->with('solicitud')
            ->first()
            ?->solicitud;
    }

    public function mensaje(SolicitudFirma $solicitud): string
    {
        return "El documento está en proceso de firma digital ({$solicitud->codigo}). No se puede volver a generar hasta que termine o se cancele el envío.";
    }
}
