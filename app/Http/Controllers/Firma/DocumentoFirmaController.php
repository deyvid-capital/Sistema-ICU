<?php

namespace App\Http\Controllers\Firma;

use App\Http\Controllers\Controller;
use App\Models\SolicitudFirmaDocumento;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
