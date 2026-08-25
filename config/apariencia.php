<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tema por defecto
    |--------------------------------------------------------------------------
    |
    | Clave del tema que se aplica cuando todavía no se ha guardado ninguna
    | configuración en la base de datos (o si esta no puede leerse).
    |
    */

    'tema_por_defecto' => 'safi',

    /*
    |--------------------------------------------------------------------------
    | Temas disponibles
    |--------------------------------------------------------------------------
    |
    | Cada entrada aparece como opción en la página de Apariencia. Los tonos
    | claros usan la paleta corporativa de Capital SAFI tal cual; los oscuros
    | son variantes seleccionadas para combinar con ella.
    |
    | 'gris' acepta el nombre de una paleta de Filament\Support\Colors\Color
    | (Slate, Zinc, Gray, Neutral, Stone...) o un color hexadecimal.
    |
    */

    'temas' => [

        'safi' => [
            'etiqueta' => 'Corporativo SAFI',
            'descripcion' => 'Sidebar en Azul SAFI y barra superior blanca.',
            'primario' => '#003973',
            'gris' => 'Slate',
            'sidebar' => ['claro' => '#003973', 'oscuro' => '#041E36'],
            'topbar' => ['claro' => '#FFFFFF', 'oscuro' => '#071B2B'],
        ],

        'confianza' => [
            'etiqueta' => 'Azul Confianza',
            'descripcion' => 'Barra superior azul institucional con sidebar claro.',
            'primario' => '#0081C1',
            'gris' => 'Slate',
            'sidebar' => ['claro' => '#FFFFFF', 'oscuro' => '#0E1A22'],
            'topbar' => ['claro' => '#006B96', 'oscuro' => '#0A2F42'],
        ],

        'turquesa' => [
            'etiqueta' => 'Turquesa Lealtad',
            'descripcion' => 'Sidebar Azul Confianza con acentos turquesa.',
            'primario' => '#008A83',
            'gris' => 'Slate',
            'sidebar' => ['claro' => '#006B96', 'oscuro' => '#062A38'],
            'topbar' => ['claro' => '#FFFFFF', 'oscuro' => '#08202B'],
        ],

        'neutro' => [
            'etiqueta' => 'Neutro Claro',
            'descripcion' => 'Barras blancas y acentos en Azul SAFI.',
            'primario' => '#003973',
            'gris' => '#949494',
            'sidebar' => ['claro' => '#FFFFFF', 'oscuro' => '#17191C'],
            'topbar' => ['claro' => '#FFFFFF', 'oscuro' => '#17191C'],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Colores semánticos
    |--------------------------------------------------------------------------
    |
    | Compartidos por todos los temas. Se registran junto al color primario y
    | al gris para que badges, notificaciones y botones usen la paleta de marca.
    |
    */

    'semanticos' => [
        'info' => '#0081C1',
        'success' => '#13A538',
        'warning' => 'Amber',
        'danger' => 'Rose',
    ],

    /*
    |--------------------------------------------------------------------------
    | Paleta corporativa Capital SAFI
    |--------------------------------------------------------------------------
    |
    | Se muestra como referencia (muestras con su hexadecimal) en la sección de
    | colores personalizados, para que se elijan colores dentro de la marca.
    |
    */

    'paleta_marca' => [
        ['nombre' => 'Azul SAFI', 'hex' => '#003973'],
        ['nombre' => 'Azul Cielo', 'hex' => '#0081C1'],
        ['nombre' => 'Azul Confianza', 'hex' => '#006B96'],
        ['nombre' => 'Turquesa Lealtad', 'hex' => '#008A83'],
        ['nombre' => 'Turquesa Respeto', 'hex' => '#48B9B2'],
        ['nombre' => 'Turquesa Integridad', 'hex' => '#42A6AC'],
        ['nombre' => 'Verde Naturaleza', 'hex' => '#13A538'],
        ['nombre' => 'Verde Próspero', 'hex' => '#9AC31C'],
        ['nombre' => 'Gris SAFI', 'hex' => '#949494'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Paletas de gris seleccionables
    |--------------------------------------------------------------------------
    |
    | El gris define fondos, bordes y textos de toda la interfaz, no solo de
    | las barras, por eso se limita a un conjunto validado.
    |
    */

    'grises_disponibles' => [
        'Slate' => 'Slate (gris azulado, recomendado)',
        'Zinc' => 'Zinc (gris neutro)',
        'Gray' => 'Gray (gris frío)',
        'Neutral' => 'Neutral (gris puro)',
        'Stone' => 'Stone (gris cálido)',
        '#949494' => 'Gris SAFI (#949494)',
    ],

    /*
    |--------------------------------------------------------------------------
    | Archivos de marca
    |--------------------------------------------------------------------------
    */

    'logo' => [
        'disco' => 'public',
        'directorio' => 'apariencia',
        'max_kb' => 2048,
        'alto_por_defecto' => '2.5rem',
        'fallback_claro' => 'images/Capital-Safi-SA-logo-.png',
    ],

    'favicon' => [
        'max_kb' => 512,
        'fallback' => 'favicon.ico',
    ],

];
