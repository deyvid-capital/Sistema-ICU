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
use Illuminate\Support\Facades\Hash;
use SensitiveParameter;

/**
 * Activación (o reactivación / renovación) de una firma por su titular, en dos pasos:
 * 1. Contraseña del .p12: se abre el archivo y se validan los datos del certificado.
 * 2. Contraseña del sistema: con ella se envuelve la contraseña del .p12.
 *
 * Ver Docs/firma-digital/03-seguridad.md#activación-y-renovación-contraseña-del-p12
 */
class ActivarCertificadoFirma
{
    private const USOS_DE_FIRMA = ['digital_signature', 'non_repudiation'];

    public function __construct(
        private FirmadorClient $firmador,
        private BovedaCertificados $boveda,
        private EnvolturaContrasena $envoltura,
        private AuditorSeguridad $auditor,
    ) {}

    /**
     * Paso 1: abre el .p12 y valida el certificado. No guarda nada.
     *
     * @return array<string, mixed> Datos del certificado devueltos por el firmador.
     *
     * @throws FirmaDigitalException
     */
    public function validarContrasenaP12(CertificadoFirma $certificado, User $usuario, #[SensitiveParameter] string $contrasenaP12): array
    {
        $this->asegurarActivable($certificado, $usuario);

        try {
            $datos = $this->firmador->inspeccionarCertificado($this->boveda->leer($certificado->archivo), $contrasenaP12);
        } catch (FirmadorException $exception) {
            throw $this->traducir($exception, $usuario, $contrasenaP12);
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

        $this->validarDocumento($datos['titular']['documento'] ?? null, $usuario);
        $this->validarNoDuplicado($datos['huella_sha256'], $usuario);

        return $datos;
    }

    /**
     * Paso 2: vuelve a validar el .p12 (no se confía en el paso 1) y activa la firma.
     *
     * @throws FirmaDigitalException
     */
    public function __invoke(
        CertificadoFirma $certificado,
        User $usuario,
        #[SensitiveParameter] string $contrasenaP12,
        #[SensitiveParameter] string $contrasenaSistema,
    ): CertificadoFirma {
        $this->asegurarActivable($certificado, $usuario);

        if (! Hash::check($contrasenaSistema, $usuario->password)) {
            throw FirmaDigitalException::contrasenaSistemaIncorrecta();
        }

        $datos = $this->validarContrasenaP12($certificado, $usuario, $contrasenaP12);
        $envuelta = $this->envoltura->envolver($contrasenaP12, $contrasenaSistema);

        DB::transaction(function () use ($certificado, $usuario, $datos, $envuelta): void {
            $this->retirarAnteriores($certificado, $usuario);

            $certificado->update([
                'estado' => EstadoCertificadoFirma::Activo,
                'activado_at' => now(),
                'contrasena_p12_envuelta' => $envuelta,
                'contrasena_usuario_huella' => $this->envoltura->huellaContrasenaUsuario($usuario->password),
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
            ]);
        });

        $this->auditor->firmaDigitalActivada($usuario);

        return $certificado->refresh();
    }

    /**
     * @throws FirmaDigitalException
     */
    private function asegurarActivable(CertificadoFirma $certificado, User $usuario): void
    {
        if ($certificado->usuario_id !== $usuario->getKey()) {
            throw FirmaDigitalException::noPerteneceAlUsuario();
        }

        if (! in_array($certificado->estado, [EstadoCertificadoFirma::PendienteActivacion, EstadoCertificadoFirma::RequiereReactivacion], true)) {
            throw FirmaDigitalException::noActivable();
        }
    }

    private function traducir(FirmadorException $exception, User $usuario, #[SensitiveParameter] string $contrasenaP12): FirmaDigitalException
    {
        if ($exception->esContrasenaIncorrecta()) {
            $this->auditor->firmaDigitalContrasenaIncorrecta($usuario);

            // Error frecuente: escribir la contraseña del sistema en el paso del .p12.
            return Hash::check($contrasenaP12, $usuario->password)
                ? FirmaDigitalException::escribioContrasenaDelSistema()
                : FirmaDigitalException::contrasenaP12Incorrecta();
        }

        return $exception->codigo === FirmadorException::NO_DISPONIBLE
            ? FirmaDigitalException::firmadorNoDisponible()
            : FirmaDigitalException::archivoInvalido();
    }

    /**
     * Compara solo la parte numérica: el carnet puede tener complemento o
     * extensión (p. ej. "8347375 LP" o "8347375-1B").
     *
     * @throws FirmaDigitalException
     */
    private function validarDocumento(?string $documentoCertificado, User $usuario): void
    {
        if (blank($documentoCertificado)) {
            return;
        }

        if (blank($usuario->carnet)) {
            throw FirmaDigitalException::usuarioSinCarnet();
        }

        if ($this->numeroDocumento($documentoCertificado) !== $this->numeroDocumento($usuario->carnet)) {
            throw FirmaDigitalException::documentoNoCoincide($documentoCertificado, $usuario->carnet);
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
    private function validarNoDuplicado(string $huella, User $usuario): void
    {
        $otro = CertificadoFirma::query()
            ->vigentes()
            ->where('huella_sha256', $huella)
            ->where('usuario_id', '!=', $usuario->getKey())
            ->with('usuario')
            ->first();

        if ($otro !== null) {
            throw FirmaDigitalException::certificadoDuplicado((string) $otro->usuario?->name);
        }
    }

    /**
     * La firma anterior (renovación) o la que requería reactivación queda como historial.
     */
    private function retirarAnteriores(CertificadoFirma $certificado, User $usuario): void
    {
        $usuario->certificadosFirma()
            ->whereKeyNot($certificado->getKey())
            ->whereIn('estado', [EstadoCertificadoFirma::Activo, EstadoCertificadoFirma::RequiereReactivacion])
            ->get()
            ->each(function (CertificadoFirma $anterior): void {
                $archivo = $anterior->archivo;

                $anterior->update([
                    'estado' => EstadoCertificadoFirma::Reemplazado,
                    'archivo' => null,
                    'contrasena_p12_envuelta' => null,
                    'contrasena_usuario_huella' => null,
                ]);

                DB::afterCommit(fn () => $this->boveda->eliminar($archivo));
            });
    }
}
