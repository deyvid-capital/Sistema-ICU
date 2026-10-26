<?php

namespace App\Services\Firma;

use App\Enums\EstadoCertificadoFirma;
use App\Models\User;
use App\Notifications\Firma\FirmaRequiereReactivacionNotification;
use App\Support\Auditoria\AuditorSeguridad;
use App\Support\Notifications\AdminRecipients;
use Illuminate\Support\Facades\Notification;

/**
 * La contraseña del sistema cambió sin pasar por Mi perfil (admin, recuperación por
 * correo, importación): la contraseña del .p12 ya no se puede abrir y la firma pasa
 * a "requiere reactivación". Se avisa al titular y a los administradores.
 *
 * Ver Docs/firma-digital/03-seguridad.md#cambios-de-contraseña-del-sistema
 */
class SuspenderCertificadoFirma
{
    public function __construct(
        private EnvolturaContrasena $envoltura,
        private AuditorSeguridad $auditor,
    ) {}

    public function trasCambioDeContrasena(User $usuario): void
    {
        $certificado = $usuario->certificadoFirmaActivo()->first();

        if ($certificado === null || blank($certificado->contrasena_p12_envuelta)) {
            return;
        }

        $huellaActual = $this->envoltura->huellaContrasenaUsuario((string) $usuario->password);

        if (hash_equals((string) $certificado->contrasena_usuario_huella, $huellaActual)) {
            return;
        }

        $actor = auth()->user();
        $actor = $actor instanceof User ? $actor : null;
        $motivo = $this->motivo($usuario, $actor);

        $certificado->update([
            'estado' => EstadoCertificadoFirma::RequiereReactivacion,
            'contrasena_p12_envuelta' => null,
            'contrasena_usuario_huella' => null,
        ]);

        $this->auditor->firmaDigitalSuspendida($usuario, $motivo, $actor);

        $usuario->notify(FirmaRequiereReactivacionNotification::paraTitular($motivo));

        Notification::send(
            AdminRecipients::all($usuario),
            FirmaRequiereReactivacionNotification::paraAdministrador($usuario, $motivo),
        );
    }

    private function motivo(User $usuario, ?User $actor): string
    {
        return match (true) {
            $actor === null => 'recuperación de contraseña por correo o proceso automático (importación)',
            $actor->is($usuario) => 'cambio de contraseña fuera de Mi perfil',
            default => "restablecida por el administrador {$actor->name}",
        };
    }
}
