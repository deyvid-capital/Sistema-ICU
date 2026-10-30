# 9. Documentos firmados en su módulo de origen (fase 7)

> **Estado:** implementado junto con la **fase 7** del [plan](07-plan-de-implementacion.md).

## El problema

En el paso **Documentos** del asistente aparecen **todos** los PDF del sistema, aunque ya estén firmados, y no hay forma de filtrarlos. Hoy son 3 y no molesta; con cientos se vuelve inmanejable.

Además, cuando un documento termina de firmarse, el PDF firmado **solo vive dentro del envío** (`firma-digital/solicitudes/{código}/`). En su módulo de origen (Entregas, Recepciones, Altas/Bajas, etc.) sigue estando el PDF **sin firmar**, y quien lo abra desde ahí ve la versión equivocada.

## Lo que se pide

1. **Filtrar** los documentos del sistema por categoría (entrega/recepción de equipos, notas, celulares, alta, baja), del **más reciente al más antiguo**.
2. **Ocultar los ya firmados**, con la opción de mostrarlos.
3. **Marcar en el origen** el estado de firma, con un **contador** de cuántos faltan.
4. Al completarse la firma, **reemplazar en el origen** el PDF sin firmar por el firmado, en la **misma ruta**, para que no haya duplicados.
5. Poder **subir un escaneo** cuando la firma se hizo a mano, y que reemplace al PDF en su categoría.
6. Al **regenerar** un PDF ya firmado, mostrar un **modal de advertencia**: la firma se pierde porque el archivo se reemplaza.
7. Separar el **almacenamiento**: lo público (logos y favicon) en el disco `public`; todo lo confidencial (PDF, imágenes de mantenimiento) en privado, con la opción de moverlo a S3 pero **nunca** al disco `public`.

## Decisiones

| Tema | Decisión |
|---|---|
| Documentos que se firman | PDF de **equipos**, **celulares** y **movimientos** (alta/baja). **Los recibos de equipo quedan fuera**: son facturas de compra escaneadas que solo se suben |
| Dónde quedan los PDF | En el **disco confidencial privado** (`local`, o `s3` si se configura). Se sirven solo con sesión y permiso |
| Rechazo parcial | Solo se publican en el origen los documentos **completados**. Un documento rechazado no se publica |
| Volver a firmar algo ya firmado | No se permite. Si falta alguien, se regenera y se envía de nuevo |
| Subir firmado a mano | Con **permiso** propio por tipo: `UploadSigned:PdfEquipo`, `UploadSigned:PdfCelular`, `UploadSigned:PdfMovimiento` |
| Regenerar un firmado | Cualquiera con permiso para generar el PDF, previa confirmación en el modal |
| "Mostrar firmados" en el asistente | Sí. Se ven para consulta, pero **no** se pueden elegir |
| Límite de tamaño | Solo se aplica a los archivos **subidos**; publicar un firmado no lo valida. Un panel de uso de almacenamiento queda para después |
| Firmantes del formulario (`Firmante`) | No se tocan |

---

## Almacenamiento: público frente a confidencial

Antes de este cambio había **un solo disco** (`ARCHIVOS_DISK` / *Configuración → Archivos*) para todo. Al ponerlo en `local` (privado), los logos y el favicon dejaban de verse, porque el navegador no puede leer un disco privado. Al dejarlo en `public`, los PDF quedaban expuestos con nombres predecibles.

| Grupo | Qué incluye | Disco | ¿Configurable? |
|---|---|---|---|
| **Público** | Logo claro, logo oscuro, favicon | `public` (siempre) | No |
| **Confidencial** | PDF de equipos, celulares y movimientos, recibos de equipo, capturas de mantenimiento | `local` (privado) por defecto, o `s3` | Sí, en *Configuración → Archivos*, **solo** entre `local` y `s3` |
| **Firma digital** | `.p12` cifrados y versiones de los envíos | `local` (siempre) | No, por seguridad |

Cómo se sirven los archivos confidenciales:

- **PDF:** con una ruta propia (`/archivos/pdf/{tipo}/{id}/ver` y `/descargar`) que exige sesión y permiso de ver ese registro. Funciona igual con `local` y con `s3`.
- **Imágenes de mantenimiento:** con **URL temporales firmadas** (Media Library + `temporaryUrl`). El disco `local` las admite gracias a `serve => true`.

**Las columnas `url` no cambian.** Guardan la ruta relativa dentro del disco (`pdf-equipos/entrega/FUE-001-2026.pdf`), así que basta con mover el archivo al disco confidencial; el enlace que ve el usuario pasa a ser la ruta protegida.

**Mover archivos existentes:** el comando `archivos:mover` lleva cada archivo a su disco correcto:

