---
name: plugin-breezy
description: "Fork de Breezy (capitalsafi/filament-breezy) en packages/capitalsafi/filament-breezy: plan PLAN-001 para pasar todas las personalizaciones de perfil/2FA/sesiones del ICU al plugin y reutilizarlo en otros proyectos"
metadata:
  type: project
---

El 2026-11-22 se clonó `jeffgreco13/filament-breezy` (`3.x`, `fca79a6` = `v3.2.8` + 16 commits solo de CI/JS) en `packages/capitalsafi/filament-breezy/`, con el remoto original como `upstream`. Mismo enfoque que [[plugin-apariencia]]: `.agente/` (ESPECIFICACION, INVENTARIO-ICU, CAMBIOS, planes/PLAN-001, skills: verificar, portar-desde-la-app, agregar-opcion, sincronizar-upstream) y `.avance/` (AVANCE + revision-observacion OBS-001…005).

Estado: **solo plan**, sin código ni commits. La app sigue con Breezy `v3.2.8` de Packagist. T0 (Vladimir): aceptar D1–D9, crear repo privado `deyvid-capital/filament-breezy`, commit base, línea base de tests.

Propuesta (D1–D9 pendientes): `capitalsafi/filament-breezy`, `CapitalSafi\Breezy\BreezyPlugin`, preset `BreezyPlugin::capitalSafi()`; se conservan el ID `filament-breezy` (la firma digital usa `filament('filament-breezy')->verify()`) y las tablas `breezy_sessions`/`passkeys`; tests PHPUnit; passkeys y Sanctum apagados; sin merges del original, solo cherry-pick.

Lo que se porta (15 archivos de la app, detalle en INVENTARIO-ICU): asistente `/configurar-2fa`, middleware `requiere_2fa`, desafío sin códigos de recuperación, «Mi perfil» (nombres/apellidos/carnet/teléfono, recifrado del .p12 al cambiar contraseña, solo ciertos roles desactivan 2FA), `UserSessions`, pestañas 2FA/Sesiones de `ManageUserSeguridad`. Lo del ICU entra por closures (`required`, `canDisable`, `afterChange`, `saveUsing`) y eventos (auditoría), nunca `use App\`.

**Why:** Breezy es el plugin más tocado del ICU (hacks `getName()`, panel `admin` fijo, vistas sobrescritas) y otros sistemas de la organización van a necesitar el mismo 2FA/perfil.

**How to apply:** antes de tocar perfil, 2FA o sesiones, leer `.agente/` y `.avance/AVANCE.md` del paquete. T7 (conectar en la app) cambia `composer.json`: pedir aprobación. Corrige la memoria vieja de [[control-acceso-seguridad-usuarios]] (OBS-004: el middleware ya no redirige a mi-perfil sino a `/configurar-2fa`).
