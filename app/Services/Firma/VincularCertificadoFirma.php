<?php

namespace App\Services\Firma;

use App\Enums\EstadoCertificadoFirma;
use App\Models\CertificadoFirma;
use App\Models\User;
use App\Support\Auditoria\AuditorSeguridad;
use Illuminate\Support\Facades\Hash;
use SensitiveParameter;

/**
 * "Contraseña única": envuelve la contraseña del .p12 en custodia con la contraseña
 * del sistema del titular. Ocurre sola al iniciar sesión o al firmar, tanto para una
 * firma recién activada por el admin como para una en pausa tras restablecer la
 * contraseña. No hace nada si no hay nada que vincular o la contraseña no es la actual.
 */
class VincularCertificadoFirma
{
    public function __construct(
        private BovedaCertificados $boveda,
        private EnvolturaContrasena $envoltura,
        private AuditorSeguridad $auditor,
    ) {}

    public function __invoke(User $usuario, #[SensitiveParameter] string $contrasenaSistema): ?CertificadoFirma
    {
        $certificado = $usuario->certificadosFirma()
            ->whereIn('estado', [EstadoCertificadoFirma::Activo, EstadoCertificadoFirma::RequiereReactivacion])
            ->whereNull('contrasena_p12_envuelta')
            ->whereNotNull('contrasena_p12_custodia')
            ->latest()
            ->first();

        if ($certificado === null || ! Hash::check($contrasenaSistema, (string) $usuario->password)) {
            return null;
        }

        $certificado->update([
            'estado' => EstadoCertificadoFirma::Activo,
            'contrasena_p12_envuelta' => $this->envoltura->envolver(
                $this->boveda->descifrar($certificado->contrasena_p12_custodia),
                $contrasenaSistema,
            ),
            'contrasena_usuario_huella' => $this->envoltura->huellaContrasenaUsuario((string) $usuario->password),
        ]);

        $this->auditor->firmaDigitalVinculada($usuario);

        return $certificado;
    }
}
