# Archivos, Media Library y preparación para la nube

Gestión unificada de **dónde** y **cómo** se guardan los archivos del panel: disco, prefijos,
límites, optimización de imágenes y catálogo de colecciones/carpetas.

**Alcance de esta entrega**

| Sí | No |
|---|---|
| Imágenes de **mantenimiento** (`cap_antes`, `cap_despues`) vía Spatie Media Library | PDF de equipo/celular/movimiento/recibo en Media Library |
| Logos y favicon de **marca** vía Media Library | Excel de importación (temporales de Filament Importer) |
| Página **Configuración → Archivos** + `ARCHIVOS_DISK` | Cambiar `Generate*FormularioPdf` a `addMediaFromString` |
| PDF siguen en columna `url`, pero leen disco/directorio/máximo desde config | |

Paquetes: `filament/spatie-laravel-media-library-plugin` **^5** sobre `spatie/laravel-medialibrary` **^11**.
Requiere extensión **GD** (o Imagick) en el contenedor Sail para conversiones.

La marca (logo/favicon) se documenta también, en detalle de ciclo de vida, en
[`../Apariencia-Tema-y-Marca/README.md`](../Apariencia-Tema-y-Marca/README.md) (§8.1–8.2).
Este documento describe el **subsistema completo de archivos en ICU**.

---

## 1. Resumen

| Pieza | Rol |
|---|---|
| `config/archivos.php` | Defaults: disco, prefijo, umbral WebP, límites, catálogo |
| Tabla `media_settings` | Una sola fila con overrides (página Archivos) |
| `App\Models\MediaSetting` | Modelo singleton (`MediaSetting::actual()`) |
| `App\Services\Archivos\ArchivosService` | Resuelve config + fila, cachea |
| `App\Observers\MediaSettingObserver` | Invalida caché al guardar |
| Tabla `media` | Filas de Spatie Media Library |
| `App\Models\Concerns\HasIcuMedia` | Trait: conversiones, URLs, fallback a columna heredada |
| `App\Media\IcuPathGenerator` | Rutas `{prefijo}/{colección}/{Modelo}/{id}/{media_id}/` |
| Página `Archivos` | UI en cluster Configuración (sort 3) |
| `icu:migrar-imagenes-a-media` | Pasa rutas legacy de columnas a Media Library |

```text
Imágenes (mantenimiento / marca)
  SpatieMediaLibraryFileUpload
       └─► tabla media + disco (ArchivosService::disco())
              ├─ original
              ├─ conversions/thumb
              └─ conversions/webp  (si tamaño > umbral; no SVG/ICO)

PDFs (sin Media Library)
  FileUpload::make('url')
       └─► columna url en el modelo
              └─► disco/directorio/max desde ArchivosService
```

---

## 2. Qué se ve en el panel

Acceso: **menú de usuario → Apariencia** abre el cluster Configuración (oculto del sidebar).
La sub-navegación izquierda lista:

| Página | Ruta | Contenido |
|---|---|---|
| Tema y colores | `/admin/configuracion/apariencia` | Temas y colores |
| Marca e identidad | `/admin/configuracion/marca` | Logos y favicon (Media Library) |
| **Archivos** | `/admin/configuracion/archivos` | Disco, prefijo, optimización, límites, catálogo |

La página **Archivos** usa `HasPageShield` (permiso de página Shield). Tras desplegar, regenerar
permisos si hace falta (`shield:generate` / asignar al rol).

Secciones de la página Archivos:

1. **Dónde se guardan** — disco (`public` / `local` / `s3`), prefijo, visibilidad.
2. **Optimización de imágenes** — umbral WebP (KB), calidad, ancho del thumb.
3. **Límites de subida (KB)** — capturas, PDF, logo, favicon.
4. **Catálogo** (solo lectura) — colecciones de imagen y carpetas PDF con la ruta efectiva.

---

## 3. Configuración

