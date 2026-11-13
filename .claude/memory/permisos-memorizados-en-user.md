---
name: permisos-memorizados-en-user
description: User memoriza hasPermissionTo por instancia (rendimiento); reglas de seguridad que no se deben romper al tocarlo
metadata:
  type: project
---

Desde 2026-10-19, `App\Models\User` usa el trait `MemoizesPermissionChecks` (sobrescribe `hasPermissionTo` con `insteadof HasRoles`). Motivo: 494 permisos de Shield hacían que cada `can()` costara ~2–3 ms y la lista de usuarios hacía ~300 comprobaciones (~900 ms → ~300 ms tras el cambio).

**Why:** el usuario preguntó explícitamente por riesgo de escalada de privilegios; se diseñó para no tenerlo.

**How to apply:** nunca mover la memoria a estado estático/global ni usar `once()` en contexto estático (fuga entre usuarios en workers/Octane). La invalidación depende de `unsetRelation/setRelation('roles'|'permissions')` y del listener `InvalidarPermisosMemorizados` (KeyForgotten de la clave de caché de Spatie). No activar `define_via_gate` de Shield como atajo de rendimiento: salta las policies. Tests: `tests/Feature/Models/UserPermissionMemoizationTest.php`.

Relacionado: [[control-acceso-seguridad-usuarios]], [[trampas-tests-y-listeners]]