- Lo confidencial va de `public` al disco confidencial configurado, o de `local` a `s3` (y al revés).
- La marca pasa al disco `public`.

Copia, comprueba y recién entonces borra el original. Nunca mueve un archivo confidencial al disco `public`.

---

## Cómo están hoy los PDF de origen

| Tabla / modelo | Lo generan o suben | Categorías | Ruta del archivo |
|---|---|---|---|
| `pdf_equipos` / `PdfEquipo` | Generado desde `EntregaEquipo`, `RecepcionEquipo`, `NotaSalidaEquipo`, `NotaRecepcionEquipo` (morph `documento`). También se puede subir en *PdfEquipos* | Entrega (FUE), Recepción (FUR), Nota de salida, Nota de recepción externa | `pdf-equipos/{tipo}/{prefijo}{número}.pdf` |
| `pdf_celulares` / `PdfCelular` | Generado desde `EntregaCelular` y `RecepcionCelular`. También se puede subir | Entrega (FUC), Recepción (FURC) | `pdf-celulares/{tipo}/…` |
| `pdf_movimientos` / `PdfMovimiento` | Generado desde `Movimiento` | Alta (FUA), Baja (FUB), según `movimientos.tipo` | `pdf-movimientos/FUA-Nombre Apellido-dd-mm-aaaa.pdf` |

- **Regenerar sobrescribe.** `Generate*FormularioPdf` escribe en la **misma ruta** y actualiza la fila existente; no crea otra.
- **Ya se bloquea regenerar mientras está en firma.** Lo hace `DocumentosEnFirma`, desde la fase 5.
- **El vínculo con el envío ya existe.** `solicitud_firma_documentos.origen_type` / `origen_id` apunta a la fila de origen (morph).
- **`PdfMovimiento`** no tiene `timestamps` ni `SoftDeletes`, a diferencia de los demás.

---

## Diseño

### 1. Columnas nuevas en las 3 tablas de origen

Una migración agrega lo mismo a `pdf_equipos`, `pdf_celulares` y `pdf_movimientos`:

| Columna | Tipo | Para qué |
|---|---|---|
| `estado_firma` | string(20), índice, default `sin_firma` | Filtrar rápido y mostrar el badge |
| `firmado_at` | timestamp nullable | Fecha en que quedó firmado (digital o manual) |
| `firma_documento_id` | FK nullable a `solicitud_firma_documentos` | Último envío que lo firmó o lo está firmando. De ahí sale el contador |
| `hash_sha256` | string(64) nullable | Huella del archivo firmado que se publicó |

Estados (`EstadoFirmaOrigen`):

| Estado | Cuándo | ¿Se puede elegir en el asistente? |
|---|---|---|
| `sin_firma` | Nunca se envió, o se regeneró | ✅ |
| `en_firma` | Hay un envío en curso con este documento | ❌ |
| `firmado` | Todos los firmantes de **ese documento** firmaron y se publicó en el origen | ❌ (se ve con "Mostrar firmados") |
| `firmado_manual` | Se subió un escaneo firmado a mano | ❌ (se ve con "Mostrar firmados") |
| `rechazado` | El último envío lo rechazó | ✅, para reenviarlo corregido |

**El contador se calcula, no se guarda**: sale de las firmas del documento del envío (`firma_documento_id`), por ejemplo "En firma 1/3".

Todo se encapsula en el trait `TieneEstadoFirma` (casts, relación `documentoFirma()`, `progresoFirma()`, scopes), usado por los 3 modelos.

### 2. Quién cambia el estado

| Evento | Estado en el origen |
|---|---|
| `EnviarSolicitudFirma` (al enviar) | `en_firma` + `firma_documento_id` |
| Documento completado (última firma) | Se **publica** el firmado (§3) → `firmado` |
| `RechazarFirma` | `rechazado` (no se publica nada) |
| `CancelarSolicitudFirma` (documentos sin completar) | `sin_firma` |
| Regenerar el PDF (§5) | `sin_firma`; se limpian `firmado_at`, `hash_sha256` y `firma_documento_id` |
| Subir un escaneo firmado (§4) | `firmado_manual` |

> El estado es **por documento**, no por envío: en un envío con 3 documentos, cada uno se publica en su origen en cuanto **ese documento** se completa.

### 3. Publicar el firmado en el origen

El job `PublicarDocumentoFirmado` va en cola (`afterCommit`; no necesita credenciales). Hace esto:

1. Lee la última versión firmada del envío (disco `local`) y **verifica** su hash.
2. La escribe en el disco confidencial, en la **misma `url`** de la fila (`FUE-001-2026.pdf` se sobrescribe; no se crea una copia).
3. Marca la fila como `firmado`, con `firmado_at` y `hash_sha256`.

