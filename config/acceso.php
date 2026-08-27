<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Intentos de inicio de sesión fallidos
    |--------------------------------------------------------------------------
    |
    | Número de contraseñas incorrectas consecutivas (por cuenta) tras el cual
    | el usuario queda bloqueado. Un administrador debe desbloquearlo desde el
    | panel. Es independiente del rate-limit por IP de Filament.
    |
    */

    'max_intentos_fallidos' => (int) env('AUTH_MAX_INTENTOS_FALLIDOS', 5),

    /*
    |--------------------------------------------------------------------------
    | Recuperación de contraseña (código OTP)
    |--------------------------------------------------------------------------
    |
    | Código alfanumérico de un solo uso, con caducidad corta. Tras agotar los
    | intentos se bloquea el reenvío y la validación durante un periodo.
    | El alfabeto omite caracteres ambiguos (0/O, 1/I/L).
    |
    */

    'recuperacion' => [
        'codigo_ttl_minutos' => (int) env('AUTH_OTP_TTL_MINUTOS', 10),
        'max_intentos_codigo' => (int) env('AUTH_OTP_MAX_INTENTOS', 5),
        'bloqueo_minutos' => (int) env('AUTH_OTP_BLOQUEO_MINUTOS', 15),
        'reenvio_cooldown_segundos' => (int) env('AUTH_OTP_REENVIO_SEGUNDOS', 60),
        'longitud_codigo' => (int) env('AUTH_OTP_LONGITUD', 8),
        'alfabeto' => 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789',
    ],

];
