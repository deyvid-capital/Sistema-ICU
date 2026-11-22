<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Temas Capital SAFI
    |--------------------------------------------------------------------------
    |
    | Cada tema declara solo lo que cambia sobre theme_base (lienzo + estados).
    | Al aplicar un tema se rellenan claro y oscuro a la vez.
    |
    */

    'theme_base' => [
        'light' => [
            'success' => '#13A538',
            'warning' => '#D97706',
            'danger' => '#E11D48',
            'info' => '#0081C1',
            'background' => '#F4F7FA',
            'surface' => '#FFFFFF',
            'input' => '#FFFFFF',
            'border' => '#D3DDE6',
            'text_color' => '#0F2437',
            'muted_text_color' => '#5B6B7A',
            // null = automático; al aplicar un tema se limpia un color a mano.
            'sidebar_icon_color' => null,
            'gray' => [
                50 => '#F8FAFC',
                100 => '#F1F5F9',
                200 => '#E2E8F0',
                300 => '#CBD5E1',
                400 => '#94A3B8',
                500 => '#64748B',
                600 => '#475569',
                700 => '#334155',
                800 => '#1E293B',
                900 => '#0F172A',
                950 => '#020617',
            ],
        ],
        'dark' => [
            'success' => '#13A538',
            'warning' => '#D97706',
            'danger' => '#E11D48',
            'info' => '#0081C1',
            'background' => '#0B1620',
            'surface' => '#122130',
            'input' => '#0F1C29',
            'border' => '#22384A',
            'text_color' => '#E8EEF3',
            'muted_text_color' => '#93A4B3',
            'sidebar_icon_color' => null,
            'gray' => [
                50 => '#F8FAFC',
                100 => '#F1F5F9',
                200 => '#E2E8F0',
                300 => '#CBD5E1',
                400 => '#94A3B8',
                500 => '#64748B',
                600 => '#475569',
                700 => '#334155',
                800 => '#1E293B',
                900 => '#0F172A',
                950 => '#020617',
            ],
        ],
    ],

    'themes' => [
        'safi' => [
            'label' => 'Corporativo SAFI',
            'description' => 'Azul institucional con acentos en Azul Cielo.',
            'light' => [
                'primary' => '#003973',
                'sidebar_active_color' => '#003973',
            ],
            'dark' => [
                'primary' => '#0081C1',
                'sidebar_active_color' => '#48B9B2',
            ],
        ],
        'confianza' => [
            'label' => 'Azul Confianza',
            'description' => 'Sobrio, con Azul Confianza en claro y Azul Cielo en oscuro.',
            'light' => [
                'primary' => '#006B96',
                'sidebar_active_color' => '#006B96',
            ],
            'dark' => [
                'primary' => '#0081C1',
                'sidebar_active_color' => '#42A6AC',
            ],
        ],
        'cielo' => [
            'label' => 'Azul Cielo',
            'description' => 'Más luminoso, con Azul Cielo en ambos modos.',
            'light' => [
                'primary' => '#0081C1',
                'sidebar_active_color' => '#006B96',
            ],
            'dark' => [
                'primary' => '#0081C1',
                'sidebar_active_color' => '#0081C1',
            ],
        ],
        'turquesa' => [
            'label' => 'Turquesa Lealtad',
            'description' => 'Acentos turquesa de la marca.',
            'light' => [
                'primary' => '#008A83',
                'sidebar_active_color' => '#008A83',
            ],
            'dark' => [
                'primary' => '#48B9B2',
                'sidebar_active_color' => '#48B9B2',
            ],
        ],
        'naturaleza' => [
            'label' => 'Verde Naturaleza',
            'description' => 'Verde institucional y Verde Próspero en oscuro.',
            'light' => [
                'primary' => '#13A538',
                // Un tono más oscuro cumple AA 3:1 sobre el lienzo claro.
                'sidebar_active_color' => '#0E7A2A',
            ],
            'dark' => [
                'primary' => '#9AC31C',
                'sidebar_active_color' => '#9AC31C',
            ],
        ],
        'neutro' => [
            'label' => 'Neutro SAFI',
            'description' => 'Grises a partir de Gris SAFI, con Azul SAFI como acento.',
            'light' => [
                'primary' => '#003973',
                'background' => '#F6F6F7',
                'border' => '#D8D8DA',
                'sidebar_active_color' => '#003973',
                'gray' => [
                    50 => '#FAFAFA',
                    100 => '#F4F4F5',
                    200 => '#E4E4E7',
                    300 => '#D4D4D8',
                    400 => '#A1A1AA',
                    500 => '#949494',
                    600 => '#71717A',
                    700 => '#52525B',
                    800 => '#3F3F46',
                    900 => '#27272A',
                    950 => '#18181B',
                ],
            ],
            'dark' => [
                'primary' => '#0081C1',
                'background' => '#121416',
                'surface' => '#1A1D21',
                'input' => '#16191D',
                'border' => '#2A2F36',
                'sidebar_active_color' => '#0081C1',
                'gray' => [
                    50 => '#FAFAFA',
                    100 => '#F4F4F5',
                    200 => '#E4E4E7',
                    300 => '#D4D4D8',
                    400 => '#A1A1AA',
                    500 => '#949494',
                    600 => '#71717A',
                    700 => '#52525B',
                    800 => '#3F3F46',
                    900 => '#27272A',
                    950 => '#18181B',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fonts
    |--------------------------------------------------------------------------
    |
    | Font families available in the editor. Lato is embedded; the rest load
    | from Google Fonts when chosen.
    |
    */

    'fonts' => [
        // Lato se sirve desde el propio paquete (ver local_fonts); el resto se
        // descarga de Google Fonts, así que requiere internet y CSP que lo permita.
        'latin' => [
            'Lato' => 'Lato (local, sin internet)',
            'Inter' => 'Inter',
            'Geist' => 'Geist',
            'Plus Jakarta Sans' => 'Plus Jakarta Sans',
            'Manrope' => 'Manrope',
            'DM Sans' => 'DM Sans',
            'Figtree' => 'Figtree',
            'Outfit' => 'Outfit',
            'Poppins' => 'Poppins',
            'Lexend' => 'Lexend',
            'Nunito Sans' => 'Nunito Sans',
            'Roboto' => 'Roboto',
            'Open Sans' => 'Open Sans',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Font Sizes
    |--------------------------------------------------------------------------
    |
    | Applied as the root (html) font size. Filament sizes everything in rem,
    | so the whole interface scales together.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Fuentes locales
    |--------------------------------------------------------------------------
    |
    | Familias que el paquete incluye embebidas (resources/dist/apariencia-fonts.css)
    | y que por tanto no se piden a Google Fonts.
    |
    */

    'local_fonts' => ['Lato'],

    'font_sizes' => [
        'sm' => ['label' => 'Small / Compact', 'size' => '93.75%'],
        'md' => ['label' => 'Medium / Standard', 'size' => '100%'],
        'lg' => ['label' => 'Large / Enhanced Readability', 'size' => '106.25%'],
        'xl' => ['label' => 'Extra Large / High Accessibility', 'size' => '112.5%'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Density & Corner Radius
    |--------------------------------------------------------------------------
    |
    | Density overrides Tailwind's --spacing variable; radius overrides the
    | --radius-* variables used by Filament's compiled theme.
    |
    */

    'densities' => [
        'compact' => ['label' => 'Compact', 'spacing' => '0.225rem'],
        'comfortable' => ['label' => 'Comfortable', 'spacing' => '0.25rem'],
        'spacious' => ['label' => 'Spacious', 'spacing' => '0.275rem'],
    ],

    'radii' => [
        'none' => ['label' => 'Square', 'sm' => '0', 'md' => '0', 'lg' => '0', 'xl' => '0'],
        'sm' => ['label' => 'Subtle', 'sm' => '0.125rem', 'md' => '0.1875rem', 'lg' => '0.25rem', 'xl' => '0.375rem'],
        'md' => ['label' => 'Standard', 'sm' => '0.25rem', 'md' => '0.375rem', 'lg' => '0.5rem', 'xl' => '0.75rem'],
        'lg' => ['label' => 'Rounded', 'sm' => '0.375rem', 'md' => '0.5625rem', 'lg' => '0.75rem', 'xl' => '1.125rem'],
        'xl' => ['label' => 'Extra Rounded', 'sm' => '0.5rem', 'md' => '0.75rem', 'lg' => '1rem', 'xl' => '1.5rem'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Dark Mode
    |--------------------------------------------------------------------------
    */

    'dark_modes' => [
        'toggle' => 'Let users switch',
        'light' => 'Light only',
        'dark' => 'Dark only',
    ],

    'default_theme_modes' => [
        'system' => 'Follow system',
        'light' => 'Light',
        'dark' => 'Dark',
    ],

    /*
    |--------------------------------------------------------------------------
    | Skins, Sidebar & Backdrop
    |--------------------------------------------------------------------------
    |
    | Skins are pre-built styles in resources/dist/apariencia.css, scoped
    | under html[data-studio-skin]. They switch at runtime without a rebuild.
    |
    */

    'skins' => [
        'classic' => 'Classic',
        'soft' => 'Soft',
    ],

    'sidebar_styles' => [
        'classic' => 'Classic',
        'collapsible' => 'Collapsible to icons',
    ],

    'backdrops' => [
        'none' => 'Plain color',
        'image' => 'Image',
    ],

    // Disk and folder for uploaded backdrop images.
    'backdrop_disk' => 'public',

    'backdrop_directory' => 'apariencia/fondos',

    /*
    |--------------------------------------------------------------------------
    | Marca: favicon
    |--------------------------------------------------------------------------
    |
    | Al reemplazar o quitar el favicon (o una imagen de fondo) el archivo
    | anterior se borra del disco, salvo que lo siga usando otro ajuste, un
    | perfil guardado o la apariencia personal de algún usuario.
    |
    */

    'brand_disk' => 'public',

    'brand_directory' => 'apariencia/marca',

    /*
    |--------------------------------------------------------------------------
    | Imágenes (ruta segura)
    |--------------------------------------------------------------------------
    |
    | Sin SVG ni ICO (D6/D7). El tipo se decide por contenido; la salida se
    | recodifica con spatie/image. driver: gd | imagick | null (autodetecta).
    |
    */

    'images' => [
        'driver' => null,
        'profiles' => [
            'logo' => [
                'max_kb' => 2048,
                'max_megapixels' => 16,
                'mime_types' => ['image/png', 'image/jpeg', 'image/webp'],
                'min_width' => 1,
                'min_height' => 1,
                'max_width' => 4096,
                'max_height' => 4096,
                'max_edge' => 1024,
                'quality' => 90,
                'output' => 'webp',
            ],
            'favicon' => [
                'max_kb' => 1024,
                'max_megapixels' => 4,
                'mime_types' => ['image/png', 'image/webp'],
                'min_width' => 180,
                'min_height' => 180,
                'max_width' => 2048,
                'max_height' => 2048,
                'square' => true,
                'square_tolerance' => 0.05,
                'output' => 'png',
            ],
            'backdrop' => [
                'max_kb' => 4096,
                'max_megapixels' => 24,
                'mime_types' => ['image/png', 'image/jpeg', 'image/webp'],
                'min_width' => 1,
                'min_height' => 1,
                'max_width' => 8000,
                'max_height' => 8000,
                'max_edge' => 2560,
                'quality' => 82,
                'output' => 'webp',
            ],
        ],
    ],

    // Compatibilidad con helpers antiguos del editor (límites del perfil favicon).
    'favicon' => [
        'max_kb' => 1024,
        'mime_types' => ['image/png', 'image/webp'],
    ],

    'backdrop_image' => [
        'max_kb' => 4096,
        'mime_types' => ['image/png', 'image/jpeg', 'image/webp'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Personal Appearance
    |--------------------------------------------------------------------------
    |
    | The admin's settings are everyone's default. When allow_personal is true,
    | users can override these keys on the "My appearance" page.
    |
    */

    'user_keys' => [
        'skin', 'font_size',
        'text_color', 'muted_text_color', 'dark_text_color', 'dark_muted_text_color',
        'primary', 'background', 'surface', 'input', 'border',
        'dark_primary', 'dark_background', 'dark_surface', 'dark_input', 'dark_border',
        'backdrop', 'dark_skin', 'dark_backdrop',
    ],

    // The colors that are set separately for each theme mode.
    'theme_keys' => [
        'light' => ['sidebar_icon_color', 'sidebar_active_color', 'text_color', 'muted_text_color', 'skin', 'backdrop', 'backdrop_image', 'success', 'warning', 'danger', 'info', 'gray_palette', 'primary', 'background', 'surface', 'input', 'border'],
        'dark' => ['dark_sidebar_icon_color', 'dark_sidebar_active_color', 'dark_text_color', 'dark_muted_text_color', 'dark_skin', 'dark_backdrop', 'dark_backdrop_image', 'dark_success', 'dark_warning', 'dark_danger', 'dark_info', 'dark_gray_palette', 'dark_primary', 'dark_background', 'dark_surface', 'dark_input', 'dark_border'],
    ],

    'profiles' => [
        'max_per_user' => 20,
        'name_max_length' => 40,
    ],

    /*
    |--------------------------------------------------------------------------
    | Defaults
    |--------------------------------------------------------------------------
    |
    | Used by the settings migration and as the fallback for any missing or
    | invalid stored value.
    |
    */

    'defaults' => [
        'text_color' => '#0F2437',
        'muted_text_color' => '#5B6B7A',
        'dark_text_color' => '#E8EEF3',
        'dark_muted_text_color' => '#93A4B3',
        'theme' => 'safi',
        'primary' => '#003973',
        'success' => '#13A538',
        'warning' => '#D97706',
        'danger' => '#E11D48',
        'info' => '#0081C1',
        'gray_palette' => [
            50 => '#F8FAFC',
            100 => '#F1F5F9',
            200 => '#E2E8F0',
            300 => '#CBD5E1',
            400 => '#94A3B8',
            500 => '#64748B',
            600 => '#475569',
            700 => '#334155',
            800 => '#1E293B',
            900 => '#0F172A',
            950 => '#020617',
        ],
        'background' => '#F4F7FA',
        'surface' => '#FFFFFF',
        'input' => '#FFFFFF',
        'border' => '#D3DDE6',
        'dark_background' => '#0B1620',
        'dark_surface' => '#122130',
        'dark_input' => '#0F1C29',
        'dark_border' => '#22384A',
        'font_latin' => 'Lato',
        'font_size' => 'md',
        'density' => 'comfortable',
        'radius' => 'md',
        'dark_mode' => 'toggle',
        'default_theme_mode' => 'system',
        'skin' => 'classic',
        'sidebar_style' => 'classic',
        'backdrop' => 'none',
        'backdrop_image' => null,
        'dark_primary' => '#0081C1',
        'allow_personal' => true,
        'dark_skin' => 'classic',
        'dark_backdrop' => 'none',
        'dark_backdrop_image' => null,
        'dark_success' => '#13A538',
        'dark_warning' => '#D97706',
        'dark_danger' => '#E11D48',
        'dark_info' => '#0081C1',
        'dark_gray_palette' => [
            50 => '#F8FAFC',
            100 => '#F1F5F9',
            200 => '#E2E8F0',
            300 => '#CBD5E1',
            400 => '#94A3B8',
            500 => '#64748B',
            600 => '#475569',
            700 => '#334155',
            800 => '#1E293B',
            900 => '#0F172A',
            950 => '#020617',
        ],

        // Capital SAFI: barra superior, marca y colores del sidebar. Los
        // colores del sidebar en null siguen a los del tema (gris y primario).
        'topbar' => false,
        'logo_light' => null,
        'logo_dark' => null,
        'logo_height' => '2.5rem',
        'logo_plate' => 'auto',
        'favicon' => null,
        'favicon_touch' => null,
        'sidebar_icon_color' => null,
        'dark_sidebar_icon_color' => null,
        'sidebar_active_color' => '#003973',
        'dark_sidebar_active_color' => '#48B9B2',
    ],

];
