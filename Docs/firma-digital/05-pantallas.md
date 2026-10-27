# 5. Pantallas

Todas se construyen en el panel Filament existente (`/admin`) y siguen las convenciones actuales: `Resources/{Plural}/` con `Pages/`, `Schemas/`, `Tables/` y `Actions/`, y las páginas custom con `HasPageShield`.

## Pantalla 1: Configuración de firmas (admin)

**Ruta:** `/admin/configuracion/firmas-digitales`. Es un resource `CertificadoFirmaResource` dentro de `ConfiguracionCluster`, junto a Archivos, Correo y Marca. El admin gestiona las firmas de **todos los usuarios, incluida la suya**.

**Tabla**

| Usuario | Titular (CN) | CI | Cargo en sello | Emisor | Vence | Estado |
|---|---|---|---|---|---|---|

- **Estado** como badge: 🟢 Activo · 🟡 Por vencer (< 30 días) · 🔴 Vencido · ⚪ Pendiente de activación · 🟠 Requiere reactivación · Reemplazado.
- **Filtros:** estado, vence en los próximos N días, área.

**Acciones**

| Acción | Qué hace |
|---|---|
| **Asignar firma** (cabecera) | `Select` de usuarios del módulo Usuarios **sin** firma activa, `FileUpload` del `.p12`, nombre y cargo en sello (prellenados desde el usuario). Queda en `pendiente_activacion` y se notifica al titular |
| **Renovar** | Sube el `.p12` del nuevo año. El actual sigue activo hasta que el titular active el nuevo |
| **Editar sello** | Cambia nombre y cargo en sello, con una vista previa del sello |
| **Reenviar aviso de activación** | Para firmas pendientes o que requieren reactivación |
| **Eliminar asignación** | Confirma, borra el archivo y deja el registro como `revocado`. Si el usuario tiene firmas pendientes, lo avisa antes |

**Página de ajustes: *Configuración → Firma digital*** (`FirmaSetting`, igual que la página Correo)

- 2FA al firmar (apagado por defecto).
- Intentos máximos y minutos de bloqueo.
- Plazo y recordatorio por defecto.

## Pantalla 1b: Mi firma digital (cada usuario)

**Ruta:** `/admin/mi-firma-digital`, desde el menú de usuario. Si hay algo pendiente, también aparece un banner en el dashboard.

