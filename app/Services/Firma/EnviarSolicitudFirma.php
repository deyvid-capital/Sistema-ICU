<?php

namespace App\Services\Firma;

use App\Enums\EstadoDocumentoFirma;
use App\Enums\EstadoSolicitudFirma;
use App\Exceptions\FirmaDigitalException;
use App\Exceptions\FirmadorException;
use App\Models\SolicitudFirma;
use App\Models\SolicitudFirmaDocumento;
use App\Models\SolicitudFirmaFirmante;
use App\Notifications\Firma\FirmaSolicitadaNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * "Enviar a firmar": crea los campos de firma vacíos en cada PDF (todos antes de la
 * primera firma), guarda esa versión 0 y avisa a los firmantes: a todos si el orden
 * es libre, o solo al primero si el envío exige orden.
 */
class EnviarSolicitudFirma
{
    public function __construct(
        private FirmadorClient $firmador,
        private GuardarPosicionesFirma $posiciones,
    ) {}

    /**
     * @throws FirmaDigitalException
     */
    public function __invoke(SolicitudFirma $solicitud): void
    {
        if ($solicitud->estado !== EstadoSolicitudFirma::Borrador) {
            throw FirmaDigitalException::solicitudNoEsBorrador();
        }

        $this->posiciones->validarCompletas($solicitud);

        $documentos = $solicitud->documentos()->with('firmas')->get();
        $disco = Storage::disk(SolicitudFirmaDocumento::DISCO);
        $preparados = [];

        try {
            foreach ($documentos as $documento) {
                $ruta = dirname($documento->archivo_original).'/v0.pdf';
                $contenido = $this->preparar($documento);
                $disco->put($ruta, $contenido);
                $preparados[$documento->id] = ['ruta' => $ruta, 'hash' => hash('sha256', $contenido)];
            }

            DB::transaction(function () use ($solicitud, $documentos, $preparados): void {
                foreach ($documentos as $documento) {
                    $documento->update([
                        'archivo_actual' => $preparados[$documento->id]['ruta'],
                        'hash_actual' => $preparados[$documento->id]['hash'],
                        'version' => 0,
                        'estado' => EstadoDocumentoFirma::EnCurso,
                    ]);

                    $documento->versiones()->create([
                        'version' => 0,
                        'archivo' => $preparados[$documento->id]['ruta'],
                        'hash_sha256' => $preparados[$documento->id]['hash'],
                    ]);
                }

                $solicitud->update([
                    'estado' => EstadoSolicitudFirma::EnCurso,
                    'enviada_at' => now(),
                ]);

                $this->notificar($solicitud);
            });
        } catch (Throwable $exception) {
            foreach ($preparados as $preparado) {
                $disco->delete($preparado['ruta']);
            }

            throw $exception;
        }
    }

    /**
     * @throws FirmaDigitalException
     */
    private function preparar(SolicitudFirmaDocumento $documento): string
    {
        $campos = $documento->firmas->map(fn ($firma): array => [
            'nombre' => $firma->campo,
            'pagina' => $firma->pagina,
            'x' => $firma->x,
            'y' => $firma->y,
            'ancho' => $firma->ancho,
            'alto' => $firma->alto,
        ])->values()->all();

        try {
            return $this->firmador->prepararPdf(
                (string) Storage::disk(SolicitudFirmaDocumento::DISCO)->get($documento->archivo_original),
                $campos,
            );
        } catch (FirmadorException $exception) {
            throw $exception->codigo === FirmadorException::NO_DISPONIBLE
                ? FirmaDigitalException::firmadorNoDisponible()
                : FirmaDigitalException::pdfInvalido($documento->nombre);
        }
    }

    private function notificar(SolicitudFirma $solicitud): void
    {
        $firmantes = $solicitud->firmantes()->with('usuario')->get();

        if ($solicitud->orden_obligatorio) {
            $firmantes = $firmantes->take(1);
        }

        $firmantes->each(fn (SolicitudFirmaFirmante $firmante) => $firmante->usuario?->notify(
            new FirmaSolicitadaNotification($solicitud),
        ));
    }
}
