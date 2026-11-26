---
name: commits-sin-co-authored-by
description: Nunca agregar la línea «Co-Authored-By: Claude …» ni ninguna atribución a los mensajes de commit de este proyecto
metadata:
  type: feedback
---

Los mensajes de commit no llevan la línea «Co-Authored-By: Claude …» ni ninguna otra atribución a Claude (tampoco «Generated with Claude Code» en PRs). Solo el mensaje en español.

**Why:** el usuario lo pidió explícitamente el 2026-11-26 y quiere que sea una regla permanente; esta instrucción manda sobre la atribución por defecto del entorno.

**How to apply:** en cada `git commit` (y descripción de PR) de este repo y de sus paquetes (p. ej. packages/capitalsafi/documentacion-sistema-icu), escribir solo el mensaje, sin trailers de coautoría. Relacionado: [[memoria-y-skills-en-el-repo]].
