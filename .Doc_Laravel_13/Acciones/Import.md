# Acciones
## Acción de Importación

### Introducción

Filament incluye una acción que permite importar filas desde un archivo CSV. Al hacer clic en el botón de acción, se abre un modal que solicita al usuario un archivo. Una vez que lo sube, se le permite mapear cada columna del CSV con una columna real de la base de datos. Si alguna fila falla la validación, se compilará en un archivo CSV descargable para que el usuario lo revise después de importar el resto. También se puede descargar un archivo CSV de ejemplo con todas las columnas que pueden importarse.

Esta característica usa "job batches" y notificaciones de base de datos, así que debes publicar estas migraciones de Laravel. Además, debes publicar las migraciones de las tablas que Filament usa para almacenar información de importaciones:

```bash
php artisan make:queue-batches-table
php artisan make:notifications-table
php artisan vendor:publish --tag=filament-actions-migrations
php artisan migrate
```

Si deseas recibir notificaciones de importación en un panel, puedes habilitarlas en la configuración del panel.

- Si usas PostgreSQL, asegúrate de que la columna `data` en la migración de las notificaciones sea `json()`: `$table->json('data')`.
- Si tu modelo User usa UUIDs, asegúrate de que la columna `notifiable` de la migración de notificaciones use `uuidMorphs()`: `$table->uuidMorphs('notifiable')`.

Puedes usar `ImportAction` así:

```php
use App\Filament\Imports\ProductImporter;
use Filament\Actions\ImportAction;

ImportAction::make()
    ->importer(ProductImporter::class)
```

#### Cargando previsualización
#### Modal de la acción de importación

Si quieres añadir esta acción al encabezado de una tabla:

```php
use App\Filament\Imports\ProductImporter;
use Filament\Actions\ImportAction;
use Filament\Tables\Table;

public function table(Table $table): Table
{
    return $table
        ->headerActions([
            ImportAction::make()
                ->importer(ProductImporter::class)
        ]);
}
```

La clase "importador" debe ser creada para indicarle a Filament cómo importar cada fila del CSV.

Si tienes más de una `ImportAction` en el mismo lugar, debes darle a cada una un nombre único en el método `make()`:

```php
use Filament\Actions\ImportAction;

ImportAction::make('importProducts')
    ->importer(ProductImporter::class);

ImportAction::make('importBrands')
    ->importer(BrandImporter::class);
```

---

## Crear un importador

Para crear una clase importadora para un modelo, puedes usar el comando:

```bash
php artisan make:filament-importer Product
```

Esto creará una nueva clase en el directorio `app/Filament/Imports`. Ahora necesitas definir las columnas que pueden importarse.

### Generar automáticamente las columnas importables

Si deseas ahorrar tiempo, Filament puede generar automáticamente las columnas basándose en las columnas de la base de datos de tu modelo usando la opción `--generate`:

```bash
php artisan make:filament-importer Product --generate
```

---

## Definir columnas importables

Para definir las columnas a importar, sobrescribe el método `getColumns()` en tu clase importadora y retorna un array de objetos `ImportColumn`:

```php
use Filament\Actions\Imports\ImportColumn;

public static function getColumns(): array
{
    return [
        ImportColumn::make('name')
            ->requiredMapping()
            ->rules(['required', 'max:255']),
        ImportColumn::make('sku')
            ->label('SKU')
            ->requiredMapping()
            ->rules(['required', 'max:32']),
        ImportColumn::make('price')
            ->numeric()
            ->rules(['numeric', 'min:0']),
    ];
}
```

---

#### Personalizar la etiqueta de una columna importada

La etiqueta de cada columna se genera automáticamente a partir de su nombre, pero puede personalizarse con el método `label()`:

```php
use Filament\Actions\Imports\ImportColumn;

ImportColumn::make('sku')
    ->label('SKU')
```

#### Requerir que una columna del importador se mapee

Puedes usar `requiredMapping()` para hacer obligatoria la asignación de la columna del CSV:

```php
ImportColumn::make('sku')
    ->requiredMapping()
```

Si la columna es obligatoria en la base de datos, asegúrate además de tener las reglas de validación correctas. Si no se mapea una columna, no se validará.

Si permites que la importación cree y actualice registros, pero sólo necesitas que la columna sea obligatoria al crear, puedes usar `requiredMappingForNewRecordsOnly()`:

