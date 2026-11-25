---
name: software-por-cargo-y-categorias
description: 2026-11-25 software por área pasó a software por cargo y la categoría de software pasó a tabla propia; decisiones no obvias
metadata:
  type: project
---

El 2026-11-25 el usuario pidió (sin migraciones nuevas: editar la existente y re-correr migrate:fresh --seed él mismo):
- `software_areas`/`SoftwareArea` → `software_cargos`/`SoftwareCargo` (cargo_id → cargos.id_cargo). Matriz de perfiles y PDF de alta/baja toman el software del cargo.
- `softwares.categoria` (string) → `categoria_id` FK a `categorias_software` (`CategoriaSoftware`, pk `id_categoria`, `codigo` único). El enum `CategoriasSoftware` sigue vivo: el PDF y la matriz ubican columnas por `codigo` = valor del enum; `CategoriaSoftwareSeeder` siembra desde el enum. El código no se edita tras crear.
- `AsignarSoftwarePorDefectoAction` → `AsignarSoftwarePorCategoriaAction` (solo dueño User) + nueva `AsignarSoftwarePorCargoAction` (User: su cargo; Software: a personas de un cargo sin Baja). Lógica en `App\Services\Software\AsignarSoftwareEnBloque`: solo software activo, restaura/activa asignaciones eliminadas o inactivas.

**Why:** decisión del usuario; el perfil de software depende del cargo, no del área.

**How to apply:** no reintroducir área en el software; nuevas categorías del enum necesitan fila en `categorias_software` (deEnum lo hace). Relacionado: [[matriz-perfiles-excel]], [[pendientes-validacion-catalogos]].
