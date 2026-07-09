# Gestión de Relaciones en FilamentPHP

FilamentPHP ofrece múltiples formas de gestionar relaciones en tu aplicación. La herramienta adecuada depende del tipo de relación que estés gestionando y de la interfaz que busques.

---

## Gestores de Relaciones

Los **gestores de relaciones** son tablas interactivas que permiten a los administradores listar, crear, adjuntar, asociar, editar, desacoplar, disociar y eliminar registros relacionados sin salir de la página de **Editar** o **Ver** del recurso.

### Relaciones Compatibles

- `HasMany`
- `HasManyThrough`
- `BelongsToMany`
- `MorphMany`
- `MorphToMany`

---

## Lista de Casillas de Verificación

Permite a los usuarios elegir entre registros existentes o crear uno nuevo usando un `select`. También puedes añadir un botón para crear un nuevo registro dentro de un modal, sin salir de la página.

### Relaciones Compatibles

- `BelongsTo`
- `MorphTo`
- `BelongsToMany`

### Características

- Los usuarios pueden seleccionar **múltiples opciones** (no solo una).
- Los registros se añaden automáticamente a la tabla dinámica al enviar el formulario.
- Puedes cambiar el desplegable de selección múltiple por una **lista de casillas de verificación** (`CheckboxList`).

---

## Repetidores

Los **repetidores** son componentes de formulario estándar que pueden renderizar un conjunto repetible de campos infinitamente. Se conectan a una relación, por lo que los registros se leen, crean, actualizan y eliminan automáticamente de la tabla correspondiente.

### Relaciones Compatibles

- `HasMany`
- `MorphMany`

### Consideraciones

- Ideal si el modelo relacionado tiene **pocos campos**. De lo contrario, el formulario puede volverse muy largo.
- Viven dentro del esquema principal del formulario y pueden usarse en páginas de recursos o anidados dentro de modales de acción.

---

## Componentes de Formulario de Disposición

Todos los componentes de diseño de formulario (como **Cuadrícula**, **Sección**, **Campo**, etc.) tienen un método `relationship()`. Al usarlo, todos los campos dentro de ese diseño se guardan en el modelo correspondiente en lugar del modelo propietario.

### Relaciones Compatibles

- `BelongsTo`
- `HasOne`
- `MorphOne`

### Ejemplo

```php
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;

Fieldset::make('Metadata')
    ->relationship('metadata')
    ->schema([
        TextInput::make('title'),
        Textarea::make('description'),
        FileUpload::make('image'),
    ])
```

- Los campos `title`, `description` e `image` se cargan automáticamente desde la relación `metadata` y se guardan al enviar el formulario.
- Si el registro no existe, se crea automáticamente.

