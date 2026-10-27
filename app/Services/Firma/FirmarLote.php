<?php

namespace App\Services\Firma;

use App\Exceptions\FirmaDigitalException;
use App\Models\SolicitudFirma;
use App\Models\SolicitudFirmaFirma;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * "Firmar todos": la contraseña se valida y el .p12 se abre una sola vez; luego
 * se firma documento por documento, cada uno con su propio bloqueo. Si uno falla
 * (por ejemplo, otra persona lo está firmando) se sigue con los demás.
 *
 * Corre en la misma petición (sin colas, para no guardar credenciales), por eso
 * tiene un tope de documentos.
 */
class FirmarLote
{
    public const MAX_DOCUMENTOS = 20;

    public function __construct(
        private FirmarDocumento $firmarDocumento,
        private AvisosSolicitudFirma $avisos,
    ) {}

    /**
     * @param  list<int>|null  $documentoIds  Limita a estos documentos (null = todos los pendientes).
     */
    public function __invoke(SolicitudFirma $solicitud, User $usuario, CredencialesFirma $credenciales, ?array $documentoIds = null): ResultadoFirmaLote
    {
        $firmas = $solicitud->firmasPendientesDe($usuario)
            ->when($documentoIds !== null, fn ($query) => $query->whereIn('documento_id', $documentoIds))
            ->orderBy('documento_id')
            ->limit(self::MAX_DOCUMENTOS)
            ->get();

        $firmados = [];
        $fallidos = [];
        $siguientes = new Collection;

        foreach ($firmas as $firma) {
            /** @var SolicitudFirmaFirma $firma */
            try {
                $siguientes = $siguientes->merge($this->firmarDocumento->firmarSinAvisar($firma, $usuario, $credenciales));
                $firmados[] = $firma->documento->nombre;
            } catch (FirmaDigitalException $exception) {
                $fallidos[$firma->documento->nombre] = $exception->getMessage();
            }
        }

        $this->avisos->pedirFirma($solicitud, $siguientes);
        $this->avisos->firmaRealizada($solicitud, $usuario, $firmados);

        return new ResultadoFirmaLote($firmados, $fallidos);
    }
}