### 3.1 Variables de entorno

En `.env` / `.env.example`:

```env
ARCHIVOS_DISK=public
ARCHIVOS_PREFIJO=icu
```

Opcionales (también editables en la página si hay fila en `media_settings`):

| Variable | Default | Uso |
|---|---|---|
| `ARCHIVOS_DISK` | `public` | Disco Laravel (`filesystems.php`) |
| `ARCHIVOS_PREFIJO` | `icu` | Prefijo de Media Library |
| `ARCHIVOS_UMBRAL_WEBP_KB` | `500` | A partir de este tamaño se genera `webp` |
| `ARCHIVOS_CALIDAD_WEBP` | `82` | Calidad de la conversión |
| `ARCHIVOS_THUMB_ANCHO` | `400` | Ancho del thumb en px |
| `ARCHIVOS_MAX_IMAGEN_KB` | `5120` | Capturas de mantenimiento |
| `ARCHIVOS_MAX_PDF_KB` | `10240` | FileUpload de PDF |
| `ARCHIVOS_MAX_LOGO_KB` | `2048` | Logos |
| `ARCHIVOS_MAX_FAVICON_KB` | `512` | Favicon |
| `ARCHIVOS_VISIBILIDAD` | `public` | `public` / `private` |

### 3.2 Prioridad de resolución

`ArchivosService` cachea en `archivos.resueltos` (forever):

1. Valor de la fila `media_settings` (si existe y el campo no es null).
2. Si no, `config/archivos.php` / `env`.

Al guardar la página Archivos, el observer llama a `olvidar()`.

### 3.3 Preparación para S3 / nube

Laravel ya define el disco `s3` en `config/filesystems.php`. Para apuntar a la nube **sin
reescribir modelos**:

```env
ARCHIVOS_DISK=s3
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=...
# R2 / MinIO / Spaces: AWS_ENDPOINT=... y a menudo AWS_USE_PATH_STYLE_ENDPOINT=true
```

O elegir `s3` en **Configuración → Archivos**.

Notas:

- Los archivos **ya subidos** siguen en el disco grabado en cada fila `media` (o en la ruta PDF).
  Cambiar el disco por defecto solo afecta a **archivos nuevos**.
- Para PDFs privados en S3 conviene `temporaryUrl()`; logos/favicon suelen ser públicos o CDN.
- FilePond/Filament necesita CORS correcto si el preview sale de otro dominio.
- Las conversiones de imagen usan la cola (`QUEUE_CONNECTION`); el worker debe tener las mismas
  credenciales AWS.

---

## 4. Media Library (solo imágenes)

### 4.1 Modelos y colecciones

Trait `HasIcuMedia` (`HasMedia` + `InteractsWithMedia` + conversiones/helpers):

| Modelo | Colección | Tipo | `singleFile` |
|---|---|---|---|
| `MantenimientoEquipo` | `cap_antes`, `cap_despues` | PNG/JPEG | sí |
| `AppearanceSetting` | `logo_claro`, `logo_oscuro`, `favicon` | imagen / ico / svg | sí |

Helpers (con fallback a la columna path si aún no hay media):

- `hasStoredImage($colección)`
- `storedImageUrl($colección, $conversion = '')`
- `storedImageFilename($colección)`
- `purgarArchivoHeredado($colección)` — borra la ruta legacy del disco y limpia la columna

Soft deletes: Spatie solo borra media en **force delete** (no en soft delete), coherente con
restaurar un mantenimiento.

### 4.2 Path generator

`App\Media\IcuPathGenerator`, registrado en `AppServiceProvider::boot()`:

```text
{prefijo}/{coleccion}/{Modelo}/{id}/{media_id}/archivo.ext
{prefijo}/{coleccion}/{Modelo}/{id}/{media_id}/conversions/...
{prefijo}/{coleccion}/{Modelo}/{id}/{media_id}/responsive/...
```