> **Nota:** Esta característica se explica con más profundidad en la [Documentación de Formularios](https://filamentphp.com/docs).

---

## Creación de un Gestor de Relaciones

Para crear un gestor de relaciones, usa el comando:

```bash
php artisan make:filament-relation-manager CategoryResource posts title
```

### Parámetros del Comando

- **`CategoryResource`**: Nombre de la clase de recurso para el modelo propietario (padre).
- **`posts`**: Nombre de la relación que quieres gestionar.
- **`title`**: Nombre del atributo que se usará para identificar las publicaciones.

### Archivo Generado

El comando crea un archivo en:

```
CategoryResource/RelationManagers/PostsRelationManager.php
```

Este archivo contiene una clase donde puedes definir un **formulario** y una **tabla** para tu gestor de relaciones:

```php
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

public function form(Schema $schema): Schema
{
    return $schema
        ->components([
            Forms\Components\TextInput::make('title')->required(),
            // ...
        ]);
}

public function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('title'),
            // ...
        ]);
}
```

### Registro del Gestor de Relaciones

Debes registrar el nuevo gestor de relaciones en el método `getRelations()` de tu recurso:

```php
public static function getRelations(): array
{
    return [
        RelationManagers\PostsRelationManager::class,
    ];
}
```

Una vez definida la tabla y el formulario, visita la página de **Edición** o **Vista** de tu recurso para ver el gestor de relaciones en acción.

---

## Personalización del Parámetro URL del Gestor de Relaciones

Si pasas una **clave** al array devuelto desde `getRelations()`, se usará en la URL del gestor de relaciones. Por ejemplo:

```php
public static function getRelations(): array
{
    return [
        'posts' => RelationManagers\PostsRelationManager::class,
    ];
}
```

Esto generará una URL como:

```
?relation=posts
```

---

## Modo de Solo Lectura

Los gestores de relaciones suelen mostrarse en la página **Editar** o **Ver** de un recurso. En la página **Ver**, Filament ocultará automáticamente todas las acciones que modifiquen la relación (como crear, editar y eliminar). Esto se conoce como **modo de solo lectura**.

### Desactivar el Modo de Solo Lectura

Puedes desactivar este comportamiento sobrescribiendo el método `isReadOnly()` en la clase de gestión de relaciones:

```php
public function isReadOnly(): bool
{
    return false;
}
```

### Desactivar para Todos los Gestores de Relaciones

Si prefieres desactivar esta funcionalidad para todos los gestores de relaciones, hazlo en la configuración del panel:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->readOnlyRelationManagersOnResourceViewPagesByDefault(false);
}
```

---

## Nombres de Relaciones Inversas Poco Convencionales

Para relaciones inversas que no siguen las convenciones de nombres de Laravel, usa el método `inverseRelationship()` en la tabla:

```php
use Filament\Tables;
use Filament\Tables\Table;

public function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('title'),
            // ...
        ])
        ->inverseRelationship('section'); // Ejemplo: si la relación inversa es `section` en lugar de `category`
}
```

---

## Manejo de Eliminaciones Suaves

Por defecto, no podrás interactuar con registros eliminados en el gestor de relaciones. Si quieres añadir funcionalidad para **restaurar**, **forzar la eliminación** y **filtrar registros eliminados**, usa la bandera `--soft-deletes` al generar el gestor de relaciones:

```bash
php artisan make:filament-relation-manager CategoryResource posts title --soft-deletes
```

### Configuración Manual

Si ya generaste el gestor de relaciones, puedes añadir la funcionalidad manualmente:

```php
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

public function table(Table $table): Table
{
    return $table
        ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]))
        ->columns([
            // ...
        ])
        ->filters([
            TrashedFilter::make(),
            // ...
        ])
        ->recordActions([
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
            // ...
        ])
        ->toolbarActions([
            BulkActionGroup::make([
                DeleteBulkAction::make(),
                ForceDeleteBulkAction::make(),
                RestoreBulkAction::make(),
                // ...
            ]),
        ]);
}
```

---

## Registros Relacionados con Atributos Pivote

Para relaciones `BelongsToMany` o `MorphToMany`, puedes añadir atributos de tabla pivote. Por ejemplo, si tienes una relación `teams` para un recurso `User` y quieres añadir el atributo pivote `role`:

```php
use Filament\Tables;

public function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('name'),
            Tables\Columns\TextColumn::make('role'),
        ]);
}
```

> **Importante:** Asegúrate de que el atributo pivote esté listado en el método `withPivot()` de la relación y su inversa.

---

## Creación de Registros Relacionados

### Crear con Atributos Pivote

Para relaciones `BelongsToMany` o `MorphToMany`, puedes añadir atributos pivote al formulario de creación:

```php
use Filament\Forms;
use Filament\Schemas\Schema;

public function form(Schema $schema): Schema
{
    return $schema
        ->components([
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\TextInput::make('role')->required(),
            // ...
        ]);
}
```

> **Importante:** Asegúrate de que el atributo pivote esté listado en el método `withPivot()` de la relación y su inversa.

### Personalización de `CreateAction`

Para personalizar la acción de creación (como mutar datos del formulario o cambiar notificaciones), consulta la [Documentación de Acciones](https://filamentphp.com/docs).

---

## Edición de Registros Relacionados

### Editar con Atributos Pivote

Para relaciones `BelongsToMany` o `MorphToMany`, puedes editar atributos pivote en el formulario de edición:

```php
use Filament\Forms;
use Filament\Schemas\Schema;