```php
ImportColumn::make('sku')
    ->requiredMappingForNewRecordsOnly()
```

Si el método `resolveRecord()` retorna una instancia de modelo que aún no está guardada, el mapeo es obligatorio en esa fila.

---

#### Validar datos del CSV

Agrega reglas de validación con el método `rules()`:

```php
ImportColumn::make('sku')
    ->rules(['required', 'max:32'])
```

Las filas que no pasen la validación no serán importadas y se guardarán en un CSV de “filas fallidas” para descargar después.

La función `rules()` permite tanto valores estáticos como funciones que reciban utilidades inyectadas.

Algunas utilidades disponibles:

- `$column`: instancia de la columna.
- `$data`: data procesada de la fila.
- `$importer`: instancia del importador.
- `$options`: opciones definidas al iniciar la importación.
- `$originalData`: datos originales antes del procesamiento.
- `$record`: modelo Eloquent de la fila actual.

---

### Cast de Estados (Casting State)

Antes de validar, los datos del CSV pueden ser casteados. Esto es útil para convertir cadenas a otros tipos, por ejemplo:

```php
ImportColumn::make('price')
    ->castStateUsing(function (string $state): ?float {
        if (blank($state)) {
            return null;
        }
        
        $state = preg_replace('/[^0-9.]/', '', $state);
        $state = floatval($state);
    
        return round($state, precision: 2);
    })
```

`castStateUsing()` admite utilidades inyectadas como `$column`, `$data`, `$importer`, `$options`, `$originalData`, `$originalState`, `$record`, `$state`.

Si una columna no es obligatoria y está vacía, no se castea.

Métodos de casteo rápidos:

```php
ImportColumn::make('price')
    ->numeric(); // Castea a float.

ImportColumn::make('price')
    ->numeric(decimalPlaces: 2); // Castea y redondea a 2 decimales.

ImportColumn::make('quantity')
    ->integer(); // Castea a entero.

ImportColumn::make('is_visible')
    ->boolean(); // Castea a booleano.
```

#### Mutar el estado después de castearlo

Puedes mutar el estado casteado usando una función en `castStateUsing()`:

```php
ImportColumn::make('price')
    ->numeric()
    ->castStateUsing(function (float $state): ?float {
        if (blank($state)) return null;
        return round($state * 100);
    });
```

Incluso puedes acceder al estado original antes de castear:

```php
ImportColumn::make('price')
    ->numeric()
    ->castStateUsing(function (float $state, mixed $originalState): ?float {
        // ...
    });
```

---

### Manejo de múltiples valores en una columna

El método `multiple()` castea los valores de una columna a un array usando un delimitador. Por ejemplo:

```php
ImportColumn::make('documentation_urls')
    ->multiple(',')
```

Si deseas castear cada item, puedes encadenar el casteo:

```php
ImportColumn::make('customer_ratings')
    ->multiple(',')
    ->integer()
```

#### Validar cada elemento de un array

Puedes usar `nestedRecursiveRules()`:

```php
ImportColumn::make('customer_ratings')
    ->multiple(',')
    ->integer()
    ->rules(['array'])
    ->nestedRecursiveRules(['integer', 'min:1', 'max:5'])
```

---

### Importar relaciones

Para importar relaciones `BelongsTo` o `BelongsToMany`, usa `relationship()`:

```php
ImportColumn::make('author')
    ->relationship()
```

En este ejemplo, la columna `author` se mapeará a `author_id`. El CSV debe tener las claves primarias (normalmente `id`).

Para `BelongsToMany`, usa además `multiple()`:

```php
ImportColumn::make('authors')
    ->relationship()
    ->multiple(',')
```

#### Personalizar cómo se resuelve la relación

Puedes buscar el registro por otra columna:

```php
ImportColumn::make('author')
    ->relationship(resolveUsing: 'email')
```

O usar múltiples columnas:

```php
ImportColumn::make('author')
    ->relationship(resolveUsing: ['email', 'username'])
```

O proveer una función personalizada:

```php
ImportColumn::make('author')
    ->relationship(resolveUsing: function (string $state) {
        return Author::query()
            ->where('email', $state)
            ->orWhere('username', $state)
            ->first();
    });
```

Si usas `BelongsToMany`, el `$state` será un array y debes retornar una colección.

---

### Marcar datos sensibles

