<?php

namespace App\Services\Firma;

use App\Enums\EstadoDocumentoFirma;
use App\Enums\EstadoFirma;
use App\Exceptions\FirmaDigitalException;
use App\Exceptions\FirmadorException;
use App\Filament\Resources\SolicitudesFirma\SolicitudFirmaResource;
use App\Models\SolicitudFirmaDocumento;
use App\Models\SolicitudFirmaFirma;
use App\Models\User;
use App\Notifications\CriticalEventNotification;
use App\Support\Notifications\NotifyAdmins;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Firma un recuadro de un documento. Ver Docs/firma-digital/04-flujo-de-firma-y-bloqueo.md.
 *
 * 1. Bloqueo exclusivo por documento (Redis): nadie más firma ese PDF a la vez.
 * 2. Se verifica que el archivo en disco sea la última versión registrada (hash).
 * 3. El firmador agrega la firma de forma incremental y se verifica el resultado.
 * 4. Se guarda la nueva versión con bloqueo optimista; si otro firmó en medio, se
 *    reintenta una vez sobre la versión nueva.
 */
class FirmarDocumento
{
    private const SEGUNDOS_BLOQUEO = 60;

    private const INTENTOS = 2;

    public function __construct(
        private FirmadorClient $firmador,
        private AvisosSolicitudFirma $avisos,
    ) {}

    /**
     * @throws FirmaDigitalException
     */
    public function __invoke(SolicitudFirmaFirma $firma, User $usuario, CredencialesFirma $credenciales): SolicitudFirmaDocumento
    {
        $siguientes = $this->firmarSinAvisar($firma, $usuario, $credenciales);
        $solicitud = $firma->documento->solicitud;

        $this->avisos->pedirFirma($solicitud, $siguientes);
        $this->avisos->firmaRealizada($solicitud, $usuario, [$firma->documento->nombre]);

        return $firma->documento->refresh();
    }

    /**
     * Firma y devuelve a quién le toca ahora (orden obligatorio), sin avisarle:
     * en una firma en lote se avisa una sola vez al final.
     *
     * @return Collection<int, User>
     *
     * @throws FirmaDigitalException
     */
    public function firmarSinAvisar(SolicitudFirmaFirma $firma, User $usuario, CredencialesFirma $credenciales): Collection
    {
        $this->validar($firma, $usuario, $credenciales);

        $documento = $firma->documento;
        $bloqueo = Cache::lock("firma-documento:{$documento->id}", self::SEGUNDOS_BLOQUEO);

        if (! $bloqueo->get()) {
            throw FirmaDigitalException::documentoEnUso($documento->nombre);
        }

        try {
            for ($intento = 1; $intento <= self::INTENTOS; $intento++) {
                if ($this->intentarFirmar($documento->refresh(), $firma->refresh(), $credenciales)) {
                    return $this->siguientesEnOrden($firma);
                }
            }

            throw FirmaDigitalException::documentoEnUso($documento->nombre);
        } finally {
            $bloqueo->release();
        }
    }

    /**
     * @throws FirmaDigitalException
     */
    private function validar(SolicitudFirmaFirma $firma, User $usuario, CredencialesFirma $credenciales): void
    {
        $firma->loadMissing(['documento.solicitud', 'firmante']);

        if ($firma->firmante->usuario_id !== $usuario->getKey() || $credenciales->certificado->usuario_id !== $usuario->getKey()) {
            throw FirmaDigitalException::noEsTuFirma();
        }

        if (! $firma->documento->solicitud->estaEnCurso()) {
            throw FirmaDigitalException::solicitudNoEnCurso();
        }

        if (! $firma->estaPendiente() || $firma->documento->estado !== EstadoDocumentoFirma::EnCurso) {
            throw FirmaDigitalException::firmaNoPendiente();
        }

        if (! $firma->leToca()) {
            throw FirmaDigitalException::noEsTuTurno((string) $firma->anteriorPendiente()?->firmante->usuario?->name);
        }
    }

    /**
     * @return bool false si otro firmante guardó una versión en medio (hay que reintentar).
     *
     * @throws FirmaDigitalException
     */
    private function intentarFirmar(SolicitudFirmaDocumento $documento, SolicitudFirmaFirma $firma, CredencialesFirma $credenciales): bool
    {
        if (! $firma->estaPendiente()) {
            throw FirmaDigitalException::firmaNoPendiente();
        }

        $disco = Storage::disk(SolicitudFirmaDocumento::DISCO);
        $version = $documento->version;
        $actual = (string) $disco->get($documento->archivo_actual);

        if (! hash_equals($documento->hash_actual, hash('sha256', $actual))) {
            $this->alertarAlteracion($documento);

            throw FirmaDigitalException::documentoAlterado($documento->nombre);
        }

        $firmado = $this->firmar($documento, $firma, $credenciales, $actual);
        $this->verificar($documento, $firma, $firmado);

        $ruta = dirname($documento->archivo_original).'/v'.($version + 1).'.pdf';
        $disco->put($ruta, $firmado);

        $guardado = DB::transaction(fn (): bool => $this->registrar($documento, $firma, $credenciales, $version, $ruta, hash('sha256', $firmado)));

        if (! $guardado) {
            $disco->delete($ruta);
        }

        return $guardado;
    }