public function form(Schema $schema): Schema
{
    return $schema
        ->components([
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\TextInput::make('role')->required(),
            // ...
        ]);
}
```

> **Importante:** Asegúrate de que el atributo pivote esté listado en el método `withPivot()` de la relación y su inversa.

### Personalización de `EditAction`

Para personalizar la acción de edición, consulta la [Documentación de Acciones](https://filamentphp.com/docs).

---

## Acoplar y Desacoplar Registros

Filament puede **acoplar** y **desacoplar** registros en relaciones `BelongsToMany` o `MorphToMany`.

### Generar con la Bandera `--attach`

Al generar el gestor de relaciones, usa la bandera `--attach` para añadir `AttachAction`, `DetachAction` y `DetachBulkAction`:

```bash
php artisan make:filament-relation-manager CategoryResource posts title --attach
```

### Añadir Acciones Manualmente

Si ya generaste el recurso, puedes añadir las acciones manualmente:

```php
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Tables\Table;

public function table(Table $table): Table
{
    return $table
        ->columns([
            // ...
        ])
        ->headerActions([
            AttachAction::make(),
        ])
        ->recordActions([
            DetachAction::make(),
        ])
        ->toolbarActions([
            BulkActionGroup::make([
                DetachBulkAction::make(),
            ]),
        ]);
}
```

### Autorización en Acciones Masivas

- Las acciones `AttachAction`, `DetachAction`, `DissociateAction` y sus variantes masivas **no consultan métodos de política de modelo por defecto**. Solo verifican el estado del gestor de relaciones.
- Las acciones masivas de **eliminación**, **eliminación forzada** y **restauración** usan los métodos `deleteAny()`, `forceDeleteAny()` y `restoreAny()` de la política (una llamada para todo el lote).
- Si necesitas autorización por registro, usa:
  ```php
  ->authorizeIndividualRecords('ability')
  ```

---

## Precargar Opciones de Selección en Modal de Adjuntar

Por defecto, las opciones se cargan desde la base de datos vía AJAX. Si deseas precargarlas al cargar el formulario, usa:

```php
use Filament\Actions\AttachAction;

AttachAction::make()
    ->preloadRecordSelect()
```

---

## Adjuntar con Atributos Pivote

Puedes definir un formulario personalizado para añadir atributos pivote al adjuntar un registro:

```php
use Filament\Actions\AttachAction;
use Filament\Forms;

AttachAction::make()
    ->schema(fn (AttachAction $action): array => [
        $action->getRecordSelect(),
        Forms\Components\TextInput::make('role')->required(),
    ])
```

> **Nota:** `$action->getRecordSelect()` devuelve el campo `select` para elegir el registro a adjuntar.

---

## Definir Opciones para Adjuntar

Puedes filtrar las opciones disponibles para adjuntar:

```php
use Filament\Actions\AttachAction;
use Illuminate\Database\Eloquent\Builder;

AttachAction::make()
    ->recordSelectOptionsQuery(fn (Builder $query) => $query->whereBelongsTo(auth()->user()))
```

---

## Buscar en Múltiples Columnas

Por defecto, las opciones se buscan en la columna `recordTitleAttribute`. Para buscar en múltiples columnas:

```php
use Filament\Actions\AttachAction;

AttachAction::make()
    ->recordSelectSearchColumns(['title', 'description'])
```

---

## Adjuntar Múltiples Registros

Para permitir la selección de múltiples registros:

```php
use Filament\Actions\AttachAction;

AttachAction::make()
    ->multiple()
```

---

## Personalización del Campo Select en Modal de Adjuntar

Puedes personalizar el campo `select` usado en el modal:

```php
use Filament\Actions\AttachAction;
use Filament\Forms\Components\Select;

