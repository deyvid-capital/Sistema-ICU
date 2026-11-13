---
name: guia-usuario-avance
description: "Guía de usuario (/guia): desde 2026-11-13 es el paquete aparte documentacion-sistema-icu en packages/; decisiones del usuario, dónde está cada cosa y qué falta"
metadata:
  node_type: memory
  type: project
  originSessionId: b92d8563-3571-455e-a7a7-e0c94f8dfc3c
  modified: 2026-11-13T18:14:54.737Z
---

La guía de usuario del Sistema ICU es un **paquete Composer aparte**: `deyvid-capital/documentacion-sistema-icu`, namespace `SistemaIcu\Documentacion`, en `packages/documentacion-sistema-icu/` (git propio; `/packages/` está en el .gitignore del sistema). Remoto: `https://github.com/deyvid-capital/documentacion-sistema-icu-.git` (con guion final). El entorno del agente no tiene credenciales de GitHub: el push lo hace el usuario.

Decisiones del usuario (2026-11-13):
- Separar la guía del sistema para "no mezclar las cosas"; contenido organizado como la documentación de Filament; cada versión del sistema lleva su versión del paquete.
- **Pantalla libre:** el plugin puede crear sus propios componentes y menú o cambiar los de Filament; lo único fijo es la **paleta de colores del sistema**.
- **Sin edición desde el navegador en la primera versión.** Quedó solo en la rama de respaldo.
- Solo para este sistema (no un motor reutilizable).
- Las skills van dentro del paquete, en `.agents/skills/` (`.claude/skills` es un enlace a esa carpeta), para repartir trabajo con otros IDE y agentes.
- Reglas anteriores que siguen: lo no permitido responde 404; PDF solo con lo visible; capturas nunca a mano ni con datos reales (base `guia_demo`); sin permisos nuevos.

Estado al 2026-11-13 (fin del día):
- Respaldo del estado anterior (con edición): rama local `guia-usuario-base`, commit `65a333d`, sin tocar `main`.
- En el sistema, la conexión con el paquete ya tiene commit en `main` (`composer.json` con repo `path` a `packages/*` y `dev-main`, `config/documentacion-icu.php`, `app/Support/Guia/AnfitrionIcu.php`, `GuiaDemoSeeder`, import en `theme.css`, suite `Documentacion` en `phpunit.xml`). **Para instalar dependencias en otro equipo hay que clonar antes el repo del paquete en `packages/documentacion-sistema-icu/`.**
- Paquete: hechas T1 (formato numerado con frontmatter), T2 (pantalla nueva + menú lateral propio plegable, componente `documentacion-icu::menu` con `Contenido\Menu`), T4 (PDF/capturas) y los capítulos Primeros pasos, Equipos (+Recibos), Inventario, Asignación de equipos, Celulares y Usuarios. Faltan C5 Accesos, C6 Software, C7 Recursos, C8 Firma digital, C9 Documentos PDF, C10 Panel de inicio, C11 Administración, y T5 (versiones/producción).
- Reparto de trabajo: **otro IDE (Cursor) hace las tareas del paquete; yo reviso.** Él anota el avance en `packages/documentacion-sistema-icu/.avance/<tarea>.md` y yo dejo observaciones en `.avance/revision/` (códigos R/V/C, prioridad, "listo cuando"; decisiones del usuario en `2026-11-13-decisiones-del-usuario.md`). `.avance/` está ignorada y no se sube. Un commit por tarea en el paquete (regla en `.avance/revision/COMMITS.md` y `AGENTS.md`); el push lo hace el usuario.
- Decisiones del usuario sobre contenido: se documentan **todos** los módulos (dashboard incluido); eliminar y restaurar se explica como texto, sin capturas.
- Observaciones abiertas al cerrar el día: C4-1 (decir qué hace una baja y que se revierte con «Reasignar todo»), C4-2 (commits pendientes en el paquete) y D4 (reflejar en la guía los cambios del sistema en Celulares y Usuarios).
- Lecciones de revisión que se repiten: mirar cada captura (los tests no ven imágenes); una página por tarea con el permiso de su botón; fechas fijas (el servidor de capturas detiene el reloj en 2026-06-15 con `GUIA_FECHA_FIJA`); citar el mensaje exacto del sistema.

Cómo trabajar:
- Único contacto con el sistema: `Contracts\Anfitrion` (implementado por `AnfitrionIcu`) y `config/documentacion-icu.php`. Ningún `use App\` en `src/` ni `resources/` del paquete.
- Tests del paquete corren con la app del sistema: `vendor/bin/sail artisan test --compact --testsuite=Documentacion`. No correrlos mientras la suite completa está en marcha: comparten la base de pruebas.
- Pint sobre el paquete: pasar las rutas (`packages/documentacion-sistema-icu/src …`); `--dirty` no lo ve porque la carpeta está ignorada.
- Capturas: `vendor/bin/sail artisan guia:capturar [capitulo] [--sin-preparar]`. Tras tocar estilos: `sail pnpm run build`. Tras tocar el job: `sail artisan queue:restart`.
- Revisar un PDF sin navegador: generarlo con tinker y rasterizarlo con `gs` dentro del contenedor.

**Why:** el usuario pidió separar la guía en un plugin propio y repartir el trabajo entre agentes.

**How to apply:** el trabajo de la guía se hace en el paquete, siguiendo su `AGENTS.md` y `TAREAS.md`. Antes de producción hay que cambiar el `composer.json` del sistema de `path` a repositorio privado con versión fija (T5). Plan y decisiones: `Docs/Plugin-Documentacion-SistemaICU/README.md`.

Relacionado: [[suite-completa-verde]], [[dashboard-widgets]]
