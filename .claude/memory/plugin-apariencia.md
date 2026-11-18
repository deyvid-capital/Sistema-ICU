---
name: plugin-apariencia
description: "Plugin de apariencia (capitalsafi/filament-apariencia) en packages/capitalsafi/: fork de filamizer, temas SAFI, decisiones del usuario, roles y estado"
metadata:
  type: project
---

El panel usa el plugin propio **`capitalsafi/filament-apariencia`** (fork de `filamizer/filamizer`, MIT), en `packages/capitalsafi/filament-apariencia/` con git propio. Remoto: `https://github.com/deyvid-capital/filament-apariencia.git`; `upstream` (filamizer) solo para traer mejoras, con el push bloqueado (`NO_PUSH`). Se instala por repositorio `path` (symlink, `@dev`). Rutas `/admin/apariencia` (solo `super_admin`) y `/admin/mi-apariencia`.

Estado al 2026-11-15: PLAN-001 cerrado (T1–T7). Falta el tag `v1.0.0` y pasar la app de `path` a `vcs` con versión fija antes de producción. Detalle en `.agente/` (especificación, CAMBIOS, planes) y `.avance/` (tablero, revisiones `REV-*`, observaciones `OBS-*`) del paquete: leerlos primero.

Decisiones del usuario (vigentes):
- Sidebar **siempre** del color del fondo (lienzo `#F4F7FA` / `#0B1620`).
- Solo estilos Clásico y Suave; sin presets genéricos ni organizador del sidebar.
- 6 temas SAFI (safi por defecto, confianza, cielo, turquesa, naturaleza, neutro); cada uno rellena claro y oscuro.
- Editor en pestañas: Tema · Colores · Texto y diseño · Marca · Perfiles.
- Lato embebida por defecto; Google Fonts como opción.
- La marca (favicon, logos) debe centralizarse en el plugin y retirarse «Tema y colores» y «Marca e identidad» de la app (plan en curso, PLAN-002).

Roles: el usuario y otro IDE implementan; Claude Code revisa (tests, diffs, capturas Puppeteer en `storage/app/ui-shots/`), escribe `REV-*`/`OBS-*` y actualiza `.avance/AVANCE.md` y `.agente/CAMBIOS.md`.

Trampas conocidas:
- `@js()` dentro de atributos de una etiqueta `<x-componente>` no se compila: poner los `x-bind` en la vista del propio componente.
- La vista previa en vivo debe generar la escala 50–950 igual que `Color::generatePalette()` (OKLCH, solo el tono), no mezclando con negro.
- Tras cambiar `resources/dist/*.css` del paquete: `sail artisan filament:assets` (copia a `public/css/capitalsafi/`; no editar esa copia).
- Tests del paquete: `sail exec -w /var/www/html/packages/capitalsafi/filament-apariencia laravel.test vendor/bin/phpunit`.

**Why:** el usuario separó la apariencia en un plugin propio para no mezclar código con la app y poder actualizarlo con Composer.

**How to apply:** los cambios de apariencia se hacen en el paquete, no en `theme.css` ni en `TemaService`. Relacionado: [[guia-usuario-avance]], [[memoria-y-skills-en-el-repo]], [[use-pnpm-not-npm]].
