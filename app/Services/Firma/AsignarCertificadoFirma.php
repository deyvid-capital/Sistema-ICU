<?php

namespace App\Services\Firma;

use App\Exceptions\FirmaDigitalException;
use App\Models\CertificadoFirma;
use App\Models\User;
use App\Notifications\Firma\FirmaAsignadaNotification;
use App\Support\Auditoria\AuditorSeguridad;
use Illuminate\Support\Facades\DB;
use SensitiveParameter;
use Throwable;

/**
 * El admin sube el .p12 de un usuario con su contraseña (asignación inicial o
 * renovación anual) y la firma queda activa en ese momento. La firma anterior, si
 * la había, pasa a historial. El titular solo tiene que iniciar sesión para que la
 * firma quede vinculada con su contraseña del sistema.
 */
class AsignarCertificadoFirma
{
    public function __construct(
        private ActivarCertificadoFirma $activar,
        private BovedaCertificados $boveda,
        private AuditorSeguridad $auditor,
    ) {}

    /**
     * @throws FirmaDigitalException
     */
    public function __invoke(
        User $titular,
        #[SensitiveParameter] string $contenidoP12,
        #[SensitiveParameter] string $contrasenaP12,
        string $nombreEnSello,
        ?string $cargoEnSello,
        ?User $administrador = null,
    ): CertificadoFirma {
        $datos = $this->activar->validar($titular, $contenidoP12, $contrasenaP12);

        $ruta = $this->boveda->guardar($titular, $contenidoP12);

        try {
            [$certificado, $esRenovacion] = DB::transaction(function () use ($titular, $ruta, $nombreEnSello, $cargoEnSello, $administrador, $datos, $contrasenaP12): array {
                $activo = $titular->certificadoFirmaActivo()->first();

                $certificado = new CertificadoFirma([
                    'usuario_id' => $titular->getKey(),
                    'archivo' => $ruta,
                    'nombre_en_sello' => $nombreEnSello,
                    'cargo_en_sello' => $cargoEnSello,
                    'reemplaza_a_id' => $activo?->getKey(),
                    'cargado_por' => $administrador?->getKey(),
                ]);

                $this->activar->aplicar($certificado, $datos, $contrasenaP12);

                return [$certificado, $activo !== null];
            });
        } catch (Throwable $exception) {
            $this->boveda->eliminar($ruta);

            throw $exception;
        }

        $this->auditor->firmaDigitalActivada($titular, $administrador);

        $titular->notify(new FirmaAsignadaNotification($esRenovacion));

        return $certificado->refresh();
    }
}