- **Es idempotente:** si el archivo ya tiene ese hash, no lo vuelve a escribir.
- **Si la fila de origen ya no existe, no falla:** el firmado sigue disponible en el envío.
- **El original sin firmar no se pierde:** queda en la carpeta privada del envío (`archivo_original`).

El comando `firmas:sincronizar-origen` recorre los documentos completados y vuelve a publicar los que falten. Sirve para el backfill y para reparar si falla la cola.

### 4. Subir un escaneo firmado a mano

Acción **"Subir firmado a mano"** junto a "Generar PDF" en Entregas, Recepciones, Notas, Celulares y Movimientos:

- Un solo PDF, con el límite de tamaño de *Configuración → Archivos*.
- Reemplaza el archivo en la **misma `url`** y marca `firmado_manual`.
- Exige el permiso `UploadSigned:{tipo}`.
- Si está `en_firma`, se bloquea.
- Si ya estaba firmado, aparece la misma advertencia que al regenerar (§5).

### 5. Una sola regla para reemplazar un PDF firmado

Cualquier camino que reemplace el archivo pasa por `ReemplazoDocumentoFirmado`:

- **"Generar PDF" / "Volver a generar PDF"** en los módulos de origen y **"Generar de nuevo"** en la vista de *PdfEquipos* / *PdfCelulares*: modal con advertencia y casilla.
- **"Guardar y generar PDF"** en el formulario de edición: guarda los cambios, pero **no** regenera un PDF firmado o en firma; avisa que se use "Volver a generar PDF" (que pide la confirmación).
- **"Subir firmado a mano"**.

*PdfEquipos* y *PdfCelulares* no tienen página de edición del archivo, así que no hay otro camino.

La regla tiene dos partes:

- **En firma:** bloqueado (como hasta ahora).
- **Firmado o firmado a mano:** modal con la lista de quién firmó y cuándo, y una casilla obligatoria *"Entiendo que se perderán las firmas de este documento"*. Si se confirma, se reemplaza y queda en el historial.

```
┌─ Regenerar documento firmado ─────────────────────────────────┐
│ ⚠️ FUE-0042.pdf está firmado digitalmente por:                 │
│    • Juan Pérez — 20/10/2026 10:15                             │
│    • Ana López  — 20/10/2026 11:02                             │
│                                                                │
│ Si lo regeneras, el archivo se reemplaza por uno nuevo SIN     │
│ firmas y tendrás que enviarlo a firmar otra vez.               │
│ La versión firmada queda guardada en el envío SF-2026-00042.   │
│                                                                │
│ ☐ Entiendo que se perderán las firmas de este documento        │
│                                    [Cancelar] [Regenerar]      │
└────────────────────────────────────────────────────────────────┘
```

La validación se repite en los servicios (`Generate*FormularioPdf`), no solo en la pantalla.

### 6. El paso "Documentos" del asistente

- **Categoría:** todas, Equipos (entrega, recepción, nota de salida, nota de recepción externa), Celulares (entrega, recepción), Movimientos (alta, baja).
- **Mostrar firmados:** apagado por defecto. Al encenderlo aparecen también los firmados, marcados y sin poder elegirse.
- **Lista:** del más reciente al más antiguo, con búsqueda por nombre de archivo. La búsqueda se hace en el servidor, así que no importa cuántos haya.
- **El servidor también valida:** al crear el envío se rechaza cualquier documento `en_firma`, `firmado` o `firmado_manual` (por ejemplo, desde dos pestañas a la vez).

> Se evaluó `ModalTableSelect` de Filament, pero trabaja con **un solo modelo y una relación**, y aquí los documentos salen de 3 tablas. Se usa el selector con búsqueda en servidor más los filtros.
>
> La categoría se reconoce por el nombre oficial del formulario. Un PDF de equipo **subido** a mano con otro nombre solo aparece en "Todas las categorías".

### 7. Acciones y badge en los módulos (fase 7)

- **Columna "Firma"** en las tablas de PDF y de los módulos de origen:
  - *Sin firma* (gris)
  - *En firma 1/3* (amarillo)
  - *Firmado* (verde)
  - *Firmado a mano* (verde)
  - *Rechazado* (rojo)

  Más un filtro por estado.
- **"Enviar a firmar"** en cada registro con PDF firmable, y como **acción masiva** en *PdfEquipos* y *PdfCelulares*. Abre el asistente con los documentos ya elegidos.

---

## Pendiente (fuera de esta fase)

- **Posiciones predeterminadas por plantilla** (los recuadros sobre las líneas de firma del formulario). Hoy se ofrece "Usar posiciones de la última vez", que cubre el caso de formularios repetidos.
- **Panel de uso de almacenamiento** (MB subidos al servidor).
- **Precargar firmantes** desde el modelo `Firmante`.
