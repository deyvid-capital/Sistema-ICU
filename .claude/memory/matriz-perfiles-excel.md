---
name: matriz-perfiles-excel
description: Reglas de negocio de la Matriz de perfiles (Excel por cargo) decididas por el usuario el 2026-11-09; commit db5a251
metadata:
  node_type: memory
  type: project
  originSessionId: b92d8563-3571-455e-a7a7-e0c94f8dfc3c
  modified: 2026-11-09T19:43:59.891Z
---

Botón "Matriz de perfiles" en el panel de usuarios del dashboard (header action de `PestanaUsuarios`). Descarga un .xlsx con una fila por cargo. La lógica está en `App\Support\MatrizPerfiles\MatrizPerfiles` y el archivo lo escribe `ExportadorMatrizPerfilesXlsx` con OpenSpout, que llega como dependencia de filament/actions. Commit db5a251 (2026-11-09).

Reglas que fijó el usuario (no derivables del código):
- **Cargo único.** Si varias personas comparten cargo, sus accesos se unen en una sola fila y sin repetidos.
- **Celdas.** Varios valores en una celda separados por " | "; sistemas como "Viafirma - Usuario".
- **Internet.** El área con siglas, "Inversiones Alternativas (IA)", igual que el formulario de alta/baja. Usa `App\Support\SiglasArea`, compartido con `MovimientoFormularioPdfData`. No se usa `nivel_acceso`.
- **Software.** Cuenta el asignado al usuario más el de su área. Las excepciones NO cuentan, porque son personales. Primero se pidió "solo del área", luego se corrigió a esto.
- **Descarga.** Síncrona, sin cola ni BD (el usuario lo confirmó).

**Why:** el usuario dio y corrigió estas reglas explícitamente.

**How to apply:** si se toca la matriz o el formulario de alta/baja, mantener estas reglas. En dev `software_areas` está vacío, así que el software visible sale de las asignaciones por usuario.

Relacionado: [[dashboard-widgets]]