    /**
     * @throws FirmaDigitalException
     */
    private function firmar(SolicitudFirmaDocumento $documento, SolicitudFirmaFirma $firma, CredencialesFirma $credenciales, string $pdf): string
    {
        try {
            return $this->firmador->firmarPdf(
                $pdf,
                $credenciales->p12(),
                $credenciales->contrasenaP12(),
                $firma->campo,
                $credenciales->certificado->datosSello(),
                SelloFirma::logo(),
                'Firma de '.$documento->solicitud->asunto,
            );
        } catch (FirmadorException $exception) {
            throw match ($exception->codigo) {
                FirmadorException::CERTIFICADO_VENCIDO => FirmaDigitalException::certificadoVencido(),
                FirmadorException::CONTRASENA_INCORRECTA => FirmaDigitalException::envolturaInvalida(),
                FirmadorException::CAMPO_YA_FIRMADO => FirmaDigitalException::firmaNoPendiente(),
                FirmadorException::NO_DISPONIBLE => FirmaDigitalException::firmadorNoDisponible(),
                default => FirmaDigitalException::pdfInvalido($documento->nombre),
            };
        }
    }

    /**
     * Todas las firmas del archivo resultante deben estar íntegras y válidas, y la nueva debe estar.
     *
     * @throws FirmaDigitalException
     */
    private function verificar(SolicitudFirmaDocumento $documento, SolicitudFirmaFirma $firma, string $pdf): void
    {
        try {
            $firmas = collect($this->firmador->verificarPdf($pdf));
        } catch (FirmadorException) {
            throw FirmaDigitalException::firmadorNoDisponible();
        }

        $todasValidas = $firmas->every(fn (array $resultado): bool => $resultado['intacta'] && $resultado['valida']);

        if (! $todasValidas || ! $firmas->contains('campo', $firma->campo)) {
            throw FirmaDigitalException::firmaNoVerificada($documento->nombre);
        }
    }

    private function registrar(
        SolicitudFirmaDocumento $documento,
        SolicitudFirmaFirma $firma,
        CredencialesFirma $credenciales,
        int $version,
        string $ruta,
        string $hash,
    ): bool {
        $actualizados = SolicitudFirmaDocumento::query()
            ->whereKey($documento->getKey())
            ->where('version', $version)
            ->update(['version' => $version + 1, 'archivo_actual' => $ruta, 'hash_actual' => $hash]);

        if ($actualizados === 0) {
            return false;
        }

        $firma->update([
            'estado' => EstadoFirma::Firmado,
            'firmado_at' => now(),
            'certificado_firma_id' => $credenciales->certificado->getKey(),
        ]);

        $documento->versiones()->create([
            'version' => $version + 1,
            'archivo' => $ruta,
            'hash_sha256' => $hash,
            'firma_id' => $firma->getKey(),
        ]);

        $documento->refresh()->completarSiCorresponde();

        if ($documento->solicitud->finalizarSiCorresponde()) {
            $this->avisos->finalizada($documento->solicitud);
        }

        return true;
    }

    /**
     * Con orden obligatorio, el siguiente firmante pendiente de este documento al que ahora le toca.
     *
     * @return Collection<int, User>
     */
    private function siguientesEnOrden(SolicitudFirmaFirma $firma): Collection
    {
        $documento = $firma->documento->refresh();

        if (! $documento->solicitud->orden_obligatorio || ! $documento->solicitud->estaEnCurso()) {
            return collect();
        }

        return $documento->firmas()
            ->where('estado', EstadoFirma::Pendiente)
            ->with(['firmante.usuario', 'documento.solicitud'])
            ->get()
            ->filter(fn (SolicitudFirmaFirma $pendiente): bool => $pendiente->leToca())
            ->map(fn (SolicitudFirmaFirma $pendiente): ?User => $pendiente->firmante->usuario)
            ->filter()
            ->values();
    }

    private function alertarAlteracion(SolicitudFirmaDocumento $documento): void
    {
        NotifyAdmins::dispatch(
            title: 'Documento en firma alterado',
            body: "El archivo de «{$documento->nombre}» ({$documento->solicitud->codigo}) no coincide con su versión registrada. Se detuvo la firma.",
            url: SolicitudFirmaResource::getUrl('view', ['record' => $documento->solicitud], panel: 'admin'),
            icon: 'heroicon-o-shield-exclamation',
            color: 'danger',
            severity: CriticalEventNotification::SEVERIDAD_ALTA,
        );
    }
}
