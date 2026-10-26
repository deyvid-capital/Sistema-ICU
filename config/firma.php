<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Servicio firmador
    |--------------------------------------------------------------------------
    |
    | Contenedor Python (docker/firmador) que firma los PDF con pyHanko. Solo
    | es accesible desde la red interna de Docker y exige un token compartido.
    | Ver: Docs/firma-digital/01-arquitectura-y-motor-de-firma.md
    |
    */

    'firmador' => [
        'url' => env('FIRMADOR_URL', 'http://firmador:8000'),
        'token' => env('FIRMADOR_TOKEN'),
        'timeout' => (int) env('FIRMADOR_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Clave de cifrado de los certificados .p12
    |--------------------------------------------------------------------------
    |
    | Distinta de APP_KEY para que rotar APP_KEY no deje inutilizables las
    | firmas. Se respalda por separado de storage/app/private.
    |
    */

    'encryption_key' => env('FIRMA_ENCRYPTION_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Derivación de la llave del titular (Argon2id)
    |--------------------------------------------------------------------------
    |
    | Costo de convertir la contraseña del sistema en la llave que abre la
    | contraseña del .p12. Los valores se guardan en cada envoltura, así que
    | subirlos más adelante no rompe las firmas ya activadas.
    |
    */

    'kdf' => [
        'operaciones' => (int) env('FIRMA_KDF_OPERACIONES', 3),
        'memoria' => (int) env('FIRMA_KDF_MEMORIA', 64 * 1024 * 1024),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sello visible
    |--------------------------------------------------------------------------
    |
    | Tamaño fijo del recuadro de firma en puntos PDF y logo del sello.
    |
    */

    'sello' => [
        'ancho' => 200,
        'alto' => 95,
        'logo' => public_path('images/Logo-Firma.png'),
        'zona_horaria' => 'America/La_Paz',
        'abreviatura_zona' => 'BOT',
    ],

];
