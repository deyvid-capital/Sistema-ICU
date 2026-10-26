<?php

namespace App\Services\Firma;

use App\Exceptions\FirmaDigitalException;
use App\Models\User;
use SensitiveParameter;

/**
 * Cambio de contraseña desde Mi perfil: se conocen la anterior y la nueva, así que
 * la contraseña del .p12 se vuelve a envolver y la firma sigue activa.
 *
 * Debe ejecutarse en la misma transacción que guarda el nuevo hash, y ANTES de
 * guardarlo: la huella actualizada evita que UserObserver suspenda la firma.
 */
class RecifrarCertificadoFirma
{
    public function __construct(private EnvolturaContrasena $envoltura) {}

    public function __invoke(
        User $usuario,
        #[SensitiveParameter] string $contrasenaActual,
        #[SensitiveParameter] string $contrasenaNueva,
        string $hashNuevo,
    ): void {
        $certificado = $usuario->certificadoFirmaActivo()->first();

        if ($certificado === null || blank($certificado->contrasena_p12_envuelta)) {
            return;
        }

        try {
            $contrasenaP12 = $this->envoltura->desenvolver($certificado->contrasena_p12_envuelta, $contrasenaActual);
        } catch (FirmaDigitalException) {
            // La envoltura ya no abría: UserObserver suspenderá la firma al guardar.
            return;
        }

        $certificado->update([
            'contrasena_p12_envuelta' => $this->envoltura->envolver($contrasenaP12, $contrasenaNueva),
            'contrasena_usuario_huella' => $this->envoltura->huellaContrasenaUsuario($hashNuevo),
        ]);
    }
}
