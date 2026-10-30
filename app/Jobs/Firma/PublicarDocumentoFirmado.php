<?php

namespace App\Jobs\Firma;

use App\Models\SolicitudFirmaDocumento;
use App\Services\Firma\PublicarFirmadoEnOrigen;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Publica en su módulo de origen un documento que terminó de firmarse. No maneja
 * credenciales, así que puede ir en cola. Si falla, `firmas:sincronizar-origen` lo repara.
 */
class PublicarDocumentoFirmado implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(public SolicitudFirmaDocumento $documento)
    {
        $this->afterCommit();
    }

    public function handle(PublicarFirmadoEnOrigen $publicar): void
    {
        $publicar($this->documento->refresh());
    }
}