- **Muestra:** estado, titular, emisor, vigencia y una vista previa de su sello.
- **Activar / Reactivar / Activar renovación:** asistente de 2 pasos ([ver seguridad](03-seguridad.md#activación-y-renovación-contraseña-del-p12)):

```
┌─ Activar mi firma digital ─────────────────────────────────────┐
│ Paso 1 de 2 · Contraseña de tu archivo de firma (.p12)         │
│                                                                │
│ 🔐 Es la contraseña que definiste al descargar tu firma de     │
│    Digicert (la misma que usas en Viafirma).                   │
│    NO es tu contraseña del sistema ICU.                        │
│                                                                │
│ [••••••••••••]                                [Validar →]      │
└────────────────────────────────────────────────────────────────┘
┌─ Activar mi firma digital ─────────────────────────────────────┐
│ ✅ Archivo validado: VLADIMIR ADALID CALDERON SARMIENTO         │
│    Digicert · vigente hasta 15/10/2027                         │
│                                                                │
│ Paso 2 de 2 · Confirma tu contraseña del sistema ICU           │
│ ✍️ Desde ahora firmarás con esta contraseña, la misma con la    │
│    que entras al sistema.                                      │
│                                                                │
│ [••••••••••••]                                [Activar]        │
└────────────────────────────────────────────────────────────────┘
```

- **Probar mi firma:** firma un PDF de ejemplo y lo descarga, para ver cómo queda el sello.

## Pantalla 2: Preparar envío a firmar

**Ruta:** `/admin/firmas/solicitudes/crear`. Es un resource `SolicitudFirmaResource`, entrada **Firma digital** del menú lateral (sin cluster), con la página Create como **Wizard**.

**Paso 1: Documentos (uno o varios).** Dos pestañas (`Tabs`):

- **Del sistema:** buscador con **selección múltiple** de documentos ya generados (altas/bajas, entregas, recepciones, notas). Los resultados salen de `PdfEquipo`, `PdfCelular`, `PdfMovimiento` y `ReciboEquipo`, con columnas tipo, número, fecha y persona. Se puede previsualizar en un modal.
- **Subir PDF:** `FileUpload` múltiple, con las validaciones de [seguridad](03-seguridad.md).

La lista final muestra todos los documentos elegidos, y se puede quitar cualquiera.

**Paso 2: Firmantes**

- `Select` múltiple que **solo** lista usuarios con firma **activa y vigente**. Si un documento viene del sistema y tiene `firmantes`, se precargan.
- Casilla **"Exigir orden de firma"** (apagada por defecto). Al marcarla, la lista de firmantes pasa a un `Repeater` **reordenable** para definir quién firma primero.

**Paso 3: Detalles**

- Asunto y mensaje para los firmantes.
- **Fecha límite** y **recordatorio cada N horas** (o "sin recordatorios"), prellenados con los valores por defecto de la configuración.

**Botón "Configurar firmas"** → abre el **canvas**.

### Canvas de posiciones

**Página:** `ConfigurarPosicionesFirma`, una página custom con vista Blade y un componente Alpine.

- **Pestañas, una por documento.** **PDF.js** renderiza cada página en un `<canvas>`, con scroll vertical y zoom (ajustar al ancho, 100 %, 150 %).
- Panel lateral con la lista de firmantes. Cada uno tiene un **color** y un **recuadro de tamaño fijo** (200 × 95 pt, escalado al zoom).
- Se **arrastra** el recuadro de cada firmante a su lugar (pointer events nativos, sin librería extra). El recuadro queda limitado a la página.
- El recuadro muestra una vista previa del sello con datos reales: logo, nombre, cargo y fecha de ejemplo.
- **"Copiar posiciones a los demás documentos"**, útil cuando todos son del mismo tipo de formulario.
- Validación: cada firmante debe tener posición en cada documento y los recuadros no deben superponerse.
- **Conversión de coordenadas:** la pantalla usa píxeles con origen arriba-izquierda y el PDF usa puntos con origen abajo-izquierda:

```js
const escala = viewport.scale;                 // px por punto
const xPdf = xPx / escala;
const yPdf = altoPaginaPt - (yPx / escala) - altoRecuadroPt;
```

- Hay que considerar `page.rotate` y el `CropBox` (PDF.js lo resuelve con `viewport.convertToPdfPoint`).
- **Guardar** envía `[{documento_id, firmante_id, pagina, x, y}]` al componente Livewire (`$wire.guardarPosiciones(...)`).
- **"Enviar a firmar"** llama a `/pdf/preparar` para cada documento, guarda la versión 0, pasa el envío a `en_curso` y notifica a **todos** los firmantes, o solo al primero si hay orden obligatorio.

**Recuerda las posiciones por tipo de documento:** se ofrece **"Usar posiciones de la última vez"** para formularios que siempre se firman en el mismo lugar.

### Lista de envíos

**Ruta:** `/admin/firmas/solicitudes`

- Columnas: código, asunto, nº de documentos, creador, progreso (`5/6 firmas`), estado y fecha límite.
- Pestañas: **Pendientes de mi firma** · **Enviados por mí** · **Todos** (con permiso).

## Pantalla 3: Firmar

**Ruta:** `/admin/firmas/solicitudes/{codigo}`. Es el destino de la campana y del correo.

```
┌──────────────────────────────────────────┬──────────────────────────────┐
│ [FUE-0042.pdf] [FUR-0042.pdf] [Nota…]    │ SF-2026-00042                │
│                                          │ Entrega de equipo – J. Pérez │
│        Visor PDF (versión actual)        │ Enviado por: Ana López       │
│    recuadro resaltado = tu posición      │ Mensaje: "Por favor firmar…" │
│                                          │ Fecha límite: 25/10/2026     │
│                                          │──────────────────────────────│
│                                          │ Firmantes (orden libre)      │
│                                          │ ✅ Juanito   20/10 10:15     │
│                                          │ ⏳ Pedro     pendiente       │
│                                          │ ✍️ Tú        pendiente       │
│                                          │──────────────────────────────│
│                                          │ [ Firmar este documento ]    │
│                                          │ [ Firmar todos (3) ]         │
│                                          │ [ Rechazar ]                 │
└──────────────────────────────────────────┴──────────────────────────────┘
```

- **Pestañas por documento.** Cada pestaña muestra el estado de firmas de ese documento.
- **Firmar este documento / Firmar todos:**
  - Modal con **la contraseña del sistema**, más el código 2FA si está activado en la configuración.
  - Casilla obligatoria: *"Declaro haber revisado el/los documento(s)"*.
  - Al terminar, muestra un resumen ([ver firma en lote](04-flujo-de-firma-y-bloqueo.md#firmar-uno-o-todos)).
- **Con orden obligatorio y si no le toca:** botones deshabilitados con el texto *"Esperando la firma de Pedro"*.
- **Si otro está firmando ese documento** en ese momento, aparece el mensaje *"Firma en proceso por otro usuario"* y la pantalla se refresca sola con `wire:poll.10s` mientras esté bloqueada.
- **Si su firma requiere reactivación:** en lugar de "Firmar" aparece **"Reactivar mi firma"**.
- **Rechazar:** modal con motivo obligatorio. Rechaza el documento que está viendo.
- **Documento completado:** botones **Ver** y **Descargar**, más el detalle de cada firma tomado de `/pdf/verificar`. Con varios documentos, también **Descargar todo (ZIP)**.
- **Cancelar envío:** visible solo para el creador y los admins.
- **Historial:** `ActivityTimelineAction`, la acción que ya existe en el proyecto.
