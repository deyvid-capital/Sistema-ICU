---
name: suite-completa-verde
description: "La suite completa pasa entera (781/781, 2026-11-11): cualquier fallo es regresión; correrla con memory_limit=1G o la matan por memoria"
metadata:
  type: project
---

El 2026-11-11 la suite completa pasó **781/781** (739/739 el 2026-11-09). Los 19 tests que antes fallaban por trabajo en curso (FortiToken, PdfPreview, cargadores, etc.) ya están arreglados. Por eso cualquier fallo nuevo es una regresión real.

**Trampa:** `vendor/bin/sail artisan test --compact` sin más terminó con exit 137 (sin memoria). Funciona así:
`vendor/bin/sail php -d memory_limit=1G artisan test --compact`
Tarda unos 17 minutos, así que conviene lanzarla en segundo plano.

**How to apply:** antes de commitear un cambio grande, correr la suite así; un rojo no se descarta como "pre-existente".

Relacionado: [[trampas-tests-y-listeners]]
