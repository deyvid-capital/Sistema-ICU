<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Disco de archivos
    |--------------------------------------------------------------------------
    |
    | Nombre del disco de Laravel (filesystems.php). Por defecto "public".
    | Para S3 u otro proveedor compatible: ARCHIVOS_DISK=s3 y las AWS_*.
    |
    */

    'disco' => env('ARCHIVOS_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Prefijo de rutas (Media Library)
    |--------------------------------------------------------------------------
    */

    'prefijo' => env('ARCHIVOS_PREFIJO', 'icu'),

    /*
    |--------------------------------------------------------------------------
    | Optimización de imágenes
    |--------------------------------------------------------------------------
    */

    'umbral_webp_kb' => (int) env('ARCHIVOS_UMBRAL_WEBP_KB', 500),

    'calidad_webp' => (int) env('ARCHIVOS_CALIDAD_WEBP', 82),

    'thumb_ancho' => (int) env('ARCHIVOS_THUMB_ANCHO', 400),

    /*
    |--------------------------------------------------------------------------
    | Límites de subida (KB)
    |--------------------------------------------------------------------------
    */

    'max_imagen_kb' => (int) env('ARCHIVOS_MAX_IMAGEN_KB', 5120),

    'max_pdf_kb' => (int) env('ARCHIVOS_MAX_PDF_KB', 10240),

    'max_logo_kb' => (int) env('ARCHIVOS_MAX_LOGO_KB', 2048),

    'max_favicon_kb' => (int) env('ARCHIVOS_MAX_FAVICON_KB', 512),

    /*
    |--------------------------------------------------------------------------
    | Visibilidad por defecto en el disco
    |--------------------------------------------------------------------------
    */

    'visibilidad' => env('ARCHIVOS_VISIBILIDAD', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Colecciones de imagen (Media Library)
    |--------------------------------------------------------------------------
    */

    'colecciones_imagen' => [
        'cap_antes' => [
            'modelo' => 'MantenimientoEquipo',
            'etiqueta' => 'Captura antes del mantenimiento',
            'mime' => ['image/png', 'image/jpeg'],
            'conversiones' => true,
        ],
        'cap_despues' => [
            'modelo' => 'MantenimientoEquipo',
            'etiqueta' => 'Captura después del mantenimiento',
            'mime' => ['image/png', 'image/jpeg'],
            'conversiones' => true,
        ],
        'logo_claro' => [
            'modelo' => 'AppearanceSetting',
            'etiqueta' => 'Logo modo claro',
            'mime' => ['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'],
            'conversiones' => true,
        ],
        'logo_oscuro' => [
            'modelo' => 'AppearanceSetting',
            'etiqueta' => 'Logo modo oscuro',
            'mime' => ['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'],
            'conversiones' => true,
        ],
        'favicon' => [
            'modelo' => 'AppearanceSetting',
            'etiqueta' => 'Favicon',
            'mime' => ['image/x-icon', 'image/vnd.microsoft.icon', 'image/png', 'image/svg+xml'],
            'conversiones' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Carpetas PDF (sin Media Library; solo metadata + FileUpload)
    |--------------------------------------------------------------------------
    */

    'pdf' => [
        'pdf_equipos' => [
            'etiqueta' => 'PDF de equipos',
            'directorio' => 'pdf-equipos',
        ],
        'pdf_celulares' => [
            'etiqueta' => 'PDF de celulares',
            'directorio' => 'pdf-celulares',
        ],
        'pdf_movimientos' => [
            'etiqueta' => 'PDF de movimientos',
            'directorio' => 'pdf-movimientos',
        ],
        'recibo_equipos' => [
            'etiqueta' => 'Recibos de equipo',
            'directorio' => 'recibo-equipos',
        ],
    ],

];
