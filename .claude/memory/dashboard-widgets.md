---
name: dashboard-widgets
description: "Dashboard propio con 8 widgets (2026-11-06); el super_admin NO salta Shield, cada widget nuevo exige re-correr RolesPermissionsSeeder"
metadata:
  node_type: memory
  type: project
  originSessionId: b92d8563-3571-455e-a7a7-e0c94f8dfc3c
  modified: 2026-11-06T18:49:39.966Z
---

El 2026-11-06 se creó `App\Filament\Pages\Dashboard` (sustituye al de Filament; también en el exclude de pages de Shield) con 8 widgets en `app/Filament/Widgets`:
- un stats de resumen;
- 4 charts: estados de equipos, altas/bajas, licencias y entregas/recepciones;
- 2 tablas: garantías a 90 días y mis firmas pendientes;
- `UsuariosPanelWidget`: panel con pestañas (Usuarios, Licencias, Software, Accesos). Cada pestaña es una subclase no descubierta de `UsuariosPanel\PestanaUsuarios`; los badges se declaran con `ColumnaDeBadges`. "Actividad reciente" se quitó a pedido del usuario. MFA = 2FA de Breezy (no Azure). Se muestran todos los usuarios.

Los charts por mes comparten el trait `App\Filament\Concerns\ConstruyeSeriesMensuales`. Tests en `tests/Feature/Filament/DashboardWidgetsTest.php`. Commiteado en c72b1e8 (2026-11-09) a pedido del usuario. Documentado en `.Doc_Laravel_13/Documentacion Sistema/Dashboard-y-Widgets/README.md`; los badges usan `listWithLineBreaks()` porque sin eso "Mostrar más" no despliega nada.

**Trampa:** con `define_via_gate => false`, el rol super_admin no salta los permisos. Solo ve lo que tiene asignado, y ese reparto lo hace `RolesPermissionsSeeder`. Un widget o recurso nuevo con `HasWidgetShield` da 403 o queda oculto hasta re-correr `db:seed --class=RolesPermissionsSeeder`. En los tests hay que crear y asignar los permisos `View:<Widget>` a mano.

**Why:** el super_admin recibía 403 en los widgets recién creados.

**How to apply:**
- `MisFirmasPendientesTable` es personal: va excluido de Shield y su `canView` se basa en ser firmante.
- Las pestañas del panel heredan el permiso único `View:UsuariosPanelWidget`; para agregar una basta crear la clase y sumarla a `UsuariosPanelWidget::PESTANAS`.
- El panel tiene el botón Matriz de perfiles: ver [[matriz-perfiles-excel]].

Relacionado: [[trampas-tests-y-listeners]], [[control-acceso-seguridad-usuarios]]
