<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

/**
 * Vocabulario cerrado de eventos de autenticación y seguridad. El valor se
 * guarda en `activity_log.event` y la etiqueta en `description`, de modo que el
 * registro general se lee en español sin traducciones adicionales.
 *
 * Al añadir un caso hay que traducirlo en `lang/vendor/activitylog/es/action.php`;
 * EventoSeguridadTest lo verifica.
 */
enum EventoSeguridad: string implements HasColor, HasLabel
{
    case IngresoExitoso = 'ingreso_exitoso';
    case PasswordIncorrecta = 'password_incorrecta';
    case CorreoDesconocido = 'correo_desconocido';
    case AccesoDenegado = 'acceso_denegado';
    case IntentoConCuentaBloqueada = 'intento_cuenta_bloqueada';
    case CuentaBloqueada = 'cuenta_bloqueada';
    case CierreSesion = 'cierre_sesion';
    case OtpSolicitado = 'otp_solicitado';
    case OtpIncorrecto = 'otp_incorrecto';
    case OtpBloqueado = 'otp_bloqueado';
    case PasswordRestablecida = 'password_restablecida';
    case DosFactoresConfirmado = 'dos_factores_confirmado';
    case DosFactoresCodigoIncorrecto = 'dos_factores_codigo_incorrecto';
    case DosFactoresReseteado = 'dos_factores_reseteado';

    public function getLabel(): string
    {
        return match ($this) {
            self::IngresoExitoso => 'Ingresó al sistema',
            self::PasswordIncorrecta => 'Contraseña incorrecta',
            self::CorreoDesconocido => 'Intento con correo desconocido',
            self::AccesoDenegado => 'Acceso denegado al panel',
            self::IntentoConCuentaBloqueada => 'Intento con cuenta bloqueada',
            self::CuentaBloqueada => 'Cuenta bloqueada por intentos fallidos',
            self::CierreSesion => 'Cerró sesión',
            self::OtpSolicitado => 'Solicitó código de recuperación',
            self::OtpIncorrecto => 'Código de recuperación incorrecto',
            self::OtpBloqueado => 'Recuperación bloqueada por intentos fallidos',
            self::PasswordRestablecida => 'Restableció su contraseña',
            self::DosFactoresConfirmado => 'Confirmó la verificación en dos pasos',
            self::DosFactoresCodigoIncorrecto => 'Código de dos pasos incorrecto',
            self::DosFactoresReseteado => 'Verificación en dos pasos reiniciada por un administrador',
        };
    }

    public function getColor(): string|array|null
    {
        return $this->resultado()->getColor();
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::IngresoExitoso => Heroicon::OutlinedArrowRightOnRectangle,
            self::PasswordIncorrecta => Heroicon::OutlinedExclamationTriangle,
            self::CorreoDesconocido => Heroicon::OutlinedQuestionMarkCircle,
            self::AccesoDenegado => Heroicon::OutlinedNoSymbol,
            self::IntentoConCuentaBloqueada, self::CuentaBloqueada, self::OtpBloqueado => Heroicon::OutlinedLockClosed,
            self::CierreSesion => Heroicon::OutlinedArrowLeftOnRectangle,
            self::OtpSolicitado => Heroicon::OutlinedEnvelope,
            self::OtpIncorrecto => Heroicon::OutlinedExclamationTriangle,
            self::PasswordRestablecida => Heroicon::OutlinedKey,
            self::DosFactoresConfirmado => Heroicon::OutlinedShieldCheck,
            self::DosFactoresCodigoIncorrecto => Heroicon::OutlinedShieldExclamation,
            self::DosFactoresReseteado => Heroicon::OutlinedArrowPath,
        };
    }

    public function resultado(): ResultadoSeguridad
    {
        return match ($this) {
            self::IngresoExitoso,
            self::CierreSesion,
            self::OtpSolicitado,
            self::PasswordRestablecida,
            self::DosFactoresConfirmado => ResultadoSeguridad::Exito,

            self::PasswordIncorrecta,
            self::CorreoDesconocido,
            self::AccesoDenegado,
            self::OtpIncorrecto,
            self::DosFactoresCodigoIncorrecto => ResultadoSeguridad::Fallo,

            self::IntentoConCuentaBloqueada,
            self::CuentaBloqueada,
            self::OtpBloqueado,
            self::DosFactoresReseteado => ResultadoSeguridad::Bloqueo,
        };
    }

    /**
     * @return array<string, string>
     */
    public static function opciones(): array
    {
        $opciones = [];

        foreach (self::cases() as $evento) {
            $opciones[$evento->value] = $evento->getLabel();
        }

        return $opciones;
    }

    /**
     * @return list<string>
     */
    public static function valoresPorResultado(ResultadoSeguridad $resultado): array
    {
        return array_values(array_map(
            fn (self $evento): string => $evento->value,
            array_filter(self::cases(), fn (self $evento): bool => $evento->resultado() === $resultado),
        ));
    }
}
