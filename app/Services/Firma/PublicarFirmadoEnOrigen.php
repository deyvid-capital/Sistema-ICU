<?php

namespace App\Services\Firma;

use App\Enums\EstadoDocumentoFirma;
use App\Models\Concerns\TieneEstadoFirma;
use App\Models\SolicitudFirmaDocumento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Copia el PDF firmado de un documento completado sobre su archivo de origen (misma
 * ruta, sin duplicados) y marca el origen como firmado. Idempotente.
 *
 * El original sin firmar no se pierde: queda en la carpeta privada del envío.
 */
class PublicarFirmadoEnOrigen
{
    /**
     * @return bool true si el origen quedó publicado (o ya lo estaba).
     */
    public function __invoke(SolicitudFirmaDocumento $documento): bool
    {
        /** @var (Model&TieneEstadoFirma)|null $origen */
        $origen = $documento->origen;

        if ($documento->estado !== EstadoDocumentoFirma::Completado || $origen === null || ! method_exists($origen, 'marcarFirmado')) {
            return false;
        }

        $disco = Storage::disk($origen::documentDisk());
        $ruta = (string) $origen->getAttribute('url');

        if (! $this->correspondeAlOrigen($documento, $origen, $disco->exists($ruta) ? (string) $disco->get($ruta) : null)) {
            return false;
        }

        $firmado = (string) Storage::disk(SolicitudFirmaDocumento::DISCO)->get($documento->archivo_actual);

        if (! hash_equals($documento->hash_actual, hash('sha256', $firmado))) {
            throw new RuntimeException("El firmado de «{$documento->nombre}» no coincide con su versión registrada; no se publica.");
        }

        if (! $disco->exists($ruta) || hash('sha256', (string) $disco->get($ruta)) !== $documento->hash_actual) {
            if (! $disco->put($ruta, $firmado, ['visibility' => 'private'])) {
                throw new RuntimeException("No se pudo escribir el firmado de «{$documento->nombre}» en su origen.");
            }
        }

        if ($origen->getAttribute('hash_sha256') !== $documento->hash_actual) {
            $origen->forceFill(['firma_documento_id' => $documento->getKey()]);
            $origen->marcarFirmado($documento->hash_actual);
        }

        return true;
    }

    /**
     * El origen apunta a este envío. Sin vínculo (envíos anteriores a la columna), solo
     * si el archivo de origen sigue siendo exactamente el que se envió a firmar: así
     * nunca se pisa un PDF regenerado después.
     */
    private function correspondeAlOrigen(SolicitudFirmaDocumento $documento, Model $origen, ?string $actual): bool
    {
        $vinculado = $origen->getAttribute('firma_documento_id');

        if ($vinculado !== null) {
            return $vinculado === $documento->getKey();
        }

        if ($actual === null) {
            return false;
        }

        if (hash('sha256', $actual) === $documento->hash_actual) {
            return true;
        }

        $original = (string) Storage::disk(SolicitudFirmaDocumento::DISCO)->get($documento->archivo_original);

        return hash('sha256', $actual) === hash('sha256', $original);
    }
}