AttachAction::make()
    ->recordSelect(
        fn (Select $select) => $select->placeholder('Selecciona un post'),
    )
```

---

## Seleccionar Registros Usando una Tabla Modal

Puedes usar una tabla de Filament para seleccionar registros en lugar de un `select`:

```php
use App\Filament\Resources\Products\Tables\ProductsTable;
use Filament\Actions\AttachAction;

AttachAction::make()
    ->tableSelect(ProductsTable::class)
```

### Ejemplo de Configuración de Tabla

```php
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

public static function configure(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('name'),
            TextColumn::make('sku'),
            // ...
        ])
        ->filters([
            // ...
        ]);
}
```

---

## Manejo de Duplicados

Por defecto, no se permite adjuntar un registro más de una vez. Para permitir duplicados:

1. Asegúrate de que el atributo `id` esté listado en el método `withPivot()` de la relación y su inversa.
2. Añade el método `allowDuplicates()` a la tabla:

```php
public function table(Table $table): Table
{
    return $table
        ->allowDuplicates();
}
```

---

## Mejorar el Rendimiento de `DetachBulkAction`

Por defecto, `DetachBulkAction` carga todos los registros en memoria. Para reducir el uso de memoria, usa:

```php
use Filament\Actions\DetachBulkAction;

DetachBulkAction::make()
    ->chunkSelectedRecords(250)
```

### Desactivar la Carga de Registros en Memoria

Si no necesitas autorización individual ni eventos de modelo, usa:

```php
DetachBulkAction::make()
    ->fetchSelectedRecords(false)
```

---

## Asociación y Disociación de Registros

Filament puede **asociar** y **disociar** registros en relaciones `HasMany` o `MorphMany`.

### Generar con la Bandera `--associate`

```bash
php artisan make:filament-relation-manager CategoryResource posts title --associate
```

### Añadir Acciones Manualmente

```php
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Tables\Table;

public function table(Table $table): Table
{
    return $table
        ->columns([
            // ...
        ])
        ->headerActions([
            AssociateAction::make(),
        ])
        ->recordActions([
            DissociateAction::make(),
        ])
        ->toolbarActions([
            BulkActionGroup::make([
                DissociateBulkAction::make(),
            ]),
        ]);
}
```

---

## Precargar Opciones de Selección en Modal de Asociar

```php
use Filament\Actions\AssociateAction;

AssociateAction::make()
    ->preloadRecordSelect()
```

---

## Definir Opciones para Asociar

```php
use Filament\Actions\AssociateAction;
use Illuminate\Database\Eloquent\Builder;

AssociateAction::make()
    ->recordSelectOptionsQuery(fn (Builder $query) => $query->whereBelongsTo(auth()->user()))
```

---

## Buscar en Múltiples Columnas para Asociar

```php
use Filament\Actions\AssociateAction;

AssociateAction::make()
    ->recordSelectSearchColumns(['title', 'description'])
```

---

## Asociar Múltiples Registros

```php
use Filament\Actions\AssociateAction;

AssociateAction::make()
    ->multiple()
```

---

## Personalización del Campo Select en Modal de Asociar

```php
use Filament\Actions\AssociateAction;
use Filament\Forms\Components\Select;

AssociateAction::make()
    ->recordSelect(
        fn (Select $select) => $select->placeholder('Selecciona un post'),
    )
```

---

## Mejorar el Rendimiento de `DissociateBulkAction`

```php
use Filament\Actions\DissociateBulkAction;

DissociateBulkAction::make()
    ->chunkSelectedRecords(250)
```

### Desactivar la Carga de Registros en Memoria

```php
DissociateBulkAction::make()
    ->fetchSelectedRecords(false)
```

---

## Visualización de Registros Relacionados

Para añadir una acción de visualización al gestor de relaciones, usa la bandera `--view`:

```bash
php artisan make:filament-relation-manager CategoryResource posts title --view
```

O añade manualmente la acción:

```php
use Filament\Actions\ViewAction;
use Filament\Tables\Table;

