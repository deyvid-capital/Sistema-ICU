---
name: firma-digital-avance
description: "Estado del módulo de firma digital (fases 1-7 hechas, 8 pendiente), decisiones del usuario y pendientes; consultar antes de tocar firma, PDF o almacenamiento"
metadata:
  node_type: memory
  type: project
  originSessionId: ecb7ce17-e6aa-4cce-9c34-0c023d72d428
  modified: 2026-10-30T15:11:10.352Z
---

Módulo de firma digital (plan en `Docs/firma-digital/*.md`, doc del sistema en `.Doc_Laravel_13/Documentacion Sistema/Firma-Digital/README.md`). Estado al 2026-10-30:

- **Fases 1-6 hechas y commiteadas** (la 6 = notificaciones/recordatorios en 08cf2bb). Cada aviso de firma respeta el toggle campana/correo por usuario en `ManageUserSeguridad` (trait `RespetaPreferenciaFirma`, `TipoEventoNotificacion::FirmaDigital`); no hay eventos con candado. Recordatorios y vencimientos corren con `schedule:work` en el contenedor `scheduler` de `compose.yaml` (sin cron; crontab solo para servidores sin Docker, README §9.5).
- **Fase 7 + doc 09 (documentos firmados en origen) implementadas el 2026-10-30, sin commit** (el usuario commitea).
  - Estado de firma en `pdf_equipos`/`pdf_celulares`/`pdf_movimientos` (enum `EstadoFirmaOrigen`, trait `TieneEstadoFirma`).
  - Completados se publican en la misma ruta del origen (job `PublicarDocumentoFirmado` → `PublicarFirmadoEnOrigen`); rechazados no.
  - Regla única de reemplazo `ReemplazoDocumentoFirmado`: bloqueado en firma, confirmación obligatoria si está firmado.
  - "Subir firmado a mano" por permisos `UploadSigned:PdfEquipo|PdfCelular|PdfMovimiento`.
  - Acciones/columna/filtro comunes en `FirmaEnOrigenActions`; asistente con filtro de categoría y "Mostrar firmados".
- **Almacenamiento:** marca (logos, favicon) siempre en disco `public`; todo lo confidencial (PDF, capturas de mantenimiento) solo `local` o `s3`, nunca `public`. PDF servidos por la ruta protegida `archivos.pdf.ver|descargar` (`DocumentoPdfController`). Comando `archivos:mover` migra lo viejo.

**Why:** decisiones explícitas del usuario: recibos de equipo NO se firman (son facturas de compra, solo escaneo); no re-firmar lo firmado; regenerar lo puede cualquiera con permiso; no tocar el modelo `Firmante` por ahora; límite de tamaño solo para archivos subidos.

**How to apply:**
- No commitear nunca; el usuario lo hace.
- Contraseñas de .p12 nunca en texto plano, cola ni logs.
- `.firma-digital/` y `.env` son locales.
- Pendientes en dev que debe correr el usuario: `archivos:mover --simular` y luego `archivos:mover`. Hay que revisar a mano `FUE-001-2026.pdf` y `FUA-Ana Campero-21-10-2026.pdf`, que difieren entre public y local. Después correr `firmas:sincronizar-origen` y re-subir logos si faltan.
- Pendiente a futuro:
  - posiciones de firma predeterminadas por plantilla;
  - panel de uso de almacenamiento;
  - precarga de firmantes desde `Firmante`;
  - fase 8 (endurecimiento).

Ver [[suite-completa-verde]] (hoy la suite pasa entera) y [[permisos-memorizados-en-user]].
