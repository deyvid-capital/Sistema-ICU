---
name: suite-completa-verde
description: "La suite completa pasa entera (~870 tests al 2026-11-26): cualquier fallo es regresión; correrla con memory_limit=1G y sin otra corrida en paralelo"
metadata:
  type: project
---

El 2026-11-26 la corrida del usuario dio 868 aprobados y 2 fallos, ambos resueltos ese día (un test arreglado y la V2 del formulario de alta/baja descartada). Antes: 838/838 el 2026-11-25, 781/781 el 2026-11-11. Los 19 tests que antes fallaban por trabajo en curso (FortiToken, PdfPreview, cargadores, etc.) ya están arreglados. Por eso cualquier fallo nuevo es una regresión real.

**Trampa:** `vendor/bin/sail artisan test --compact` sin más terminó con exit 137 (sin memoria). Funciona así:
`vendor/bin/sail php -d memory_limit=1G artisan test --compact`
Tarda unos 15 minutos, así que conviene lanzarla en segundo plano.

**Trampa 2:** si el usuario (u otro IDE) corre tests al mismo tiempo, ambas corridas comparten la base `testing` y aparecen fallos falsos en masa (p. ej. «column attribute_changes of relation activity_log does not exist»). Volver a correr los archivos afectados antes de darlos por regresión.

**How to apply:** antes de commitear un cambio grande, correr la suite así; un rojo no se descarta como "pre-existente".

Relacionado: [[trampas-tests-y-listeners]]
