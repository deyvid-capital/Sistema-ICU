<?php

namespace App\Services\Firma;

use App\Enums\EstadoSolicitudFirma;
use App\Models\SolicitudFirma;
use App\Models\SolicitudFirmaDocumento;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use LogicException;

/**
 * Un borrador nunca se envió a nadie: se elimina por completo, con sus copias de PDF.
 */
class EliminarBorradorSolicitudFirma
{
    public function __invoke(SolicitudFirma $solicitud): void
    {
        if ($solicitud->estado !== EstadoSolicitudFirma::Borrador) {
            throw new LogicException('Solo se pueden eliminar envíos en borrador.');
        }

        $directorio = $solicitud->directorio();

        DB::transaction(fn () => $solicitud->forceDelete());

        Storage::disk(SolicitudFirmaDocumento::DISCO)->deleteDirectory($directorio);
    }
}