Para evitar guardar columnas sensibles al fallar las filas de importación, usa `sensitive()`:

```php
ImportColumn::make('ssn')
    ->label('Número de Seguridad Social')
    ->sensitive()
    ->rules(['required', 'digits:9'])
```

---

### Personalizar cómo se llena un registro

Puedes usar `fillRecordUsing()`:

```php
ImportColumn::make('sku')
    ->fillRecordUsing(function (Product $record, string $state) {
        $record->sku = strtoupper($state);
    });
```

---

### Añadir texto de ayuda

Puedes añadir ayuda abajo del mapeo de columna:

```php
ImportColumn::make('skus')
    ->multiple(',')
    ->helperText('Una lista de SKUs separados por coma.')
```

---

### Actualizar registros existentes al importar

La clase generada de importador incluye el método `resolveRecord()`:

```php
public function resolveRecord(): ?Product
{
    // return Product::firstOrNew([
    //     'email' => $this->data['email'],
    // ]);
    return new Product();
}
```
Puedes editarlo para buscar y actualizar por un campo como `sku`:

```php
public function resolveRecord(): ?Product
{
    return Product::firstOrNew([
        'sku' => $this->data['sku'],
    ]);
}
```

#### Solo actualizar registros existentes

Si solo quieres actualizar y no crear, retorna null si no existe:

```php
public function resolveRecord(): ?Product
{
    return Product::where('sku', $this->data['sku'])->first();
}
```

Si deseas que la fila falle cuando no encuentra el registro:

```php
use Filament\Actions\Imports\Exceptions\RowImportFailedException;

public function resolveRecord(): ?Product
{
    $product = Product::where('sku', $this->data['sku'])->first();
    if (! $product) {
        throw new RowImportFailedException("No se encontró producto con SKU [{$this->data['sku']}].");
    }
    return $product;
}
```

---

### Ignorar valores en blanco

Por defecto, si una columna es mapeada pero está en blanco y no es obligatoria, se guarda como null. Para usar el valor original de la base si está en blanco, usa `ignoreBlankState()`:

```php
ImportColumn::make('price')
    ->ignoreBlankState()
```

---

### Uso de opciones de importación

Puedes agregar componentes extra al formulario para que el usuario ajuste el comportamiento:

```php
use Filament\Forms\Components\Checkbox;

public static function getOptionsFormComponents(): array
{
    return [
        Checkbox::make('updateExisting')
            ->label('Actualizar registros existentes'),
    ];
}
```

O pasar opciones estáticas en el método `options()`:

```php
ImportAction::make()
    ->importer(ProductImporter::class)
    ->options([
        'updateExisting' => true,
    ])
```

Dentro de la clase importadora, accede a `$this->options`.

---

### Mejorar el mapeo de columnas

Por defecto, Filament intenta adivinar el mapeo de columnas. Puedes dar más ejemplos usando `guess()`:

```php
ImportColumn::make('sku')
    ->guess(['id', 'number', 'stock-keeping unit'])
```

---

### Proveer ejemplos de CSV

Antes de subir un archivo, el usuario puede descargar un ejemplo. Puedes añadir ejemplos con `example()` o varios con `examples()`:

```php
ImportColumn::make('sku')
    ->example('ABC123')

ImportColumn::make('sku')
    ->examples(['ABC123', 'DEF456'])
```

Personaliza el encabezado con `exampleHeader()`:

```php
ImportColumn::make('sku')
    ->exampleHeader('SKU')
```

---

### Usar un modelo de usuario personalizado

Por defecto, la tabla `imports` tiene la columna `user_id` relacionada a la tabla `users`:

```php
$table->foreignId('user_id')->constrained()->cascadeOnDelete();
```

Puedes enlazar otro modelo authenticable:

```php
use App\Models\Admin;
use Illuminate\Contracts\Auth\Authenticatable;

$this->app->bind(Authenticatable::class, Admin::class);
```

O usar una tabla diferente:

```php
$table->foreignId('user_id')->constrained('admins')->cascadeOnDelete();
```

#### Relaciones polimórficas de usuario

Para asociar importaciones con varios modelos:

```php
$table->morphs('user');
```

Y en el boot de tu provider:

```php
use Filament\Actions\Imports\Models\Import;

Import::polymorphicUserRelationship();
```

---

### Limitar el número máximo de filas importables

