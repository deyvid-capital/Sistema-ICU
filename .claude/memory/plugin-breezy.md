---
name: plugin-breezy
description: "Fork de Breezy (capitalsafi/filament-breezy) en packages/capitalsafi/filament-breezy: plan PLAN-001 para pasar todas las personalizaciones de perfil/2FA/sesiones del ICU al plugin y reutilizarlo en otros proyectos"
metadata:
  type: project
---

El 2026-11-22 se clonó `jeffgreco13/filament-breezy` (`3.x`, `fca79a6` = `v3.2.8` + 16 commits solo de CI/JS) en `packages/capitalsafi/filament-breezy/`, con el remoto original como `upstream`. Mismo enfoque que [[plugin-apariencia]]: `.agente/` (ESPECIFICACION, INVENTARIO-ICU, CAMBIOS, planes/PLAN-001, skills: verificar, portar-desde-la-app, agregar-opcion, sincronizar-upstream) y `.avance/` (AVANCE + revision-observacion OBS-001…005).

Estado (2026-11-22): T0–T3 hechas y revisadas; T4, T5, T6 (código) y T7 hechas por Claude Code, **por revisar**. La app usa el plugin en `main` (rama `breezy-capitalsafi` fusionada el 2026-11-23, fast-forward `3865130`): `composer.json` con repositorio `path` y `capitalsafi/filament-breezy: @dev`. Decisión 2026-11-23: los tres paquetes propios (breezy, apariencia, guía) siguen como repositorio `path`; en cada compu se clonan en `packages/capitalsafi/` antes de `composer install`. Pasar a `vcs` (GitHub + token en `auth.json`) se deja para producción. Tags del Breezy original borrados del clon y `remote.upstream.tagOpt=--no-tags`. Más adelante: rama `develop` además de `main`. Pendiente: tareas sencillas para Cursor (capturas T6-C1, `en` T6-C2, README T8-C1, CHANGELOG T8-C2) listadas en `.avance/AVANCE.md`, revisión de T4–T7, tag `v1.0.0` y `vcs` en producción.

Decisiones D1–D9 (aceptadas): `capitalsafi/filament-breezy`, `CapitalSafi\Breezy\BreezyPlugin`, preset `BreezyPlugin::capitalSafi()`; se conservan el ID `filament-breezy` (la firma digital usa `filament('filament-breezy')->verify()`) y las tablas `breezy_sessions`/`passkeys`; tests PHPUnit; passkeys y Sanctum apagados; sin merges del original, solo cherry-pick.

Lo que se porta (15 archivos de la app, detalle en INVENTARIO-ICU): asistente `/configurar-2fa`, middleware `requiere_2fa`, desafío sin códigos de recuperación, «Mi perfil» (nombres/apellidos/carnet/teléfono, recifrado del .p12 al cambiar contraseña, solo ciertos roles desactivan 2FA), `UserSessions`, pestañas 2FA/Sesiones de `ManageUserSeguridad`. Lo del ICU entra por closures (`required`, `canDisable`, `afterChange`, `saveUsing`) y eventos (auditoría), nunca `use App\`.

**Why:** Breezy es el plugin más tocado del ICU (hacks `getName()`, panel `admin` fijo, vistas sobrescritas) y otros sistemas de la organización van a necesitar el mismo 2FA/perfil.

Trampas: no llamar `__()` del paquete al configurar el panel (el traductor cachea el grupo vacío; usar closures); tras cambiar `resources/css/breezy.css` correr `artisan filament:assets`; el facade `Filament::registerPanel()` difiere el registro (en tests usar `app(PanelRegistry::class)->register()`); los tests del paquete usan su propio `vendor/`.

**How to apply:** antes de tocar perfil, 2FA o sesiones, leer `.agente/` y `.avance/AVANCE.md` del paquete. T7 (conectar en la app) cambia `composer.json`: pedir aprobación. Corrige la memoria vieja de [[control-acceso-seguridad-usuarios]] (OBS-004: el middleware ya no redirige a mi-perfil sino a `/configurar-2fa`).