public function table(Table $table): Table
{
    return $table
        ->columns([
            // ...
        ])
        ->recordActions([
            ViewAction::make(),
            // ...
        ]);
}
```

---

## Eliminación de Registros Relacionados

Para añadir funcionalidad de eliminación suave, usa la bandera `--soft-deletes`:

```bash
php artisan make:filament-relation-manager CategoryResource posts title --soft-deletes
```

O configúralo manualmente (ver sección [Manejo de Eliminaciones Suaves](#manejo-de-eliminaciones-suaves)).

---

## Importación de Registros Relacionados

Puedes añadir un `ImportAction` al gestor de relaciones para importar registros. Usa `options` para pasar el ID del registro propietario:

```php
ImportAction::make()
    ->importer(ProductImporter::class)
    ->options(['categoryId' => $this->getOwnerRecord()->getKey()])
```

### Asociar al Propietario en la Relación

En la clase importadora:

```php
public function resolveRecord(): ?Product
{
    $product = Product::firstOrNew([
        'sku' => $this->data['sku'],
    ]);
    
    $product->category()->associate($this->options['categoryId']);
    
    return $product;
}
```

### Adjuntar en Relación Muchos-a-Muchos

Usa el gancho `afterSave()`:

```php
protected function afterSave(): void
{
    $this->record->categories()->syncWithoutDetaching([$this->options['categoryId']]);
}
```

---

## Acceso al Registro Propietario

Los gestores de relaciones son componentes Livewire. El registro propietario (el modelo padre) se guarda en una propiedad. Puedes acceder a él con:

```php
$this->getOwnerRecord()
```

### En Métodos Estáticos

Si estás en un método estático como `form()` o `table()`, usa una devolución de llamada para acceder a la instancia Livewire:

```php
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;

public function form(Schema $schema): Schema
{
    return $schema
        ->components([
            Forms\Components\Select::make('store_id')
                ->options(function (RelationManager $livewire): array {
                    return $livewire->getOwnerRecord()->stores()
                        ->pluck('name', 'id')
                        ->toArray();
                }),
            // ...
        ]);
}
```

---

## Agrupación de Gestores de Relaciones

Puedes agrupar gestores de relaciones en una sola pestaña usando `RelationGroup`:

```php
use Filament\Resources\RelationManagers\RelationGroup;

public static function getRelations(): array
{
    return [
        RelationGroup::make('Interacciones', [
            RelationManagers\CommentsRelationManager::class,
            RelationManagers\TagsRelationManager::class,
        ]),
        RelationGroup::make('Enlaces', [
            RelationManagers\LinksRelationManager::class,
        ]),
    ];
}
```

---

## Mostrar Gestores de Relaciones Condicionalmente

Por defecto, los gestores de relaciones son visibles si el método `viewAny()` de la política del modelo relacionado devuelve `true`. Puedes personalizar esto con:

```php
use Illuminate\Database\Eloquent\Model;

public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
{
    return $ownerRecord->status === Status::Draft;
}
```

---

## Combinar Pestañas de Gestor de Relaciones con el Formulario

En la clase **Editar** o **Ver**, sobrescribe el método:

```php
public function hasCombinedRelationManagerTabsWithContent(): bool
{
    return true;
}
```

---

## Personalización de la Pestaña de Contenido

En la clase **Editar** o **Ver**, sobrescribe el método `getContentTabComponent()`:

```php
use Filament\Schemas\Components\Tabs\Tab;

public function getContentTabComponent(): Tab
{
    return Tab::make('Configuración')
        ->icon('heroicon-m-cog');
}
```

---

## Establecer la Posición de la Pestaña de Formulario

Por defecto, la pestaña de formulario se muestra antes de las pestañas de relación. Para mostrarla después:

```php
use Filament\Resources\Pages\Enums\ContentTabPosition;

public function getContentTabPosition(): ?ContentTabPosition
{
    return ContentTabPosition::After;
}
```

---

## Personalización de Pestañas del Gestor de Relaciones

Para personalizar la pestaña de un gestor de relaciones, sobrescribe el método `getTabComponent()`:

```php
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Model;