Para prevenir sobrecarga:

```php
ImportAction::make()
    ->importer(ProductImporter::class)
    ->maxRows(100000)
```

`maxRows()` también acepta función.

Utilidades disponibles: `$action`, `$arguments`, `$data`, `$livewire`, `$model`, `$mountedActions`, `$record`, `$schema`, `$schemaComponent`, `$schemaComponentState`, `$schemaGet`, `$schemaOperation`, `$schemaSet`, `$schemaState`, `$selectedRecords`, `$table`.

---

### Modificar el tamaño de chunk en la importación

Por defecto, cada chunk es de 100 filas. Puedes cambiarlo:

```php
ImportAction::make()
    ->importer(ProductImporter::class)
    ->chunkSize(250)
```

---

### Cambiar el delimitador del CSV

Por defecto es coma. Puedes cambiarlo:

```php
ImportAction::make()
    ->importer(ProductImporter::class)
    ->csvDelimiter(';')
```

Solo se admite un caracter.

---

### Cambiar el desplazamiento de la cabecera

Si los encabezados de columna no están en la primera línea:

```php
ImportAction::make()
    ->importer(ProductImporter::class)
    ->headerOffset(5)
```

---

### Personalizar notificación de finalización

Puedes personalizar el título/cuerpo de la notificación sobrescribiendo los métodos `getCompletedNotificationTitle()` y `getCompletedNotificationBody()`:

```php
use Filament\Actions\Imports\Models\Import;

public static function getCompletedNotificationTitle(Import $import): string
{
    return 'Se ha completado la importación de productos';
}

public static function getCompletedNotificationBody(Import $import): string
{
    return $import->successful_rows . ' productos fueron importados.';
}
```

Para una personalización más avanzada, sobrescribe `modifyCompletedNotification()`:

```php
use Filament\Actions\Action;
use Filament\Notifications\Notification;

public static function modifyCompletedNotification(Notification $notification, Import $import): Notification
{
    $notification->icon('heroicon-o-shopping-bag');

    if ($import->getOptions()['sendWelcomeEmails'] ?? false) {
        $notification->actions([
            ...$notification->getActions(),
            Action::make('viewWelcomeEmails')
                ->url(route('emails.sent')),
        ]);
    }

    return $notification;
}
```

---

### Personalizar el Job de Importación

El job por defecto es `Filament\Actions\Imports\Jobs\ImportCsv`. Puedes reemplazarlo enlazando tu propia clase en el provider:

```php
use App\Jobs\ImportCsv;
use Filament\Actions\Imports\Jobs\ImportCsv as BaseImportCsv;

$this->app->bind(BaseImportCsv::class, ImportCsv::class);
```

O usando el método `job()`:

```php
ImportAction::make()
    ->importer(ProductImporter::class)
    ->job(ImportCsv::class)
```

---

### Personalizar cola y conexión del job

Sobrescribe los métodos en tu importador:

```php
public function getJobQueue(): ?string
{
    return 'imports';
}

public function getJobConnection(): ?string
{
    return 'sqs';
}
```

---

### Personalizar el middleware del job

Por defecto usa `WithoutOverlapping` para evitar ejecuciones concurrentes:

```php
use Illuminate\Queue\Middleware\WithoutOverlapping;

public function getJobMiddleware(): array
{
    return [
        (new WithoutOverlapping("import{$this->import->getKey()}"))->expireAfter(600),
    ];
}
```

Consulta la documentación de Laravel para más detalles.

---

### Personalizar los reintentos del job

Por defecto, reintenta durante 24 horas o hasta 5 fallos seguidos. Puedes cambiar esto:

```php
use Carbon\CarbonInterface;

public function getJobRetryUntil(): ?CarbonInterface
{
    return now()->addHours(12);
}
```

---

### Personalizar la espera (backoff) de reintentos

Por defecto: 1 minuto, 2, 5, después 10. Puedes cambiar este comportamiento:

```php
public function getJobBackoff(): int | array | null
{
    return [60, 120, 300, 600];
}
```

---

### Personalizar las etiquetas (tags) del job

Por defecto, usa el ID de la importación:

```php
public function getJobTags(): array
{
    return ["import{$this->import->getKey()}"];
}
```

---

### Personalizar el nombre del job batch

Por defecto es ninguno. Puedes agregarlo así:

