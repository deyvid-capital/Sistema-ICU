---
name: inventario-eliminado-crudselect
description: El cluster Inventario (modelo/micro/RAM/GPU) se eliminó el 2026-11-26; esos catálogos se gestionan con CrudSelect en EquipoForm
metadata:
  node_type: memory
  type: project
  originSessionId: 33e22bc8-c914-4bee-aa89-8b95ca7e66f4
  modified: 2026-11-27T02:15:44.557Z
---

El 2026-11-26 el usuario pidió borrar el módulo Inventario (InventarioCluster, 4 resources y sus 4 importadores) por ineficiente. Los modelos, tablas y policies siguen: Equipo los usa por FK.

- EquipoForm usa `selectorDeCatalogo()` → CrudSelect con `optionsFromModel` + withCount de equipos (con eliminados); «Eliminar» solo si no hay equipos.
- Los permisos Create/Update/Delete de esos 4 modelos ya no salen de resources: están en `custom_permissions` de config/filament-shield.php.
- EquipoImporter crea el catálogo que falte (búsqueda sin distinguir mayúsculas) en vez de exigir que exista.
- Pendiente que el usuario dejó para después: badge/contador de equipos por opción.

**Why:** cargar un equipo obligaba a salir a Inventario para crear un modelo.
**How to apply:** no reintroducir resources para estos catálogos; mejoras van en el CrudSelect del formulario. Relacionado: [[dashboard-widgets]]