Ejemplo: `icu/cap_antes/MantenimientoEquipo/12/45/antes_ST100001_2026-07-20.png`

### 4.3 Conversiones

En `HasIcuMedia::registerMediaConversions()`:

| Conversión | Cuándo | Notas |
|---|---|---|
| `thumb` | Siempre en colecciones raster (PNG/JPEG/WebP) | Ancho desde config |
| `webp` | Si `$media->size > umbral` | Calidad desde config; **original intacto** |

No se convierten: SVG, ICO, no-imágenes, ni la colección `favicon`.

En producción las conversiones van por cola (config Spatie + `QUEUE_CONNECTION=redis`).
En tests (`QUEUE_CONNECTION=sync`) se ejecutan al subir.

Infolists/tablas de capturas usan `SpatieMediaLibraryImageEntry` / columna con `conversion('thumb')`.

### 4.4 Formularios Filament

- Mantenimiento: `SpatieMediaLibraryFileUpload` + `collection('cap_antes'|'cap_despues')` +
  `getUploadedFileNameForStorageUsing` (`antes_{tag}_{fecha}.ext`).
- Marca: mismo componente; el registro debe existir (`id`) antes de adjuntar. Tras
  `getState()` / `save()` se llama `saveRelationships()` porque el campo está
  `dehydrated(false)`.
- Las columnas `cap_*` / `logo_*` / `favicon` dejan de escribirse en altas nuevas; se mantienen
  nullable como fallback legacy.

---

## 5. PDFs (sin Media Library)

Siguen el patrón anterior: columna `url` + helpers `hasStoredDocument()` /
`storedDocumentUrl()` / `downloadStoredDocument()` / `deleteStoredDocument()`.

Cambio de esta entrega: el disco y la carpeta **ya no están hardcodeados** a `public` + carpeta
fija. Cada modelo expone:

```php
PdfEquipo::documentDisk();       // ArchivosService::disco()
PdfEquipo::documentDirectory();  // config archivos.pdf.pdf_equipos.directorio
```

Igual para `PdfCelular`, `PdfMovimiento`, `ReciboEquipo`.

Los `FileUpload` de los forms leen:

- `->disk(fn () => …::documentDisk())`
- `->directory(fn () => …::documentDirectory())`
- `->maxSize(fn () => app(ArchivosService::class)->maxPdfKb())`
- `->visibility(fn () => app(ArchivosService::class)->visibilidad())`

Los generadores Browsershot (`Generate*FormularioPdf`) usan `documentDisk()` al hacer `Storage::put`.

Catálogo en config (solo metadata / UI):

| Clave | Directorio por defecto |
|---|---|
| `pdf_equipos` | `pdf-equipos` |
| `pdf_celulares` | `pdf-celulares` |
| `pdf_movimientos` | `pdf-movimientos` |
| `recibo_equipos` | `recibo-equipos` |

---

## 6. Migración de imágenes legacy

Comando:

```bash
vendor/bin/sail artisan icu:migrar-imagenes-a-media
vendor/bin/sail artisan icu:migrar-imagenes-a-media --dry-run
vendor/bin/sail artisan icu:migrar-imagenes-a-media --disk=public
```

Comportamiento:

1. Recorre `MantenimientoEquipo` (`cap_antes`, `cap_despues`) y `AppearanceSetting`
   (`logo_claro`, `logo_oscuro`, `favicon`).
2. Si la colección ya tiene media → omite (idempotente).
3. Si la columna tiene ruta y el archivo existe → `addMediaFromDisk(...)->preservingOriginal()`.
4. Si el archivo no existe o Spatie no puede procesarlo (p. ej. no es imagen válida) → avisa y
   cuenta como ausente; no aborta el resto.

Tras migrar, el archivo original en la ruta legacy **sigue** hasta que se purgue (reemplazo en
Marca/mantenimiento o `purgarArchivoHeredado`). Las columnas **no se dropean** en esta entrega.

