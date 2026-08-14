# Documentación

- [✨ Características](#características)
- [📦 Instalación](#instalación)
    - [Publicar Configuración y migraciones](#publicar-configuración-y-migraciones)
- [🚀 Uso](#uso)
    - [Uso básico en el recurso de filamento](#uso-básico-en-el-recurso-de-filamento)
    - [Configuración avanzada](#configuración-avanzada)
    - [Uso independiente](#uso-independiente)
- [⚙️ Configuración](#configuración)
    - [Opciones de configuración](#opciones-de-configuración)
- [📋 Pasos de importación](#pasos-de-importación)
    - [Paso 1: Subir](#paso-1-subir)
    - [Paso 2: Columnas de mapas](#paso-2-columnas-de-mapas)
        - [Fusión de localidades (campos traducibles)](#fusión-de-localidades-campos-traducibles)
        - [Mapeo de relaciones](#mapeo-de-relaciones)
    - [Paso 3: Revisar y validar](#paso-3-revisar-y-validar)
    - [Paso 4: Importar](#paso-4-importar)
- [🔗 Vinculación de relaciones](#vinculación-de-relaciones)
    - [Cómo funciona la resolución de relaciones](#cómo-funciona-la-resolución-de-relaciones)
- [🔃 Upsert (Match & Merge)](#upsert-match--merge)
- [🌍 Traducible Spatie y Fusión de Localidades](#traducible-spatie-y-fusión-de-localidades)
    - [Detección automática](#detección-automática)
    - [Modo de fusión de localidades](#modo-de-fusión-de-localidades)
    - [Etiquetas personalizadas de campos de importación](#etiquetas-personalizadas-de-campos-de-importación)
- [🧠 Soporte para Modelos Inteligentes y Valores Protegidos](#soporte-para-modelos-inteligentes-y-valores-protegidos)
- [🛠️ Personalización](#personalización)
    - [Ancho modal personalizado](#ancho-modal-personalizado)
    - [Configuración de la cola](#configuración-de-la-cola)
    - [Reglas de validación personalizadas](#reglas-de-validación-personalizadas)
- [📝 Requisitos](#requisitos)
- [📄 Licencia](#licencia)
- [🤝 Contribuciones](#contribuciones)
- [📧 Apoyo](#apoyo)
- [Capturas de pantalla](#capturas-de-pantalla)

---

Un potente **asistente de importación CSV y Excel** con colas para Filament, mapeo inteligente de columnas, enlace de relaciones, soporte para Spatie Translatable, fusión local, validación dinámica de esquemas y procesamiento en segundo plano para 100K+ filas.

**Soporte para:**  
- Modo Oscuro  
- PHP 8.2+  
- Laravel 10+  
- Filament 4.x/5.x  
- Licencia: MIT

---

## ✨ Características

- Asistente de importación de **4 pasos**: Subir, mapear columnas, revisar datos e importar dentro de un modal de Filament.
- **Interfaz de Mapeado Inverso:** Los campos del modelo se muestran primero con desplegables de columnas CSV.
- **Mapeado automático inteligente:** Asocia automáticamente encabezados CSV a campos de modelo usando coincidencia _snake_case_ (ej: `Title` → `title`, `CategoryId` → `category_id`).
- **Enlace de Relaciones:** Vincula o crea registros relacionados (BelongsTo) con manejo inteligente de IDs con autoincremento.
- **Soporte traducible de Spatie:** Detección de trait `HasTranslations` y columnas JSON, soporte para consultas por localización.
- **Fusión de localidades:** Combina varias columnas de idioma (ej: `title_en`, `title_ar`) en un solo campo traducible JSON.
- **Búsqueda de relaciones por localidad:** Encuentra o crea modelos relacionados usando valores traducidos.
- **Soporte Upsert:** Actualiza registros existentes por claves configurables para evitar duplicados.
- **Procesamiento por colas:** Ejecución por lotes con seguimiento de progreso en tiempo real para grandes datasets.
- **Eloquent Event Firing:** Dispara eventos y observadores de Eloquent (`boot()`, `save()`, timestamps).
- **Validación dinámica:** Reglas generadas automáticamente desde los tipos de columna de la base de datos.
- **Revisión de errores previa:** Validación online y descarga de errores antes de importar.
- **Listo para multi-tenancy:** Scoped a equipos/inquilinos, incluido en los trabajos de cola.
- **Modo independiente:** Usable como componente Livewire fuera de paneles Filament.
- **Modo Oscuro:** Soporte total para temas dark.
- **Configuración de cola personalizada:** Conexión y nombre de cola ajustables.
- **Recorte automático de columnas vacías en Excel.**

---

## 📦 Instalación

```bash
composer require waad/filament-import-wizard
```

## Publicar Configuración y migraciones

```bash
# Publicar archivo de configuración (opcional)
php artisan vendor:publish --tag="filament-import-wizard-config"

# Publicar y correr migraciones
php artisan vendor:publish --tag="filament-import-wizard-migrations"
php artisan migrate
```

> ⚠️ Si hay errores en CSS, prueba:  
> ```bash
> php artisan filament:assets
> ```

---

## 🚀 Uso

### Uso básico en el recurso de filamento

Agrega la acción de importación a la tabla de tu recurso Filament. Ejemplo (`app/Filament/Resources/Posts/Pages/ListPosts.php`):

```php
use Waad\FilamentImportWizard\Actions\ImportWizardAction;

protected function getHeaderActions(): array
{
    return [
        ImportWizardAction::make(),
    ];
}
```

### Configuración avanzada

```php
ImportWizardAction::make()
    ->forModel(\App\Models\Product::class)
    ->chunkSize(500)               // Procesar 500 filas por job
    ->enableUpsert(true)           // Actualizar registros existentes
    ->upsertKeys(['sku'])          // Coincidencia por el campo SKU
    ->queueConnection('redis')     // Conexión de cola personalizada
    ->queueName('imports');        // Nombre de cola personalizado
```

### Uso independiente

Usa el asistente fuera de los paneles de Filament mediante Livewire:

```blade
@livewire('filament-import-wizard', ['modelClass' => \App\Models\Product::class])
```

---

## ⚙️ Configuración

Archivo: `config/filament-import-wizard.php`

```php
return [
    'modal_width' => Width::Full,        // Ancho del modal (Width enum de Filament)
    'chunk_size' => 1000,                // Filas por job de la cola
    'default_csv_delimiter' => ',',      // Delimitador CSV (, ; [tab])
    'queue_connection' => null,          // Conexión de cola (null = default Laravel)
    'queue_name' => null,                // Nombre de cola (null = default)
];
```

### Opciones de configuración

| Opción                 | Default         | Descripción                                             |
|------------------------|----------------|---------------------------------------------------------|
| `modal_width`          | `Width::Full`  | Ancho del modal del asistente de importación            |
| `chunk_size`           | `1000`         | Número de filas procesadas por trabajo de cola          |
| `default_csv_delimiter`| `,`            | Delimitador CSV por defecto                             |
| `queue_connection`     | `null`         | Conexión de cola (`null` = Laravel default)             |
| `queue_name`           | `null`         | Nombre específico de la cola (`null` = cola por defecto)|

---

## 📋 Pasos de importación

### Paso 1: Subir

Sube tu archivo CSV o Excel. Formatos soportados:
- CSV (`.csv`) — con detección automática UTF-8 BOM
- Excel (`.xlsx`, `.xls`) — las columnas vacías finales se recortan automáticamente

### Paso 2: Columnas de mapas

El mapeo presenta primero los campos del modelo, en un diseño limpio tipo tabla:

- **Campos del modelo:** Cada campo con su etiqueta, nombre, y un desplegable para seleccionar la columna CSV
- **Relaciones:** Cada relación (BelongsTo) con insignias FK/PK y desplegable buscable
- **Columnas no mapeadas:** Las columnas CSV no utilizadas aparecen abajo

#### Fusión de localidades (campos traducibles)

Para columnas traducibles/JSON, activa el interruptor de Fusión de Traducción para dividir un campo en múltiples mapeos por localidad:

```
Title (translatable) → Merge Translation ON
  [en] → title_en
  [ar] → title_ar
  [fr] → title_fr
```

La localización se detecta automáticamente del header (ej: `title_en`, `title_ar`), o se puede introducir manualmente.  
Esto almacena el dato como JSON:  
```json
{"en": "Hello", "ar": "مرحبا"}
```

#### Mapeo de relaciones

Para relaciones **BelongsTo**:

```
Category → [Selecciona la columna CSV...]
           FK: category_id  PK: id
           [Buscar campo...]  [name ▼]
```

Las relaciones permiten detectar IDs automáticos (en CSV como numéricos) y respaldo por campos string.

### Paso 3: Revisar y validar

- Vista previa de las primeras 100 filas mapeadas
- Ver errores de validación por fila con etiquetas de error
- Reglas generadas auto por el esquema DB (tipos, nullable, unique)
- Relaciones foreign key saltan restricción de tipo en validación (etiquetas resueltas luego)
- Configura upsert (activar/desactivar, llaves)

### Paso 4: Importar

- Procesamiento por cola en segundo plano con encuestas cada 5 s
- La cola de sync ejecuta jobs en caliente para pruebas
- **Modo Eloquent (sin upsert):** Se guardan uno a uno con eventos y observadores (`save()`, `boot()`)
- **Modo upsert:** Bulk `upsert()`, con fallback a batch insert si falla
- Seguimiento y descarga de registros de error
- Resumen con conteo animado de éxitos/errores, y estado (`completed_with_errors`)

---

## 🔗 Vinculación de relaciones

Registros vinculados durante la importación, ejemplo:

```php
// CSV: "Category Name" → Relación: category → Campo: name
```

Tipos permitidos:
- **BelongsTo** — Con resolución FK/PK automática

### Cómo funciona la resolución de relaciones

- Si la clave propietaria es **auto-incremental**:
    - Valores numéricos → buscar/crear por ID
    - Valores string → buscar/crear por columna string adecuada (ej. name)
- Si es columna distinta a ID → Consulta por ese nombre de columna
- Resolución traducible para campos JSON por `whereJsonContains()`

---

## 🔃 Upsert (Match & Merge)

Actualiza registros existentes en vez de crear duplicados:

```php
ImportWizardAction::make()
    ->forModel(\App\Models\User::class)
    ->enableUpsert(true)
    ->upsertKeys(['email']);  // Coincidencia por email
```

El asistente:

- Busca registros existentes por las llaves dadas
- Actualiza coincidencias con `upsert()`
- Inserta nuevos solo si no hay match
- Si falla upsert, usa insertos por chunks

---

## 🌍 Traducible Spatie y Fusión de Localidades

### Detección automática

El asistente detecta automáticamente:

- Modelos que usan el trait `Spatie\Translatable\HasTranslations`
- Columnas `JSON/JSONB` en el esquema de la BD

### Modo de fusión de localidades

Activa Merge Translation en cualquier campo traducible para mapear por localidad:

| CSV Header | Maps To                     |
|------------|-----------------------------|
| title_en   | title con localidad `en`    |
| title_ar   | title con localidad `ar`    |
| title_fr   | title con localidad `fr`    |

Esto se guarda como una única columna JSON:

```json
{"en": "valor", "ar": "قيمة", "fr": "valeur"}
```

### Etiquetas personalizadas de campos de importación

Agrega un método en tu modelo para nombres personalizados:

```php
class Product extends Model
{
    public function getImportFieldLabel(string $field): string
    {
        return match($field) {
            'sku' => 'SKU (Stock Keeping Unit)',
            'price_in_cents' => 'Price (cents)',
            default => Str::title(str_replace(['_', '.'], ' ', $field)),
        };
    }
}
```

---

## 🧠 Soporte para Modelos Inteligentes y Valores Protegidos

- El wizard soporta modelos con `$guarded = []` (sin fillable definido)
- Resolución fillable dinámica: usa `Schema::getColumnListing()` si getFillable está vacío
- Todas las columnas permitidas: si fillable vacío, cualquier columna CSV puede mapear a db
- Foreign key resolutions: si el modelo no está protegido, ignora fillable para FKs

---

## 🛠️ Personalización

### Ancho modal personalizado

```php
use Filament\Support\Enums\Width;

ImportWizardAction::make()
    ->forModel(\App\Models\Product::class)
    ->setModalWidth(Width::ExtraLarge);
```

### Configuración de la cola

Global en config:

```php
// config/filament-import-wizard.php
return [
    'queue_connection' => 'redis',
    'queue_name' => 'imports',
];
```

O por acción:

```php
ImportWizardAction::make()
    ->forModel(\App\Models\Product::class)
    ->queueConnection('redis')
    ->queueName('imports');
```

### Reglas de validación personalizadas

Agrega un método en tu modelo:

```php
class Product extends Model
{
    public function getImportRules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:50'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
```

Si no existe el método `getImportRules()`, las reglas se generan aut. por el esquema de la bd.

---

## 📝 Requisitos

- **PHP**: 8.2+
- **Laravel**: 10+
- **Filament**: 4.x o 5.x

---

## 📄 Licencia

Licencia MIT.  
Ver archivo LICENSE para más información.