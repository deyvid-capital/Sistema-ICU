---
name: memoria-y-skills-en-el-repo
description: "El usuario trabaja el proyecto en dos equipos: la memoria se copia a .claude/memory del repo y las skills de .claude y .cursor se versionan, para que el otro equipo tenga constancia"
metadata:
  type: feedback
---

El usuario tiene otro equipo en casa con este mismo proyecto. El 2026-11-13 pidió versionar en el repositorio del Sistema ICU las skills de `.claude/skills` y `.cursor/skills` y mantener al día `.claude/memory/MEMORY.md` (copia de esta memoria dentro del repo), "para que tenga constancia de todo lo que se está haciendo".

**Why:** la memoria automática vive fuera del repo, en el equipo donde corre Claude; sin la copia, el otro equipo no sabe qué se decidió ni en qué quedó el trabajo.

**How to apply:** cuando el usuario pida actualizar la memoria o hacer un commit de cierre, copiar los archivos de esta memoria a `.claude/memory/` del proyecto (mismos nombres, `MEMORY.md` incluido) e incluirlos en el commit. No versionar `.claude/settings.local.json` ni `.cursor/mcp.json` (configuración local, puede llevar credenciales). El push lo hace él: este entorno no tiene credenciales de GitHub. Al empezar en un equipo donde la memoria automática esté vacía o vieja, leer `.claude/memory/` del repo.

Relacionado: [[guia-usuario-avance]]