---

## 7. Tablas

| Migración | Tabla |
|---|---|
| `2026_08_26_154000_create_media_settings_table` | `media_settings` |
| `2026_08_26_154100_create_media_table` | `media` (Spatie) |

```bash
vendor/bin/sail artisan migrate --no-interaction
```

---

## 8. Archivos del código

**Nuevos**

```text
config/archivos.php
app/Models/MediaSetting.php
app/Services/Archivos/ArchivosService.php
app/Observers/MediaSettingObserver.php
app/Models/Concerns/HasIcuMedia.php
app/Media/IcuPathGenerator.php
app/Filament/Clusters/Configuracion/Pages/Archivos.php
resources/views/filament/clusters/configuracion/pages/archivos.blade.php
resources/views/filament/clusters/configuracion/partials/archivos-catalogo.blade.php
app/Console/Commands/MigrarImagenesAMediaCommand.php
database/migrations/2026_08_26_154000_create_media_settings_table.php
database/migrations/2026_08_26_154100_create_media_table.php
tests/Feature/Filament/ArchivosPageTest.php
tests/Feature/Console/MigrarImagenesAMediaCommandTest.php
tests/Feature/Media/ImageConversionTest.php
tests/Unit/Services/ArchivosServiceTest.php
```

**Modificados (principales)**

```text
app/Providers/AppServiceProvider.php          # ArchivosService, observer, path_generator
app/Models/MantenimientoEquipo.php            # HasMedia + colecciones
app/Models/AppearanceSetting.php              # HasMedia + colecciones + clearBrandMedia()
app/Filament/.../MantenimientoEquipoForm.php  # SpatieMediaLibraryFileUpload
app/Filament/.../MantenimientoEquipoInfolist.php
app/Filament/Clusters/Configuracion/Pages/Marca.php
app/Services/Apariencia/AparienciaService.php # URLs desde media
app/Models/PdfEquipo.php | PdfCelular.php | PdfMovimiento.php | ReciboEquipo.php
app/Filament/.../Pdf*Form.php | ReciboEquipoForm.php
.env.example                                  # ARCHIVOS_DISK, ARCHIVOS_PREFIJO
phpunit.xml                                   # ARCHIVOS_DISK=public
```

---

## 9. Pruebas

```bash
vendor/bin/sail artisan test --compact \
  tests/Feature/Filament/MantenimientoEquipoCapturaUploadTest.php \
  tests/Feature/Models/MantenimientoEquipoDeletesStoredImagesTest.php \
  tests/Feature/Filament/MarcaPageTest.php \
  tests/Feature/Filament/ArchivosPageTest.php \
  tests/Feature/Console/MigrarImagenesAMediaCommandTest.php \
  tests/Feature/Media/ImageConversionTest.php \
  tests/Unit/Services/ArchivosServiceTest.php \
  tests/Unit/Services/AparienciaServiceTest.php
```

Cubren: subida a media con nombres esperados, soft/force delete, reemplazo `singleFile`,
fallback de columna legacy, página Archivos + servicio, migración idempotente (con **PNG reales**,
no texto), conversiones `thumb`/`webp` según umbral, y resolución de apariencia/marca.

---

## 10. Operación rápida

```bash
# Tras pull
vendor/bin/sail up -d
vendor/bin/sail artisan migrate --no-interaction
# Comprobar GD
vendor/bin/sail php -m | grep -i gd

# Migrar logos/capturas ya guardados como path
vendor/bin/sail artisan icu:migrar-imagenes-a-media --dry-run
vendor/bin/sail artisan icu:migrar-imagenes-a-media

# Cola (conversiones en prod)
vendor/bin/sail artisan queue:work
```

Cambiar a S3 más adelante: configurar `AWS_*`, poner `ARCHIVOS_DISK=s3` (o en la página Archivos)
y planificar un job aparte para copiar históricos si hace falta.
