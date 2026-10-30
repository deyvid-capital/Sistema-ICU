<?php

namespace App\Console\Commands;

use App\Enums\EstadoDocumentoFirma;
use App\Models\SolicitudFirmaDocumento;
use App\Services\Firma\PublicarFirmadoEnOrigen;
use Illuminate\Console\Command;
use Throwable;

/**
 * Publica en su origen los documentos completados que aún no lo estén (backfill, o
 * reparación si falló la cola).
 */
class SincronizarOrigenFirmaCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'firmas:sincronizar-origen';

    /**
     * @var string
     */
    protected $description = 'Copia a su módulo de origen los PDF que terminaron de firmarse';

    public function handle(PublicarFirmadoEnOrigen $publicar): int
    {
        $publicados = 0;
        $errores = 0;

        SolicitudFirmaDocumento::query()
            ->where('estado', EstadoDocumentoFirma::Completado)
            ->whereNotNull('origen_id')
            ->with('origen')
            ->each(function (SolicitudFirmaDocumento $documento) use ($publicar, &$publicados, &$errores): void {
                try {
                    if ($publicar($documento)) {
                        $publicados++;
                    }
                } catch (Throwable $exception) {
                    $errores++;
                    $this->error("{$documento->nombre}: {$exception->getMessage()}");
                }
            });

        $this->info("Documentos publicados o ya al día: {$publicados}. Errores: {$errores}.");

        return $errores === 0 ? self::SUCCESS : self::FAILURE;
    }
}
