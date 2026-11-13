---
name: pendientes-validacion-catalogos
description: Lista de pendientes abiertos sobre validación de catálogos inactivos y cascada de desactivación en asignaciones (Sistema ICU)
metadata: 
  node_type: memory
  type: project
  originSessionId: 823068bf-2dde-48a3-90c5-0dde22279160
  modified: 2026-08-12T19:54:33.337Z
---

Lista viva de pendientes que el usuario pide recordar cuando pregunte "¿qué pendientes hay?". Abierta desde el 2026-08-03. Actualizar (marcar hecho / agregar) conforme avance el trabajo.

## Contexto ya implementado (2026-08-03)

- Trait `App\Models\Concerns\WarnsOnInactiveCatalogRelations`: al guardar una asignación avisa —sin bloquear— con una notificación warning de 12s si el catálogo referenciado tiene `estado = false`. Una sola notificación agrupada cuando hay varios recursos inactivos. Conectado en `SoftwareUsuario`, `LicenciaUsuario`, `AccesoSharepoint` y `AccesoVirtual`.
- Cascada de desactivación vía `afterSave()` en las páginas Edit de Software, Licencia y (nuevo) SitioSharepoint: al poner el catálogo en `estado = false`, sus asignaciones activas pasan a `false`.
- Decidido explícitamente: `AccesoSistema` / catálogo `sistemas` queda FUERA de todo esto, no es necesario.

## Contexto implementado (2026-08-12) — baja en cascada por usuario

- `App\Services\Movimientos\ProcesarBajaColaborador` + `ResumenBajaColaborador`: al registrar un `Movimiento` tipo Baja desactiva los 8 modelos de acceso con `estado`, los accesos virtuales (vía `internet_id`), y cierra `fecha_fin` de equipo, celular, cargador del equipo y `software_exc`. Libera el equipo a `SinAsignar` y el cargador a `Disponible`. Idempotente y unidireccional (un Alta posterior NO reactiva nada).
- Disparado por `App\Observers\MovimientoObserver` en `created` y en `updated` cuando cambia `tipo`. Cubre `CreateMovimiento`, `EditMovimiento` y el toggle Alta/Baja de `EditUser`.
- `accesos_virtual` ya tiene columna `estado` (migración 2026_08_12_183911) + trait `TogglesEstadoOnSoftDelete` + UI completa.
- `MovimientoFormularioPdfData` deja de filtrar por `estado`/`fecha_fin` cuando el movimiento es Baja, para que el formulario oficial no salga vacío tras la cascada.
- Decidido: `asig_cargador_cel` queda FUERA — el cargador de celular pertenece al celular, no al usuario.

## Pendientes abiertos

1. **La cascada solo cubre la página Edit, no las demás rutas de edición.**
   Software, Licencia y SitioSharepoint también se editan desde el modal `EditAction` de la tabla y desde el `EditAction` de la página View. Por esas rutas la cascada NO corre. Propuesta pendiente de aprobación: mover la cascada del `afterSave()` de la página a un hook `updated` en el modelo del catálogo (trait hermano de `TogglesEstadoOnSoftDelete`), para cubrir tabla, modales, bulk actions y seeders de una sola vez. El usuario pidió `afterSave` explícitamente, por eso quedó así por ahora.

2. **Índices únicos no parciales complican el reingreso.**
   Tras una baja, `accesos_virtual`, `accesos_sharepoint`, `licencias_usuarios` y `software_usuarios` conservan sus filas con `estado = false` pero siguen ocupando la combinación única. Al reingresar al colaborador, crear el mismo acceso falla con violación de unicidad: hay que reactivar la fila existente. Es preexistente, pero mucho más frecuente ahora con la cascada. Falta decidir si se convierten en índices parciales o si se maneja con un mensaje amigable.

Relacionado: [[use-pnpm-not-npm]]