public static function getTabComponent(Model $ownerRecord, string $pageClass): Tab
{
    return Tab::make('Publicaciones del Blog')
        ->badge($ownerRecord->posts()->count())
        ->badgeColor('info')
        ->badgeTooltip('Número de publicaciones en esta categoría')
        ->icon('heroicon-m-document-text');
}
```

### Para Grupos de Relaciones

```php
use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Model;

RelationGroup::make('Contactos', [
    // ...
])
    ->tab(fn (Model $ownerRecord): Tab => Tab::make('Publicaciones del Blog')
        ->badge($ownerRecord->posts()->count())
        ->badgeColor('info')
        ->badgeTooltip('Número de publicaciones en esta categoría')
        ->icon('heroicon-m-document-text'));
```

---

## Aplazamiento de la Carga de Insignias de Pestaña

Si la consulta para la insignia es costosa, puedes aplazar su carga:

```php
use Illuminate\Database\Eloquent\Model;

protected static bool $isBadgeDeferred = true;

public static function getBadge(Model $ownerRecord, string $pageClass): ?string
{
    $count = $ownerRecord->tickets()->count();
    return $count > 0 ? (string) $count : null;
}
```

### Método Dinámico

```php
public static function isBadgeDeferred(Model $ownerRecord, string $pageClass): bool
{
    return FeatureFlag::active();
}
```

### Para Grupos de Relaciones

```php
RelationGroup::make('Contactos', [
    // ...
])
    ->badge(fn (Model $ownerRecord): string => (string) $ownerRecord->contacts()->count())
    ->deferBadge();
```

---

## Compartir Formulario y Tabla de un Recurso con un Gestor de Relaciones

Puedes reutilizar el formulario y la tabla de un recurso en un gestor de relaciones:

```php
use App\Filament\Resources\Blog\Posts\PostResource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

public function form(Schema $schema): Schema
{
    return PostResource::form($schema);
}

public function table(Table $table): Table
{
    return PostResource::table($table);
}
```

---

## Ocultar Componentes de Formulario Compartidos

Si compartes un componente de formulario del recurso con el gestor de relaciones y quieres ocultarlo en el gestor, usa:

```php
use App\Filament\Resources\Blog\Posts\PostResource\RelationManagers\CommentsRelationManager;
use Filament\Forms\Components\Select;

Select::make('post_id')
    ->relationship('post', 'title')
    ->hiddenOn(CommentsRelationManager::class)
```

---

## Ocultar Columnas de Tabla Compartidas

```php
use App\Filament\Resources\Blog\Posts\PostResource\RelationManagers\CommentsRelationManager;
use Filament\Tables\Columns\TextColumn;

TextColumn::make('post.title')
    ->hiddenOn(CommentsRelationManager::class)
```

---

## Ocultar Filtros de Tabla Compartidos

```php
use App\Filament\Resources\Blog\Posts\PostResource\RelationManagers\CommentsRelationManager;
use Filament\Tables\Filters\SelectFilter;

SelectFilter::make('post')
    ->relationship('post', 'title')
    ->hiddenOn(CommentsRelationManager::class)
```

---

## Anulación de Configuración Compartida

Puedes sobrescribir la configuración del recurso en el gestor de relaciones. Por ejemplo, desactivar la paginación:

```php
use App\Filament\Resources\Blog\Posts\PostResource;
use Filament\Tables\Table;

public function table(Table $table): Table
{
    return PostResource::table($table)
        ->paginated(false);
}
```

### Añadir Acciones Adicionales

```php
use App\Filament\Resources\Blog\Posts\PostResource;
use Filament\Actions\AttachAction;
use Filament\Actions\CreateAction;
use Filament\Tables\Table;

