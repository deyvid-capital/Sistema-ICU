<?php

/**
 * Sobrescritura parcial de las traducciones del plugin de registro de actividad.
 *
 * Laravel fusiona este archivo sobre el del paquete con array_replace_recursive,
 * así que solo hace falta declarar los eventos propios: `created`, `updated`,
 * `deleted` y `restored` siguen viniendo del vendor.
 *
 * Las claves deben coincidir con los valores de App\Enums\EventoSeguridad;
 * EventoSeguridadTest lo verifica.
 */
return [
    'event' => [
        'ingreso_exitoso' => 'ingresó al sistema',
        'password_incorrecta' => 'contraseña incorrecta',
        'correo_desconocido' => 'correo desconocido',
        'acceso_denegado' => 'acceso denegado',
        'intento_cuenta_bloqueada' => 'intento con cuenta bloqueada',
        'cuenta_bloqueada' => 'cuenta bloqueada',
        'cierre_sesion' => 'cerró sesión',
        'otp_solicitado' => 'solicitó código de recuperación',
        'otp_incorrecto' => 'código de recuperación incorrecto',
        'otp_bloqueado' => 'recuperación bloqueada',
        'password_restablecida' => 'contraseña restablecida',
        'dos_factores_confirmado' => 'dos pasos confirmado',
        'dos_factores_codigo_incorrecto' => 'código de dos pasos incorrecto',
        'dos_factores_reseteado' => 'dos pasos reiniciado por un administrador',
        'firma_digital_activada' => 'firma digital activada',
        'firma_digital_contrasena_incorrecta' => 'contraseña del .p12 incorrecta',
        'firma_digital_suspendida' => 'firma digital suspendida',
        'firma_digital_intento_fallido' => 'contraseña incorrecta al firmar',
        'firma_digital_bloqueada' => 'firma digital bloqueada',
    ],
];
