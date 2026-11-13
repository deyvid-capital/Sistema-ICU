---
name: control-acceso-seguridad-usuarios
description: "cómo está montado el control de acceso y seguridad de usuarios (login, 2FA, sesiones, notificaciones) para el super-admin"
metadata: 
  node_type: memory
  type: project
  originSessionId: d4257c13-73a1-473d-a2a7-589b5502bc58
  modified: 2026-08-20T17:12:23.070Z
---

Feature de control de acceso y seguridad por usuario (super-admin), implementada en fases sobre Filament v5 + Breezy + Shield:

- **Acceso al panel:** `User::canAccessPanel()` delega en `User::motivoAccesoDenegado()` (fuente única de verdad, también usada para los mensajes de login). Columnas en `users`: `acceso_habilitado`, `acceso_expira_at`, `requiere_2fa`, `intentos_fallidos`, `bloqueado_at` (fusionadas en el `create` migration).
- **Login:** `App\Filament\Pages\Auth\Login` (registrado con `->login()`) cuenta intentos fallidos por cuenta, bloquea al máximo (`config/acceso.php`), y muestra notificaciones específicas (intentos restantes, bloqueado, vencido, deshabilitado, sin rol). Reset del contador vía listener del evento `Login`.
- **2FA:** flag `requiere_2fa` + middleware `EnsureTwoFactorWhenRequired` (en `authMiddleware`) que redirige a "mi-perfil". El 2FA vive en `breezy_sessions` (no en `users`); resetear = `breezySessions()->withoutGlobalScopes()->delete()`.
- **Sesiones:** `App\Support\Sessions\UserSessions` (active/terminate) sobre la tabla `sessions` (user_id uuid).
- **Notificaciones:** tabla `notification_preferences` + `User::debeRecibir()` (opt-out, default true) que consulta `CriticalEventNotification::via()`.
- **UI:** `ManageUserSeguridad` (sub-página de `UserResource`, patrón `EditRecord`) con **Tabs** (Acceso / 2FA / Sesiones / Notificaciones), gated por ability `manageSecurity` (permiso custom `ManageSecurity:User`). Acciones rápidas y masivas en `UsersTable`.
- **Roles:** `RolesPermissionsSeeder` corre `shield:generate` y da todos los permisos solo a `super_admin`; `admin_ti`/`operador`/`auditor` se crean vacíos.

Auditoría (spatie/laravel-activitylog) quedó **aplazada** a propósito. Ver tests en `tests/Feature/Auth`, `tests/Feature/Notifications`, `tests/Feature/Filament/Seguridad*`. Ojo con [[suite-completa-verde]].
