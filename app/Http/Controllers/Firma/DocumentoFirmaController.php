<?php

namespace App\Http\Controllers\Firma;

use App\Http\Controllers\Controller;
use App\Models\SolicitudFirma;
use App\Models\SolicitudFirmaDocumento;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

/**
 * Sirve la versión actual de un documento de un envío. Los PDF viven en el disco
 * privado: solo los ve quien puede ver el envío (creador, firmantes o permiso).
 */
class DocumentoFirmaController extends Controller
{
    public function ver(SolicitudFirmaDocumento $documento): StreamedResponse
    {
        return $this->responder($documento, 'inline');
    }

    public function descargar(SolicitudFirmaDocumento $documento): StreamedResponse
    {
        return $this->responder($documento, 'attachment');
    }

    /**
     * Todos los documentos del envío (en su versión actual) en un ZIP.
     */
    public function zip(SolicitudFirma $solicitud): BinaryFileResponse
    {
        Gate::authorize('view', $solicitud);

        $disco = Storage::disk(SolicitudFirmaDocumento::DISCO);
        $temporal = tempnam(sys_get_temp_dir(), 'firma-zip-');
        $zip = new ZipArchive;
        $zip->open($temporal, ZipArchive::OVERWRITE);

        foreach ($solicitud->documentos as $documento) {
            if ($disco->exists($documento->archivo_actual)) {
                $zip->addFromString($documento->id.'-'.$documento->nombre, (string) $disco->get($documento->archivo_actual));
            }
        }

        $zip->close();

        return response()->download($temporal, "{$solicitud->codigo}.zip", ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend();
    }

    private function responder(SolicitudFirmaDocumento $documento, string $disposicion): StreamedResponse
    {
        Gate::authorize('view', $documento->solicitud);

        $disco = Storage::disk(SolicitudFirmaDocumento::DISCO);
        abort_unless($disco->exists($documento->archivo_actual), 404);

        return $disco->response($documento->archivo_actual, $documento->nombre, [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'private, no-store',
        ], $disposicion);
    }
}