public function table(Table $table): Table
{
    return PostResource::table($table)
        ->headerActions([
            CreateAction::make(),
            AttachAction::make(),
        ]);
}
```

---

## Personalización de la Consulta Eloquent del Gestor de Relaciones

Puedes aplicar restricciones de consulta o ámbitos de modelo que afecten a todo el gestor de relaciones:

```php
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

public function table(Table $table): Table
{
    return $table
        ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true))
        ->columns([
            // ...
        ]);
}
```

---

## Personalización del Título del Gestor de Relaciones

### Título Estático

```php
protected static ?string $title = 'Publicaciones';
```

### Título Dinámico

```php
use Illuminate\Database\Eloquent\Model;

public static function getTitle(Model $ownerRecord, string $pageClass): string
{
    return __('relation-managers.posts.title');
}
```

### Personalización del Encabezado de la Tabla

```php
use Filament\Tables;

public function table(Table $table): Table
{
    return $table
        ->heading('Publicaciones')
        ->columns([
            // ...
        ]);
}
```

---

## Personalización del Título del Registro

El gestor de relaciones usa el concepto de **atributo de título de registro** para identificar registros. Al crear un gestor de relaciones, este atributo se pasa como tercer argumento al comando:

```bash
php artisan make:filament-relation-manager CategoryResource posts title
```

En este ejemplo, el atributo `title` del modelo `Post` se usará para identificar una publicación.

### Concatenar Múltiples Atributos

Puedes sobrescribir el método `recordTitle()` para concatenar múltiples atributos:

```php
use App\Models\Post;
use Filament\Tables\Table;

public function table(Table $table): Table
{
    return $table
        ->recordTitle(fn (Post $record): string => "{$record->title} ({$record->id})")
        ->columns([
            // ...
        ]);
}
```

### Especificar Columnas de Búsqueda

Si usas `AssociateAction` o `AttachAction`, especifica las columnas de búsqueda:

```php
use Filament\Actions\AssociateAction;
use Filament\Actions\AttachAction;

AssociateAction::make()
    ->recordSelectSearchColumns(['title', 'id']);

AttachAction::make()
    ->recordSelectSearchColumns(['title', 'id'])
```

---

## Páginas de Relaciones

Si prefieres mantener la funcionalidad de gestionar una relación separada de la edición o visualización del registro propietario, usa una **página de relación** (`ManageRelatedRecords`).

### Crear una Página de Relación

```bash
php artisan make:filament-page ManageCustomerAddresses --resource=CustomerResource --type=ManageRelatedRecords
```

### Registro en el Recurso

```php
public static function getPages(): array
{
    return [
        'index' => Pages\ListCustomers::route('/'),
        'create' => Pages\CreateCustomer::route('/create'),
        'view' => Pages\ViewCustomer::route('/{record}'),
        'edit' => Pages\EditCustomer::route('/{record}/edit'),
        'addresses' => Pages\ManageCustomerAddresses::route('/{record}/addresses'),
    ];
}
```

### Añadir a la Sub-Navegación de Recursos

```php
use App\Filament\Resources\Customers\Pages;
use Filament\Resources\Pages\Page;

public static function getRecordSubNavigation(Page $page): array
{
    return $page->generateNavigationItems([
        // ...
        Pages\ManageCustomerAddresses::class,
    ]);
}
```

---

## Transferir Propiedades a Gestores de Relaciones

Al registrar un gestor de relaciones, puedes pasar propiedades de Livewire:

```php
use App\Filament\Resources\Blog\Posts\PostResource\RelationManagers\CommentsRelationManager;

public static function getRelations(): array
{
    return [
        CommentsRelationManager::make([
            'status' => 'approved',
        ]),
    ];
}
```

### Acceso en el Gestor de Relaciones

```php
use Filament\Resources\RelationManagers\RelationManager;

class CommentsRelationManager extends RelationManager
{
    public string $status;

    // Puedes acceder a $this->status en el gestor de relaciones
}
```

---

## Desactivar la Carga Perezosa

Por defecto, los gestores de relaciones se cargan de forma perezosa (solo cuando son visibles). Para desactivarlo:

```php
protected static bool $isLazy = false;
```