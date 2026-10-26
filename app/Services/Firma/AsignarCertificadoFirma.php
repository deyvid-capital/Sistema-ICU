<?php

namespace App\Services\Firma;

use App\Enums\EstadoCertificadoFirma;
use App\Exceptions\FirmaDigitalException;
use App\Exceptions\FirmadorException;
use App\Models\CertificadoFirma;
use App\Models\User;
use App\Notifications\Firma\FirmaActivacionPendienteNotification;
use Illuminate\Support\Facades\DB;
use SensitiveParameter;
use Throwable;

/**
 * El admin sube el .p12 de un usuario (asignación inicial o renovación anual).
 *
 * Sin la contraseña solo se puede comprobar que el archivo sea un PKCS#12 válido;
 * los datos del certificado se leen cuando el titular lo activa. Si el usuario ya
 * tiene una firma activa, esta sigue funcionando hasta que active la nueva.
 */
class AsignarCertificadoFirma
{
    public function __construct(
        private FirmadorClient $firmador,
        private BovedaCertificados $boveda,
    ) {}

    /**
     * @throws FirmaDigitalException
     */
    public function __invoke(
        User $titular,
        #[SensitiveParameter] string $contenidoP12,
        string $nombreEnSello,
        ?string $cargoEnSello,
        ?User $administrador = null,
    ): CertificadoFirma {
        $this->validarEstructura($contenidoP12);

        $ruta = $this->boveda->guardar($titular, $contenidoP12);

        try {
            [$certificado, $esRenovacion] = DB::transaction(function () use ($titular, $ruta, $nombreEnSello, $cargoEnSello, $administrador): array {
                $this->descartarPendientes($titular);

                $activo = $titular->certificadoFirmaActivo()->first();

                $certificado = CertificadoFirma::query()->create([
                    'usuario_id' => $titular->getKey(),
                    'archivo' => $ruta,
                    'nombre_en_sello' => $nombreEnSello,
                    'cargo_en_sello' => $cargoEnSello,
                    'estado' => EstadoCertificadoFirma::PendienteActivacion,
                    'reemplaza_a_id' => $activo?->getKey(),
                    'cargado_por' => $administrador?->getKey(),
                ]);

                return [$certificado, $activo !== null];
            });
        } catch (Throwable $exception) {
            $this->boveda->eliminar($ruta);

            throw $exception;
        }

        $titular->notify(new FirmaActivacionPendienteNotification($esRenovacion));

        return $certificado;
    }

    /**
     * Con contraseña vacía el firmador distingue "no es un .p12" de "contraseña incorrecta".
     *
     * @throws FirmaDigitalException
     */
    private function validarEstructura(#[SensitiveParameter] string $contenidoP12): void
    {
        try {
            $this->firmador->inspeccionarCertificado($contenidoP12, '');
        } catch (FirmadorException $exception) {
            if ($exception->esContrasenaIncorrecta()) {
                return;
            }

            throw $exception->codigo === FirmadorException::NO_DISPONIBLE
                ? FirmaDigitalException::firmadorNoDisponible()
                : FirmaDigitalException::archivoInvalido();
        }
    }

    /**
     * Un archivo subido antes y nunca activado se reemplaza por el nuevo.
     */
    private function descartarPendientes(User $titular): void
    {
        $titular->certificadosFirma()
            ->where('estado', EstadoCertificadoFirma::PendienteActivacion)
            ->get()
            ->each(function (CertificadoFirma $pendiente): void {
                $archivo = $pendiente->archivo;

                $pendiente->update([
                    'estado' => EstadoCertificadoFirma::Revocado,
                    'archivo' => null,
                ]);

                DB::afterCommit(fn () => $this->boveda->eliminar($archivo));
            });
    }
}