```php
public function getJobBatchName(): ?string
{
    return 'product-import';
}
```

---

### Personalizar mensajes de validación

Puedes sobrescribir `getValidationMessages()` para cambiar los textos por defecto:

```php
public function getValidationMessages(): array
{
    return [
        'name.required' => 'El campo nombre no puede estar vacío.',
    ];
}
```

Para más info revisa los docs de Laravel.

---

### Personalizar atributos de validación

Para personalizar el nombre mostrado en mensajes de error:

```php
ImportColumn::make('name')
    ->validationAttribute('nombre completo')
```

---

### Personalizar la validación del archivo

Puedes agregar reglas Laravel para el archivo subido con `fileRules()`:

```php
ImportAction::make()
    ->importer(ProductImporter::class)
    ->fileRules([
        'max:1024',
        // o
        File::types(['csv', 'txt'])->max(1024),
    ]),
```

`fileRules()` también acepta función con utilidades inyectables.

---

## Ciclo de Vida (Lifecycle hooks)

Puedes ejecutar código en varios puntos sobrescribiendo métodos protegidos en tu importador, por ejemplo:

```php
protected function beforeSave(): void
{
    // Se ejecuta antes de guardar en BD.
}
```

Hooks disponibles:

- `beforeValidate()`: antes de validar la fila.
- `afterValidate()`: después de validar la fila.
- `beforeFill()`: antes de llenar el modelo.
- `afterFill()`: después de llenar el modelo.
- `beforeSave()`: antes de guardar.
- `afterSave()`: después de guardar.
- `beforeCreate()`: solo antes de crear nuevos registros.
- `afterCreate()`: solo después de crear nuevos registros.
- `beforeUpdate()`: solo antes de actualizar registros existentes.
- `afterUpdate()`: solo después de actualizar existentes.

En estos métodos puedes usar:

- `$this->data`: datos ya procesados.
- `$this->originalData`: datos originales del CSV.
- `$this->record`: modelo actual, si existe.
- `$this->options`: opciones de importación.

---

## Autorización

Por defecto, solo el usuario que inició la importación puede acceder al archivo CSV de errores. Para personalizar la lógica, crea una clase `ImportPolicy` y regístrala en tu AuthServiceProvider:

```php
use App\Policies\ImportPolicy;
use Filament\Actions\Imports\Models\Import;

protected $policies = [
    Import::class => ImportPolicy::class,
];
```

El método `view()` decide si puede acceder:

```php
public function view(User $user, Import $import): bool
{
    return $import->user()->is($user);
}
```

Nota: Si defines una policy, la lógica por defecto se elimina. Debes implementar la restricción manualmente si quieres mantenerla.

---

## Seguridad

### Autorización por registro

La importación **NO** valida políticas por-fila antes de crear o actualizar registros. Si el usuario tiene permiso para iniciar una importación, puede modificar cualquier registro permitido por el importador. Si necesitas validación por-fila, asegúrate de realizar las comprobaciones en hooks como `beforeCreate()` o `beforeUpdate()`.

Esto es **importante** si tu app deja que usuarios no confiables lancen importaciones.

---

### Inyección de fórmulas CSV

Si una fila no pasa la validación, se guarda en un CSV especial, tal y como fue subida, sin transformar los datos. Si el archivo contiene valores que empiezan con =, +, -, o @, pueden ser interpretados como fórmulas peligrosas por Excel/Google Sheets, lo que podría ser un riesgo si el CSV viene de una fuente no confiable.

Puedes advertir a los usuarios o sanitizar estos valores en los hooks del importador.

También puedes activar la protección de Filament: cualquier celda que inicie con un carácter peligroso será precedida por un apóstrofo (`'`), evitando así su ejecución como fórmula. Esto afecta a todo el archivo CSV de errores, aunque la columna no esté mapeada.

Para habilitar la protección en un importador:

```php
use Filament\Actions\Imports\Importer;

class ProductImporter extends Importer
{
    protected static bool $shouldPreventFormulaInjection = true;
}
```

Para habilitarlo globalmente desde un ServiceProvider:

```php
use Filament\Actions\Imports\Importer;

Importer::preventFormulaInjection();
```

**Nota:** La protección está desactivada por defecto porque puede modificar datos válidos legítimos (por ejemplo, -5 o +44). Actívala solo si los archivos pueden ser subidos por fuentes no seguras.