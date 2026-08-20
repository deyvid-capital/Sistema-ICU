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

];
