---
name: plugin-apariencia
description: "Plugin de apariencia (capitalsafi/filament-apariencia) en packages/capitalsafi/: temas SAFI, instalación path/vcs, API StudioBrand, roles y trampas"
metadata:
  type: project
---

El panel usa el plugin propio **`capitalsafi/filament-apariencia`** (MIT; atribución al autor original), en `packages/capitalsafi/filament-apariencia/` con git propio. Remoto: `https://github.com/deyvid-capital/filament-apariencia.git`. En Sistema ICU se instala por repositorio `path` (symlink, `@dev`) bajo `packages/capitalsafi/`. En producción: `vcs` + tag (`^1.0`). Namespace: `CapitalSafi\Apariencia\`.

Estado (README + CHANGELOG, 2026-11): hay tag **`v1.1.0`**. En la app ya se retiraron «Tema y colores» y «Marca e identidad»; el control lo toma el plugin. Rutas: `/admin/apariencia` (global, con `canCustomize`) y `/admin/mi-apariencia` (personal). Detalle de planes/revisiones en `.agente/` y `.avance/` del paquete.

## Instalación (resumen del README)

- Path (dev): `packages/capitalsafi/filament-apariencia` + `@dev` + `symlink: true`.
- VCS (prod): GitHub + versión por tags.
- Tras instalar: `php artisan apariencia:install --migrate` y `storage:link`.
- Requisitos: Filament 5, `spatie/laravel-settings` (tabla `settings`), disco público.

## Registro en paneles

- Admin: `AparienciaPlugin::make()->canCustomize(fn => auth()->user()?->hasRole(super_admin))` en `AdminPanelProvider` (obligatorio `canCustomize` o nadie edita lo global).
- Guía / solo lectura: `->settingsPage(false)->personalAppearance(false)` (hereda marca/colores sin editor). Ver `AnfitrionIcu`.
- API de marca: `CapitalSafi\Apariencia\Support\StudioBrand` (`logoUrl`, `logoPath`, `logoHeight`, `faviconUrl`, `touchIconUrl`).

Opciones: `settingsPage` (default true), `settingsNavigation` (false), `personalAppearance` (true). El plugin toma colores, fuente, modo oscuro, topbar, menú de usuario, sidebar, logos y favicon; lo del panel queda de **respaldo**.

## Qué hace (producto)

- 6 temas SAFI (claro+oscuro de un clic); estilos **Clásico** / **Suave**; fondos sólido o imagen.
- Editor en pestañas: Tema · Colores · Texto y diseño · Marca · Perfiles.
- Lato local embebida (default); Google Fonts opcionales (CSP: fonts.googleapis.com / gstatic).
- Marca: logos WebP ≤1024, alto 1–6 rem/em o 16–96 px, placa detrás del logo, favicon PNG/WebP ≥180 (genera 32 + apple-touch 180; **sin SVG/ICO**).
- Sidebar al color del fondo; íconos e ítem activo configurables.
- Perfiles: no guardan marca (logos/favicon/barra); los del admin son compartidos.
- Imágenes: `StudioImageProcessor` (tipo por contenido, megapíxeles, recodifica, limpia al reemplazar/quitar/restablecer). Comando `apariencia:limpiar`.
- Datos: `settings` grupo `studio`; tablas `studio_user_preferences`, `studio_profiles` (nombres conservados a propósito).

## Trampas

- Tras cambiar CSS del paquete: `sail artisan filament:assets` (copia a `public/css/capitalsafi/`; no editar esa copia).
- Tests del paquete: `composer test` dentro del paquete, o `sail exec -w /var/www/html/packages/capitalsafi/filament-apariencia laravel.test vendor/bin/phpunit`.
- Config publicada (`apariencia-config`) se congela: al actualizar, comparar claves nuevas.
- Cambios de apariencia van en el **paquete**, no en `theme.css` ni en un `TemaService` de la app.

**Why:** apariencia separada del núcleo ICU para versionar y desplegar con Composer.

**How to apply:** leer primero el README del paquete; tocar código en `packages/capitalsafi/filament-apariencia/`. Relacionado: [[guia-usuario-avance]], [[use-pnpm-not-npm]].
