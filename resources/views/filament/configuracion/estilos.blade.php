{{--
    Recolorea el sidebar y la barra superior con los valores guardados en la
    página Tema y colores. Se inyecta en el render hook `panels::styles.after`,
    por lo que gana a los estilos de Filament sin recompilar assets.

    Todo el color legible (texto, iconos, hover, ítem activo) se deriva de los
    tokens --icu-*-fg calculados en TemaService con la API de contraste de
    Filament, de modo que un fondo oscuro nunca deja el menú ilegible.
--}}
<style>
    :root {
        --icu-sidebar-bg: {{ $claro['sidebar']['bg'] }};
        --icu-sidebar-fg: {{ $claro['sidebar']['fg'] }};
        --icu-sidebar-activo: {{ $claro['sidebar']['activo'] }};
        --icu-topbar-bg: {{ $claro['topbar']['bg'] }};
        --icu-topbar-fg: {{ $claro['topbar']['fg'] }};
    }

    .dark {
        --icu-sidebar-bg: {{ $oscuro['sidebar']['bg'] }};
        --icu-sidebar-fg: {{ $oscuro['sidebar']['fg'] }};
        --icu-sidebar-activo: {{ $oscuro['sidebar']['activo'] }};
        --icu-topbar-bg: {{ $oscuro['topbar']['bg'] }};
        --icu-topbar-fg: {{ $oscuro['topbar']['fg'] }};
    }

    /* Sidebar: el fondo se repite dentro del media query porque desde 64rem
       Filament lo deja transparente y se vería el fondo del cuerpo. */
    .fi-sidebar,
    .fi-sidebar:where(.dark, .dark *) {
        background-color: var(--icu-sidebar-bg);
        color: var(--icu-sidebar-fg);
    }

    @media (min-width: 64rem) {
        .fi-sidebar,
        .fi-sidebar:where(.dark, .dark *) {
            background-color: var(--icu-sidebar-bg);
        }
    }

    /* Todas las reglas de ítems van acotadas a `.fi-sidebar`: la sub-navegación
       lateral de una página reutiliza estas mismas clases fuera del sidebar
       (page/sub-navigation/sidebar.blade.php) y heredaría un texto ilegible. */
    .fi-sidebar .fi-sidebar-item-label,
    .fi-sidebar .fi-sidebar-item-label:where(.dark, .dark *),
    .fi-sidebar .fi-user-menu-trigger-text,
    .fi-sidebar .fi-sidebar-database-notifications-btn > .fi-sidebar-database-notifications-btn-label,
    .fi-sidebar .fi-sidebar-database-notifications-btn > .fi-sidebar-database-notifications-btn-label:where(.dark, .dark *) {
        color: var(--icu-sidebar-fg);
    }

    .fi-sidebar .fi-sidebar-group-label,
    .fi-sidebar .fi-sidebar-group-label:where(.dark, .dark *) {
        color: color-mix(in oklab, var(--icu-sidebar-fg) 62%, transparent);
    }

    .fi-sidebar .fi-sidebar-item-btn > .fi-icon,
    .fi-sidebar .fi-sidebar-item-btn > .fi-icon:where(.dark, .dark *),
    .fi-sidebar .fi-user-menu-trigger .fi-icon,
    .fi-sidebar .fi-user-menu-trigger .fi-icon:where(.dark, .dark *),
    .fi-sidebar .fi-sidebar-database-notifications-btn > .fi-icon,
    .fi-sidebar .fi-sidebar-database-notifications-btn > .fi-icon:where(.dark, .dark *),
    .fi-sidebar-header .fi-icon-btn,
    .fi-sidebar-header .fi-icon-btn:where(.dark, .dark *) {
        color: color-mix(in oklab, var(--icu-sidebar-fg) 72%, transparent);
    }

    .fi-sidebar .fi-sidebar-item:not(.fi-active) > .fi-sidebar-item-btn:hover,
    .fi-sidebar .fi-user-menu-trigger:hover,
    .fi-sidebar .fi-sidebar-group-dropdown-trigger-btn:hover,
    .fi-sidebar .fi-sidebar-database-notifications-btn:hover,
    .fi-sidebar .fi-sidebar-database-notifications-btn:focus-visible,
    .fi-sidebar .fi-sidebar-database-notifications-btn:where(.dark, .dark *):hover,
    .fi-sidebar .fi-sidebar-database-notifications-btn:where(.dark, .dark *):focus-visible {
        background-color: color-mix(in oklab, var(--icu-sidebar-fg) 8%, transparent);
    }

    .fi-sidebar .fi-sidebar-item.fi-active > .fi-sidebar-item-btn,
    .fi-sidebar .fi-sidebar-item.fi-active > .fi-sidebar-item-btn:where(.dark, .dark *) {
        background-color: color-mix(in oklab, var(--icu-sidebar-fg) 13%, transparent);
    }

    .fi-sidebar .fi-sidebar-item.fi-active > .fi-sidebar-item-btn > .fi-icon,
    .fi-sidebar .fi-sidebar-item.fi-active > .fi-sidebar-item-btn > .fi-icon:where(.dark, .dark *),
    .fi-sidebar .fi-sidebar-item.fi-active > .fi-sidebar-item-btn > .fi-sidebar-item-label,
    .fi-sidebar .fi-sidebar-item.fi-active > .fi-sidebar-item-btn > .fi-sidebar-item-label:where(.dark, .dark *),
    .fi-sidebar .fi-sidebar-group.fi-active .fi-sidebar-group-dropdown-trigger-btn .fi-icon {
        color: var(--icu-sidebar-activo);
    }

    /* Líneas de los ítems agrupados: derivadas del texto para no perderse
       sobre fondos oscuros. */
    .fi-sidebar .fi-sidebar-item-grouped-border-part,
    .fi-sidebar .fi-sidebar-item-grouped-border-part-not-first,
    .fi-sidebar .fi-sidebar-item-grouped-border-part-not-last {
        background-color: color-mix(in oklab, var(--icu-sidebar-fg) 35%, transparent);
    }

    /* Barra superior */
    .fi-topbar,
    .fi-topbar:where(.dark, .dark *) {
        background-color: var(--icu-topbar-bg);
        color: var(--icu-topbar-fg);
    }

    .fi-topbar .fi-topbar-item-label,
    .fi-topbar .fi-topbar-item-label:where(.dark, .dark *) {
        color: var(--icu-topbar-fg);
    }

    .fi-topbar .fi-icon-btn,
    .fi-topbar .fi-icon-btn:where(.dark, .dark *),
    .fi-topbar .fi-topbar-item-btn > .fi-icon,
    .fi-topbar .fi-topbar-item-btn > .fi-icon:where(.dark, .dark *),
    .fi-topbar .fi-input-wrp-icon {
        color: color-mix(in oklab, var(--icu-topbar-fg) 72%, transparent);
    }

    .fi-topbar .fi-icon-btn:hover,
    .fi-topbar .fi-topbar-item-btn:hover {
        background-color: color-mix(in oklab, var(--icu-topbar-fg) 8%, transparent);
    }

    /* Búsqueda global: vive dentro de la barra, así que sigue su color. */
    .fi-topbar .fi-input-wrp,
    .fi-topbar .fi-input-wrp:where(.dark, .dark *) {
        background-color: color-mix(in oklab, var(--icu-topbar-fg) 8%, transparent);
        --tw-ring-color: color-mix(in oklab, var(--icu-topbar-fg) 18%, transparent);
    }

    .fi-topbar .fi-input,
    .fi-topbar .fi-input:where(.dark, .dark *) {
        color: var(--icu-topbar-fg);
    }

    .fi-topbar .fi-input::placeholder,
    .fi-topbar .fi-input-wrp-suffix {
        color: color-mix(in oklab, var(--icu-topbar-fg) 60%, transparent);
    }
</style>

@php
    $regionesConPlaca = [];

    foreach (['claro' => 'html:not(.dark)', 'oscuro' => '.dark'] as $modo => $prefijo) {
        foreach (['sidebar' => '.fi-sidebar-header', 'topbar' => '.fi-topbar'] as $region => $contenedor) {
            if ($placaDeLogo[$modo][$region]) {
                $regionesConPlaca[] = "{$prefijo} {$contenedor} .fi-logo";
            }
        }
    }
@endphp

@if ($regionesConPlaca !== [])
    {{-- Placa clara tras el logo: los logos corporativos suelen ser oscuros
         sobre transparente y se perderían sobre una barra oscura. Se controla en
         Configuración → Marca e identidad. --}}
    <style>
        {!! implode(",\n        ", $regionesConPlaca) !!} {
            background-color: #FFFFFF;
            padding: 0.25rem 0.5rem;
            border-radius: 0.5rem;
            box-sizing: content-box;
        }
    </style>
@endif
