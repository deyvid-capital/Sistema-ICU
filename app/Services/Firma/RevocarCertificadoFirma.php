<?php

namespace App\Services\Firma;

use App\Enums\EstadoCertificadoFirma;
use App\Models\CertificadoFirma;
use Illuminate\Support\Facades\DB;

/**
 * El admin elimina la asignación: el .p12 se borra físicamente y el registro queda
 * como "revocado" para la auditoría.
 */
class RevocarCertificadoFirma
{
    public function __construct(private BovedaCertificados $boveda) {}

    public function __invoke(CertificadoFirma $certificado): void
    {
        $archivo = $certificado->archivo;

        DB::transaction(function () use ($certificado, $archivo): void {
            $certificado->update([
                'estado' => EstadoCertificadoFirma::Revocado,
                'archivo' => null,
                'contrasena_p12_envuelta' => null,
                'contrasena_usuario_huella' => null,
            ]);

            DB::afterCommit(fn () => $this->boveda->eliminar($archivo));
        });
    }
}
