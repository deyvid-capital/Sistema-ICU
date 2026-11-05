<?php

namespace App\Services\Firma;

use App\Enums\EstadoCertificadoFirma;
use App\Exceptions\FirmaDigitalException;
use App\Exceptions\FirmadorException;
use App\Models\CertificadoFirma;
use App\Models\User;
use App\Support\Auditoria\AuditorSeguridad;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use SensitiveParameter;

/**
 * El admin activa la firma con la contraseña del .p12: se abre el archivo, se validan
 * los datos del certificado contra el titular y la contraseña queda en custodia
 * (cifrada con FIRMA_ENCRYPTION_KEY). El titular no hace nada: la firma se vincula con
 * su contraseña del sistema al iniciar sesión (VincularCertificadoFirma).
 *
 * Ver Docs/firma-digital/03-seguridad.md#activación-y-renovación-contraseña-del-p12
 */
class ActivarCertificadoFirma
{
    private const USOS_DE_FIRMA = ['digital_signature', 'non_repudiation'];

    public function __construct(
        private FirmadorClient $firmador,
        private BovedaCertificados $boveda,
        private AuditorSeguridad $auditor,
    ) {}

    /**
     * Abre el .p12 y valida el certificado contra el titular. No guarda nada.
     *
     * @return array<string, mixed> Datos del certificado devueltos por el firmador.
     *
     * @throws FirmaDigitalException
     */
    public function validar(User $titular, #[SensitiveParameter] string $contenidoP12, #[SensitiveParameter] string $contrasenaP12): array
    {
        try {
            $datos = $this->firmador->inspeccionarCertificado($contenidoP12, $contrasenaP12);
        } catch (FirmadorException $exception) {
            throw $this->traducir($exception, $titular);
        }

        if (! $datos['clave_corresponde']) {
            throw FirmaDigitalException::clavePrivadaNoCorresponde();
        }

        if (! $datos['vigente']) {
            throw FirmaDigitalException::certificadoVencido(Carbon::parse($datos['vigente_hasta'])->format('d/m/Y'));
        }

        if ($datos['uso_clave'] !== [] && array_intersect(self::USOS_DE_FIRMA, $datos['uso_clave']) === []) {
            throw FirmaDigitalException::sinUsoDeFirma();
        }

        $this->validarDocumento($datos['titular']['documento'] ?? null, $titular);
        $this->validarNoDuplicado($datos['huella_sha256'], $titular);

        return $datos;
    }

    /**
     * Activa una firma ya subida: pendiente del flujo anterior o en pausa sin copia en custodia.
     *
     * @throws FirmaDigitalException
     */
    public function __invoke(CertificadoFirma $certificado, #[SensitiveParameter] string $contrasenaP12, ?User $administrador = null): CertificadoFirma
    {
        if (! in_array($certificado->estado, [EstadoCertificadoFirma::PendienteActivacion, EstadoCertificadoFirma::RequiereReactivacion], true)) {
            throw FirmaDigitalException::noActivable();
        }

        $titular = $certificado->usuario;
        $datos = $this->validar($titular, $this->boveda->leer($certificado->archivo), $contrasenaP12);

        DB::transaction(fn () => $this->aplicar($certificado, $datos, $contrasenaP12));

        $this->auditor->firmaDigitalActivada($titular, $administrador);

        return $certificado->refresh();
    }

    /**
     * Deja la firma activa y pendiente de vincular; acepta una firma aún sin guardar.
     * Las anteriores se retiran antes de guardar (índice único de una activa y una
     * pendiente por usuario). Debe llamarse dentro de una transacción.
     *
     * @param  array<string, mixed>  $datos  Resultado de validar().
     */
    public function aplicar(CertificadoFirma $certificado, array $datos, #[SensitiveParameter] string $contrasenaP12): void
    {
        $this->retirarAnteriores($certificado);

        $certificado->fill([
            'estado' => EstadoCertificadoFirma::Activo,
            'activado_at' => now(),
            'contrasena_p12_envuelta' => null,
            'contrasena_p12_custodia' => $this->boveda->cifrar($contrasenaP12),
            'contrasena_usuario_huella' => null,
            'huella_sha256' => $datos['huella_sha256'],
            'titular_cn' => $datos['titular']['cn'] ?? null,
            'titular_documento' => $datos['titular']['documento'] ?? null,
            'titular_tipo_documento' => $datos['titular']['tipo_documento'] ?? null,
            'titular_cargo' => $datos['titular']['cargo'] ?? null,
            'titular_organizacion' => $datos['titular']['organizacion'] ?? null,
            'emisor' => $datos['emisor']['cn'] ?? null,
            'numero_serie' => $datos['numero_serie'],
            'vigente_desde' => Carbon::parse($datos['vigente_desde']),
            'vigente_hasta' => Carbon::parse($datos['vigente_hasta']),
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
        ])->save();
    }

    private function traducir(FirmadorException $exception, User $titular): FirmaDigitalException
    {
        if ($exception->esContrasenaIncorrecta()) {
            $this->auditor->firmaDigitalContrasenaIncorrecta($titular);

            return FirmaDigitalException::contrasenaP12Incorrecta();
        }

        return match ($exception->codigo) {
            FirmadorException::NO_DISPONIBLE => FirmaDigitalException::firmadorNoDisponible(),
            default => FirmaDigitalException::archivoInvalido(),
        };
    }

    /**
     * Compara solo la parte numérica: el carnet puede tener complemento o
     * extensión (p. ej. "8347375 LP" o "8347375-1B").
     *
     * @throws FirmaDigitalException
     */
    private function validarDocumento(?string $documentoCertificado, User $titular): void
    {
        if (blank($documentoCertificado)) {
            return;
        }

        if (blank($titular->carnet)) {
            throw FirmaDigitalException::usuarioSinCarnet();
        }

        if ($this->numeroDocumento($documentoCertificado) !== $this->numeroDocumento($titular->carnet)) {
            throw FirmaDigitalException::documentoNoCoincide($documentoCertificado, $titular->carnet);
        }
    }

    private function numeroDocumento(string $documento): string
    {
        preg_match('/\d+/', $documento, $coincidencia);

        return ltrim($coincidencia[0] ?? '', '0');
    }

    /**
     * @throws FirmaDigitalException
     */
    private function validarNoDuplicado(string $huella, User $titular): void
    {
        $otro = CertificadoFirma::query()
            ->vigentes()
            ->where('huella_sha256', $huella)
            ->where('usuario_id', '!=', $titular->getKey())
            ->with('usuario')
            ->first();

        if ($otro !== null) {
            throw FirmaDigitalException::certificadoDuplicado((string) $otro->usuario?->name);
        }
    }

    /**
     * La firma anterior (renovación) o la que estaba en pausa queda como historial;
     * un .p12 subido y nunca activado se descarta.
     */
    private function retirarAnteriores(CertificadoFirma $certificado): void
    {
        CertificadoFirma::query()
            ->where('usuario_id', $certificado->usuario_id)
            ->when($certificado->exists, fn ($query) => $query->whereKeyNot($certificado->getKey()))
            ->vigentes()
            ->get()
            ->each(function (CertificadoFirma $anterior): void {
                $archivo = $anterior->archivo;

                $anterior->update([
                    'estado' => $anterior->estado === EstadoCertificadoFirma::PendienteActivacion
                        ? EstadoCertificadoFirma::Revocado
                        : EstadoCertificadoFirma::Reemplazado,
                    'archivo' => null,
                    'contrasena_p12_envuelta' => null,
                    'contrasena_p12_custodia' => null,
                    'contrasena_usuario_huella' => null,
                ]);

                DB::afterCommit(fn () => $this->boveda->eliminar($archivo));
            });
    }
}
