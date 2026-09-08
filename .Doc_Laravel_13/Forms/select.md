Aquí tienes el contenido completo en **Markdown** y en **español**, sin omitir nada y manteniendo la estructura original:

---

# Select

## Introducción

El componente **Select** te permite seleccionar de una lista de opciones predefinidas:

```php
use Filament\Forms\Components\Select;

Select::make('status')
    ->options([
        'draft' => 'Borrador',
        'reviewing' => 'En revisión',
        'published' => 'Publicado',
    ])
```

Además de permitir un array estático, el método `options()` también acepta una función para calcularlo dinámicamente. Puedes inyectar varias utilidades en la función como parámetros.
[Más información sobre inyección de utilidades](#).

---



## Campo


| Variable     | Tipo                                          | Descripción                                                                                            |
| ------------ | --------------------------------------------- | ------------------------------------------------------------------------------------------------------ |
| `$component` | `Filament\Forms\Components\Field`             | La instancia actual del componente de campo.                                                           |
| `$get`       | `Filament\Schemas\Components\Utilities\Get`   | Una función para recuperar valores de los datos actuales del formulario. **No se ejecuta validación**. |
| `$livewire`  | `Livewire\Component`                          | La instancia del componente Livewire.                                                                  |
| `$model`     | `?string<Illuminate\Database\Eloquent\Model>` | El FQN (Nombre Completo Calificado) del modelo Eloquent para el esquema actual.                        |
| `$operation` | `string`                                      | La operación actual que se está realizando en el esquema. Normalmente: `create`, `edit` o `view`.      |
| `$rawState`  | `mixed`                                       | El valor actual del campo, **antes de aplicar los casts de estado**. **No se ejecuta validación**.     |
| `$record`    | `?Illuminate\Database\Eloquent\Model`         | El registro Eloquent para el esquema actual.                                                           |
| `$state`     | `mixed`                                       | El valor actual del campo. **No se ejecuta validación**.                                               |


---



## Habilitar el select de JavaScript

Por defecto, Filament usa el select nativo de HTML5. Puedes habilitar un select de JavaScript más personalizable usando el método `native(false)`:

```php
use Filament\Forms\Components\Select;

Select::make('status')
    ->options([
        'draft' => 'Borrador',
        'reviewing' => 'En revisión',
        'published' => 'Publicado',
    ])
    ->native(false)
```

Además de permitir un valor estático, el método `native()` también acepta una función para calcularlo dinámicamente.
[Más información sobre inyección de utilidades](#).

---



## Buscar opciones

Puedes habilitar un campo de búsqueda para facilitar el acceso a muchas opciones usando el método `searchable()`:

```php
use Filament\Forms\Components\Select;

Select::make('author_id')
    ->label('Autor')
    ->options(User::query()->pluck('name', 'id'))
    ->searchable()
```

Opcionalmente, puedes pasar un valor booleano para controlar si el campo debe ser buscable o no:

```php
Select::make('author_id')
    ->label('Autor')
    ->options(User::query()->pluck('name', 'id'))
    ->searchable(FeatureFlag::active())
```

El método `searchable()` también acepta una función para calcularlo dinámicamente.
[Más información sobre inyección de utilidades](#).

---



## Devolver resultados de búsqueda personalizados

Si tienes muchas opciones y quieres poblarlas según una búsqueda en la base de datos u otra fuente de datos externa, puedes usar los métodos `getSearchResultsUsing()` y `getOptionLabelUsing()` en lugar de `options()`.

- `getSearchResultsUsing()` acepta un callback que devuelve resultados de búsqueda en formato `$key => $value`. La búsqueda actual del usuario está disponible como `$search`, y debes usarla para filtrar los resultados.
- `getOptionLabelUsing()` acepta un callback que transforma el valor de la opción seleccionada (`$value`) en una etiqueta. Esto se usa cuando el formulario se carga por primera vez y el usuario aún no ha realizado una búsqueda.

Ambos métodos, `getSearchResultsUsing()` y `getOptionLabelUsing()`, deben usarse juntos en el select si deseas proporcionar resultados de búsqueda personalizados:

```php
use Filament\Forms\Components\Select;

Select::make('author_id')
    ->searchable()
    ->getSearchResultsUsing(fn (string $search): array => User::query()
        ->where('name', 'like', "%{$search}%")
        ->limit(50)
        ->pluck('name', 'id')
        ->all())
    ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->name),
```

**Nota:** `getOptionLabelUsing()` es crucial, ya que proporciona a Filament la etiqueta de la opción seleccionada, para que no tenga que ejecutar una búsqueda completa para encontrarla. Si una opción no es válida, debe devolver `null`.

---



## Establecer un mensaje de carga personalizado

Cuando usas un select buscable o multi-select, puedes mostrar un mensaje personalizado mientras se cargan las opciones usando el método `loadingMessage()`:

```php
Select::make('author_id')
    ->relationship(name: 'author', titleAttribute: 'name')
    ->searchable()
    ->loadingMessage('Cargando autores...')
```

El método `loadingMessage()` también acepta una función para calcularlo dinámicamente.
[Más información sobre inyección de utilidades](#).

---



## Establecer un mensaje personalizado de "sin resultados de búsqueda"

Puedes mostrar un mensaje personalizado cuando no se encuentran resultados de búsqueda usando el método `noSearchResultsMessage()`:

```php
Select::make('author_id')
    ->relationship(name: 'author', titleAttribute: 'name')
    ->searchable()
    ->noSearchResultsMessage('No se encontraron autores.')
```

El método `noSearchResultsMessage()` también acepta una función para calcularlo dinámicamente.
[Más información sobre inyección de utilidades](#).

---



## Establecer un mensaje personalizado de "sin opciones"

Puedes mostrar un mensaje personalizado cuando no hay opciones disponibles en un select o multi-select con `preload()` u opciones dinámicas usando el método `noOptionsMessage()`:

```php
Select::make('author_id')
    ->relationship(name: 'author', titleAttribute: 'name')
    ->searchable()
    ->preload()
    ->noOptionsMessage('No hay autores disponibles.')
```

El método `noOptionsMessage()` también acepta una función para calcularlo dinámicamente.
[Más información sobre inyección de utilidades](#).

---



## Establecer un mensaje personalizado de búsqueda

Puedes mostrar un mensaje personalizado cuando el usuario aún no ha ingresado un término de búsqueda usando el método `searchPrompt()`:

```php
Select::make('author_id')
    ->relationship(name: 'author', titleAttribute: 'name')
    ->searchable(['name', 'email'])
    ->searchPrompt('Buscar autores por nombre o correo electrónico')
```

El método `searchPrompt()` también acepta una función para calcularlo dinámicamente.
[Más información sobre inyección de utilidades](#).

---



## Establecer un mensaje personalizado de "buscando"

Puedes mostrar un mensaje personalizado mientras se cargan los resultados de búsqueda usando el método `searchingMessage()`:

```php
Select::make('author_id')
    ->relationship(name: 'author', titleAttribute: 'name')
    ->searchable()
    ->searchingMessage('Buscando autores...')
```

El método `searchingMessage()` también acepta una función para calcularlo dinámicamente.
[Más información sobre inyección de utilidades](#).

---



## Ajustar el debounce de búsqueda

Por defecto, Filament esperará **1000 milisegundos** (1 segundo) antes de buscar opciones cuando el usuario escribe en un select buscable o multi-select. También esperará 1000 milisegundos entre búsquedas si el usuario sigue escribiendo. Puedes cambiar esto usando el método `searchDebounce()`:

```php
Select::make('author_id')
    ->relationship(name: 'author', titleAttribute: 'name')
    ->searchable()
    ->searchDebounce(500)
```

**Advertencia:** No reduzcas demasiado el debounce, ya que esto puede hacer que el select se vuelva lento y no responda debido a un alto número de solicitudes de red para recuperar opciones del servidor.

El método `searchDebounce()` también acepta una función para calcularlo dinámicamente.
[Más información sobre inyección de utilidades](#).

---



## Multi-select

El método `multiple()` en el componente **Select** te permite seleccionar múltiples valores de la lista de opciones:

```php
Select::make('technologies')
    ->multiple()
    ->options([
        'tailwind' => 'Tailwind CSS',
        'alpine' => 'Alpine.js',
        'laravel' => 'Laravel',
        'livewire' => 'Laravel Livewire',
    ])
```

Opcionalmente, puedes pasar un valor booleano para controlar si el campo debe ser múltiple o no:

```php
Select::make('technologies')
    ->multiple(FeatureFlag::active())
    ->options([
        'tailwind' => 'Tailwind CSS',
        'alpine' => 'Alpine.js',
        'laravel' => 'Laravel',
        'livewire' => 'Laravel Livewire',
    ])
```

El método `multiple()` también acepta una función para calcularlo dinámicamente.
[Más información sobre inyección de utilidades](#).

---



### Guardar opciones múltiples en JSON

Estas opciones se devuelven en formato JSON. Si las estás guardando usando Eloquent, asegúrate de agregar un cast de array a la propiedad del modelo:

```php
use Illuminate\Database\Eloquent\Model;

class App extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'technologies' => 'array',
        ];
    }
}
```

---



### Devolver etiquetas de opciones personalizadas para multi-select

Si estás devolviendo resultados de búsqueda personalizados, debes definir `getOptionLabelsUsing()` en lugar de `getOptionLabelUsing()`. `$values` se pasará al callback en lugar de `$value`, y debes devolver un array `$key => $value` de etiquetas y sus valores correspondientes:

```php
Select::make('technologies')
    ->multiple()
    ->searchable()
    ->getSearchResultsUsing(fn (string $search): array => Technology::query()
        ->where('name', 'like', "%{$search}%")
        ->limit(50)
        ->pluck('name', 'id')
        ->all())
    ->getOptionLabelsUsing(fn (array $values): array => Technology::query()
        ->whereIn('id', $values)
        ->pluck('name', 'id')
        ->all()),
```

**Nota:** `getOptionLabelsUsing()` es crucial, ya que proporciona a Filament las etiquetas de las opciones ya seleccionadas, para que no tenga que ejecutar una búsqueda completa para encontrarlas. También se usa para validar que las opciones seleccionadas por el usuario son válidas. Si una opción no es válida, no debe estar presente en el array devuelto por `getOptionLabelsUsing()`.

---



## Reordenar opciones seleccionadas

El método `reorderable()` te permite reordenar las opciones seleccionadas en un multi-select:

```php
Select::make('technologies')
    ->multiple()
    ->reorderable()
    ->options([
        'tailwind' => 'Tailwind CSS',
        'alpine' => 'Alpine.js',
        'laravel' => 'Laravel',
        'livewire' => 'Laravel Livewire',
    ])
```

Esto es útil cuando el orden de las opciones seleccionadas importa.
El método `reorderable()` también acepta una función para calcularlo dinámicamente.
[Más información sobre inyección de utilidades](#).

---



## Agrupar opciones

Puedes agrupar opciones bajo una etiqueta para organizarlas mejor. Para hacerlo, puedes pasar un array de grupos a `options()` o donde normalmente pasarías un array de opciones. Las claves del array se usan como etiquetas de grupo, y los valores son arrays de opciones en ese grupo:

```php
Select::make('status')
    ->searchable()
    ->options([
        'En Proceso' => [
            'draft' => 'Borrador',
            'reviewing' => 'En revisión',
        ],
        'Revisado' => [
            'published' => 'Publicado',
            'rejected' => 'Rechazado',
        ],
    ])
```

---



## Integración con relaciones Eloquent

Puedes usar el método `relationship()` del **Select** para configurar una relación **BelongsTo** y recuperar automáticamente las opciones. El parámetro `titleAttribute` es el nombre de una columna que se usará para generar una etiqueta para cada opción:

```php
Select::make('author_id')
    ->relationship(name: 'author', titleAttribute: 'name')
```

El método `multiple()` puede usarse en combinación con `relationship()` para usar una relación **BelongsToMany**. Filament cargará las opciones de la relación y las guardará de vuelta en la tabla pivote de la relación cuando se envíe el formulario. Si no se proporciona un nombre, Filament usará el nombre del campo como nombre de la relación:

```php
Select::make('technologies')
    ->multiple()
    ->relationship(titleAttribute: 'name')
```

**Importante:** Cuando uses `disabled()` con `multiple()` y `relationship()`, asegúrate de que `disabled()` se llame **antes** que `relationship()`. Esto garantiza que la llamada a `saved()` de `disabled()` no se aplique después de la configuración de `relationship()`:

```php
Select::make('technologies')
    ->multiple()
    ->disabled()
    ->relationship(titleAttribute: 'name')
```

---



### Buscar opciones de relación en múltiples columnas

Por defecto, si el select también es buscable, Filament devolverá resultados de búsqueda para la relación basados en la columna de título de la relación. Si deseas buscar en múltiples columnas, puedes pasar un array de columnas al método `searchable()`:

```php
Select::make('author_id')
    ->relationship(name: 'author', titleAttribute: 'name')
    ->searchable(['name', 'email'])
```

---



### Precargar opciones de relación

Si deseas poblar las opciones buscables desde la base de datos cuando se carga la página (en lugar de cuando el usuario busca), puedes usar el método `preload()`:

```php
Select::make('author_id')
    ->relationship(name: 'author', titleAttribute: 'name')
    ->searchable()
    ->preload()
```

Opcionalmente, puedes pasar un valor booleano para controlar si el campo debe precargarse o no:

```php
Select::make('author_id')
    ->relationship(name: 'author', titleAttribute: 'name')
    ->searchable()
    ->preload(FeatureFlag::active())
```

El método `preload()` también acepta una función para calcularlo dinámicamente.
[Más información sobre inyección de utilidades](#).

---



### Excluir el registro actual

Al trabajar con relaciones recursivas, es probable que desees eliminar el registro actual del conjunto de resultados. Esto se puede hacer fácilmente usando el argumento `ignoreRecord`:

```php
Select::make('parent_id')
    ->relationship(name: 'parent', titleAttribute: 'name', ignoreRecord: true)
```

---



### Personalizar la consulta de relación

Puedes personalizar la consulta de la base de datos que recupera las opciones usando el tercer parámetro del método `relationship()`:

```php
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;

Select::make('author_id')
    ->relationship(
        name: 'author',
        titleAttribute: 'name',
        modifyQueryUsing: fn (Builder $query) => $query->withTrashed(),
    )
```

El argumento `modifyQueryUsing` puede inyectar varias utilidades en la función como parámetros.
[Más información sobre inyección de utilidades](#).

---



### Personalizar las etiquetas de las opciones de relación

Si deseas personalizar la etiqueta de cada opción (por ejemplo, para ser más descriptivo o concatenar nombre y apellido), puedes usar una columna virtual en tu migración de base de datos:

```php
$table->string('full_name')->virtualAs('concat(first_name, " ", last_name)');
```

O alternativamente, usar el método `getOptionLabelFromRecordUsing()` para transformar el modelo Eloquent de una opción en una etiqueta:

```php
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

Select::make('author_id')
    ->relationship(
        name: 'author',
        modifyQueryUsing: fn (Builder $query) => $query->orderBy('first_name')->orderBy('last_name'),
    )
    ->getOptionLabelFromRecordUsing(fn (Model $record) => "{$record->first_name} {$record->last_name}")
    ->searchable(['first_name', 'last_name'])
```

El método `getOptionLabelFromRecordUsing()` puede inyectar varias utilidades en la función como parámetros.
[Más información sobre inyección de utilidades](#).

---



### Guardar datos pivote en la relación

Si estás usando una relación `multiple()` y tu tabla pivote tiene columnas adicionales, puedes usar el método `pivotData()` para especificar los datos que deben guardarse en ellas:

```php
Select::make('primaryTechnologies')
    ->relationship(name: 'technologies', titleAttribute: 'name')
    ->multiple()
    ->pivotData([
        'is_primary' => true,
    ])
```

El método `pivotData()` también acepta una función para calcularlo dinámicamente.
[Más información sobre inyección de utilidades](#).

---



## Crear una nueva opción en un modal

Puedes definir un formulario personalizado que se use para crear un nuevo registro y adjuntarlo a la relación **BelongsTo**:

```php
Select::make('author_id')
    ->relationship(name: 'author', titleAttribute: 'name')
    ->createOptionForm([
        Forms\Components\TextInput::make('name')
            ->required(),
        Forms\Components\TextInput::make('email')
            ->required()
            ->email(),
    ]),
```

El método `createOptionForm()` también acepta una función para calcularlo dinámicamente.
[Más información sobre inyección de utilidades](#).

---



### Personalizar la creación de nuevas opciones

Puedes personalizar el proceso de creación de la nueva opción definida en el formulario usando el método `createOptionUsing()`, que debe devolver la clave primaria del registro recién creado:

```php
Select::make('author_id')
    ->relationship(name: 'author', titleAttribute: 'name')
    ->createOptionForm([
       // ...
    ])
    ->createOptionUsing(function (array $data): int {
        return auth()->user()->team->members()->create($data)->getKey();
    }),
```

El método `createOptionUsing()` puede inyectar varias utilidades en la función como parámetros.
[Más información sobre inyección de utilidades](#).

---



## Editar la opción seleccionada en un modal

Puedes definir un formulario personalizado que se use para editar el registro seleccionado y guardarlo de vuelta en la relación **BelongsTo**:

```php
Select::make('author_id')
    ->relationship(name: 'author', titleAttribute: 'name')
    ->editOptionForm([
        Forms\Components\TextInput::make('name')
            ->required(),
        Forms\Components\TextInput::make('email')
            ->required()
            ->email(),
    ]),
```

El método `editOptionForm()` también acepta una función para calcularlo dinámicamente.
[Más información sobre inyección de utilidades](#).

---



### Personalizar las actualizaciones de opciones

Puedes personalizar el proceso de actualización de la opción seleccionada definida en el formulario usando el método `updateOptionUsing()`. El registro Eloquent actual que se está editando se puede recuperar usando el método `getRecord()` en el esquema:

```php
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

Select::make('author_id')
    ->relationship(name: 'author', titleAttribute: 'name')
    ->editOptionForm([
       // ...
    ])
    ->updateOptionUsing(function (array $data, Schema $schema) {
        $schema->getRecord()?->update($data);
    }),
```

El método `updateOptionUsing()` puede inyectar varias utilidades en la función como parámetros.
[Más información sobre inyección de utilidades](#).

---



## Manejar relaciones MorphTo

Las relaciones **MorphTo** son especiales, ya que permiten al usuario seleccionar registros de una variedad de modelos diferentes. Por esto, tenemos un componente dedicado **MorphToSelect**, que en realidad no es un campo select, sino **2 campos select dentro de un fieldset**. El primer campo select te permite seleccionar el tipo, y el segundo te permite seleccionar el registro de ese tipo.

Para usar **MorphToSelect**, debes pasar `types()` al componente, lo que le indica cómo renderizar opciones para diferentes tipos:

```php
use Filament\Forms\Components\MorphToSelect;

MorphToSelect::make('commentable')
    ->types([
        MorphToSelect\Type::make(Product::class)
            ->titleAttribute('name'),
        MorphToSelect\Type::make(Post::class)
            ->titleAttribute('title'),
    ])
```

El método `types()` puede inyectar varias utilidades en la función como parámetros.
[Más información sobre inyección de utilidades](#).

---



### Personalizar las etiquetas de las opciones para cada tipo morpheado

El método `titleAttribute()` se usa para extraer los títulos de cada producto o publicación. Si deseas personalizar la etiqueta de cada opción, puedes usar el método `getOptionLabelFromRecordUsing()` para transformar el modelo Eloquent en una etiqueta:

```php
MorphToSelect::make('commentable')
    ->types([
        MorphToSelect\Type::make(Product::class)
            ->getOptionLabelFromRecordUsing(fn (Product $record): string => "{$record->name} - {$record->slug}"),
        MorphToSelect\Type::make(Post::class)
            ->titleAttribute('title'),
    ])
```

---



### Personalizar la consulta de relación para cada tipo morpheado

Puedes personalizar la consulta de la base de datos que recupera las opciones usando el método `modifyOptionsQueryUsing()`:

```php
use Filament\Forms\Components\MorphToSelect;
use Illuminate\Database\Eloquent\Builder;

MorphToSelect::make('commentable')
    ->types([
        MorphToSelect\Type::make(Product::class)
            ->titleAttribute('name')
            ->modifyOptionsQueryUsing(fn (Builder $query) => $query->whereBelongsTo($this->team)),
        MorphToSelect\Type::make(Post::class)
            ->titleAttribute('title')
            ->modifyOptionsQueryUsing(fn (Builder $query) => $query->whereBelongsTo($this->team)),
    ])
```

El método `modifyOptionsQueryUsing()` puede inyectar varias utilidades en la función como parámetros.
[Más información sobre inyección de utilidades](#).

---



### Personalizar los campos select de MorphTo

Puedes personalizar aún más el campo select de **"clave"** para un tipo morpheado específico usando el método `modifyKeySelectUsing()`:

```php
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

MorphToSelect::make('commentable')
    ->types([
        MorphToSelect\Type::make(Product::class)
            ->titleAttribute('name')
            ->modifyKeySelectUsing(fn (Select $select): Select => $select
                ->createOptionForm([
                    TextInput::make('title')
                        ->required(),
                ])
                ->createOptionUsing(function (array $data): int {
                    return Product::create($data)->getKey();
                })),
        MorphToSelect\Type::make(Post::class)
            ->titleAttribute('title'),
    ])
```

Esto es útil si deseas personalizar el campo select de **"clave"** para cada tipo morpheado individualmente. Si deseas personalizar el select de clave para todos los tipos, puedes usar el método `modifyKeySelectUsing()` en el componente **MorphToSelect** en sí:

```php
MorphToSelect::make('commentable')
    ->types([
        MorphToSelect\Type::make(Product::class)
            ->titleAttribute('name'),
        MorphToSelect\Type::make(Post::class)
            ->titleAttribute('title'),
    ])
    ->modifyKeySelectUsing(fn (Select $select): Select => $select->native())
```

También puedes modificar el campo select de **"tipo"** usando el método `modifyTypeSelectUsing()`:

```php
MorphToSelect::make('commentable')
    ->types([
        MorphToSelect\Type::make(Product::class)
            ->titleAttribute('name'),
        MorphToSelect\Type::make(Post::class)
            ->titleAttribute('title'),
    ])
    ->modifyTypeSelectUsing(fn (Select $select): Select => $select->native())
```

---



### Usar botones de alternancia para el selector de tipo

Por defecto, el selector de tipo es un campo select. Puedes cambiarlo para que use botones de alternancia en línea usando el método `typeSelectToggleButtons()`:

```php
MorphToSelect::make('commentable')
    ->typeSelectToggleButtons()
    ->types([
        MorphToSelect\Type::make(Product::class)
            ->titleAttribute('name'),
        MorphToSelect\Type::make(Post::class)
            ->titleAttribute('title'),
    ])
```

Al usar botones de alternancia, puedes personalizarlos usando el método `modifyTypeSelectUsing()`:

```php
use Filament\Forms\Components\ToggleButtons;

MorphToSelect::make('commentable')
    ->typeSelectToggleButtons()
    ->types([
        MorphToSelect\Type::make(Product::class)
            ->titleAttribute('name'),
        MorphToSelect\Type::make(Post::class)
            ->titleAttribute('title'),
    ])
    ->modifyTypeSelectUsing(fn (ToggleButtons $toggleButtons): ToggleButtons => $toggleButtons->grouped())
```

Muchas de las mismas opciones del campo select están disponibles para **MorphToSelect**, incluyendo `searchable()`, `preload()`, `native()`, `allowHtml()` y `optionsLimit()`.

---



## Permitir HTML en las etiquetas de opciones

Por defecto, Filament escapará cualquier HTML en las etiquetas de opciones. Si deseas permitir HTML, puedes usar el método `allowHtml()`:

```php
Select::make('technology')
    ->options([
        'tailwind' => '<span class="text-blue-500">Tailwind</span>',
        'alpine' => '<span class="text-green-500">Alpine</span>',
        'laravel' => '<span class="text-red-500">Laravel</span>',
        'livewire' => '<span class="text-pink-500">Livewire</span>',
    ])
    ->searchable()
    ->allowHtml()
```

**Advertencia:** Asegúrate de que el HTML sea seguro de renderizar, de lo contrario, tu aplicación será vulnerable a ataques XSS.

Opcionalmente, puedes pasar un valor booleano para controlar si el campo debe permitir HTML o no:

```php
Select::make('technology')
    ->options([
        'tailwind' => '<span class="text-blue-500">Tailwind</span>',
        'alpine' => '<span class="text-green-500">Alpine</span>',
        'laravel' => '<span class="text-red-500">Laravel</span>',
        'livewire' => '<span class="text-pink-500">Livewire</span>',
    ])
    ->searchable()
    ->allowHtml(FeatureFlag::active())
```

El método `allowHtml()` también acepta una función para calcularlo dinámicamente.
[Más información sobre inyección de utilidades](#).

---



## Ajustar o truncar etiquetas de opciones

Al usar el select de JavaScript, las etiquetas que exceden el ancho del elemento select se ajustarán a múltiples líneas por defecto. Alternativamente, puedes elegir truncar las etiquetas que desbordan usando el método `wrapOptionLabels(false)`:

```php
Select::make('truncate')
    ->wrapOptionLabels(false)
```

El método `wrapOptionLabels()` también acepta una función para calcularlo dinámicamente.
[Más información sobre inyección de utilidades](#).

---



## Deshabilitar la selección del placeholder

Puedes evitar que el placeholder (opción nula) sea seleccionado usando el método `selectablePlaceholder(false)`:

```php
Select::make('status')
    ->options([
        'draft' => 'Borrador',
        'reviewing' => 'En revisión',
        'published' => 'Publicado',
    ])
    ->default('draft')
    ->selectablePlaceholder(false)
```

El método `selectablePlaceholder()` también acepta una función para calcularlo dinámicamente.
[Más información sobre inyección de utilidades](#).

---



## Deshabilitar opciones específicas

Puedes deshabilitar opciones específicas usando el método `disableOptionWhen()`. Este acepta un closure en el que puedes verificar si la opción con un `$value` específico debe deshabilitarse:

```php
Select::make('status')
    ->options([
        'draft' => 'Borrador',
        'reviewing' => 'En revisión',
        'published' => 'Publicado',
    ])
    ->default('draft')
    ->disableOptionWhen(fn (string $value): bool => $value === 'published')
```

Puedes inyectar varias utilidades en la función como parámetros.
[Más información sobre inyección de utilidades](#).

---



### Parámetros disponibles en `disableOptionWhen()`


| Variable     | Tipo                                             | Descripción                                                              |
| ------------ | ------------------------------------------------ | ------------------------------------------------------------------------ |
| `$component` | `Filament\Forms\Components\Field`                | La instancia actual del componente de campo.                             |
| `$get`       | `Filament\Schemas\Components\Utilities\Get`      | Una función para recuperar valores de los datos actuales del formulario. |
| `$label`     | `string | Illuminate\Contracts\Support\Htmlable` | La etiqueta de la opción a deshabilitar.                                 |
| `$livewire`  | `Livewire\Component`                             | La instancia del componente Livewire.                                    |
| `$model`     | `?string<Illuminate\Database\Eloquent\Model>`    | El FQN del modelo Eloquent para el esquema actual.                       |
| `$operation` | `string`                                         | La operación actual que se está realizando en el esquema.                |
| `$rawState`  | `mixed`                                          | El valor actual del campo, antes de aplicar los casts de estado.         |
| `$record`    | `?Illuminate\Database\Eloquent\Model`            | El registro Eloquent para el esquema actual.                             |
| `$state`     | `mixed`                                          | El valor actual del campo.                                               |
| `$value`     | `mixed`                                          | El valor de la opción a deshabilitar.                                    |


---



## Agregar texto de afijo junto al campo

Puedes colocar texto antes y después del campo usando los métodos `prefix()` y `suffix()`:

```php
Select::make('domain')
    ->prefix('https://')
    ->suffix('.com')
```

Los métodos `prefix()` y `suffix()` también aceptan una función para calcularlos dinámicamente.
[Más información sobre inyección de utilidades](#).

---



## Usar iconos como afijos

Puedes colocar un icono antes y después del campo usando los métodos `prefixIcon()` y `suffixIcon()`:

```php
use Filament\Forms\Components\Select;
use Filament\Support\Icons\Heroicon;

Select::make('domain')
    ->suffixIcon(Heroicon::GlobeAlt)
```

Los métodos `prefixIcon()` y `suffixIcon()` también aceptan una función para calcularlos dinámicamente.
[Más información sobre inyección de utilidades](#).

---



## Establecer el color del icono de afijo

Los iconos de afijo son grises por defecto, pero puedes establecer un color diferente usando los métodos `prefixIconColor()` y `suffixIconColor()`:

```php
Select::make('domain')
    ->suffixIcon(Heroicon::CheckCircle)
    ->suffixIconColor('success')
```

Los métodos `prefixIconColor()` y `suffixIconColor()` también aceptan una función para calcularlos dinámicamente.
[Más información sobre inyección de utilidades](#).

---



## Limitar el número de opciones

Puedes limitar el número de opciones que se muestran en un select buscable o multi-select usando el método `optionsLimit()`. El valor predeterminado es 50:

```php
Select::make('author_id')
    ->relationship(name: 'author', titleAttribute: 'name')
    ->searchable()
    ->optionsLimit(20)
```

**Advertencia:** No aumentes demasiado el límite, ya que esto puede hacer que el select se vuelva lento y no responda debido al alto uso de memoria en el navegador.

El método `optionsLimit()` también acepta una función para calcularlo dinámicamente.
[Más información sobre inyección de utilidades](#).

---



## Opciones booleanas

Si deseas un select booleano simple, con opciones **"Sí"** y **"No"**, puedes usar el método `boolean()`:

```php
Select::make('feedback')
    ->label('¿Te gusta esta publicación?')
    ->boolean()
```

Para personalizar la etiqueta **"Sí"**, puedes usar el argumento `trueLabel` en el método `boolean()`:

```php
Select::make('feedback')
    ->label('¿Te gusta esta publicación?')
    ->boolean(trueLabel: '¡Absolutamente!')
```

Para personalizar la etiqueta **"No"**, puedes usar el argumento `falseLabel` en el método `boolean()`:

```php
Select::make('feedback')
    ->label('¿Te gusta esta publicación?')
    ->boolean(falseLabel: '¡Para nada!')
```

Para personalizar el placeholder que se muestra cuando no se ha seleccionado una opción, puedes usar el argumento `placeholder` en el método `boolean()`:

```php
Select::make('feedback')
    ->label('¿Te gusta esta publicación?')
    ->boolean(placeholder: 'Decídete...')
```

---



## Seleccionar opciones de una tabla en un modal

Puedes usar el componente **ModalTableSelect** para abrir una tabla de Filament en un modal, permitiendo a los usuarios seleccionar registros de ella. Esto es útil cuando tienes una relación con muchos registros y deseas que los usuarios puedan realizar filtrado y búsqueda avanzada.

Para usar **ModalTableSelect**, debes tener una clase de configuración de tabla para el modelo. Puedes generar una de estas clases usando el comando `make:filament-table`:

```php
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('parent')
                    ->relationship('parent', 'name')
                    ->searchable()
                    ->preload(),
            ]);
    }
}
```

La clase debe tener un método `configure()` que acepte el objeto **Table** y lo devuelva. El nombre de la clase debe pasarse al método `tableConfiguration()` del componente **ModalTableSelect**:

```php
use Filament\Forms\Components\ModalTableSelect;

ModalTableSelect::make('category_id')
    ->relationship('category', 'name')
    ->tableConfiguration(CategoriesTable::class)
```

También puedes usar el método `multiple()` con una relación múltiple como **BelongsToMany**:

```php
ModalTableSelect::make('categories')
    ->relationship('categories', 'name')
    ->multiple()
    ->tableConfiguration(CategoriesTable::class)
```

---



### Personalizar las acciones del modal table select

Puedes personalizar el botón **"Seleccionar"** y el modal usando los métodos de configuración del objeto de acción. Pasar una función al método `selectAction()` te permite modificar el objeto `$action`, por ejemplo, para cambiar la etiqueta del botón y el encabezado del modal:

```php
use Filament\Actions\Action;
use Filament\Forms\Components\ModalTableSelect;

ModalTableSelect::make('category_id')
    ->relationship('category', 'name')
    ->tableConfiguration(CategoriesTable::class)
    ->selectAction(
        fn (Action $action) => $action
            ->label('Seleccionar una categoría')
            ->modalHeading('Buscar categorías')
            ->modalSubmitActionLabel('Confirmar selección'),
    )
```

El método `selectAction()` puede inyectar varias utilidades en la función como parámetros.
[Más información sobre inyección de utilidades](#).

---



### Personalizar las etiquetas de las opciones en el modal table select

El método `getOptionLabelFromRecordUsing()` puede usarse para personalizar la etiqueta de cada opción seleccionada. Esto es útil si deseas mostrar una etiqueta más descriptiva o concatenar dos columnas:

```php
ModalTableSelect::make('category_id')
    ->relationship('category', 'name')
    ->tableConfiguration(CategoriesTable::class)
    ->getOptionLabelFromRecordUsing(fn (Category $record): string => "{$record->name} ({$record->slug})")
```

Por defecto, las opciones `multiple()` se muestran en un diseño de **"insignia"**, y las opciones singulares se muestran en texto plano. El método `badge()` puede usarse para definir si la etiqueta de la opción debe aparecer dentro de una insignia:

```php
ModalTableSelect::make('category_id')
    ->relationship('category', 'name')
    ->tableConfiguration(CategoriesTable::class)
    ->badge()

ModalTableSelect::make('categories')
    ->relationship('categories', 'name')
    ->multiple()
    ->tableConfiguration(CategoriesTable::class)
    ->badge(false)
```

El método `badgeColor()` puede usarse para establecer el color de la insignia:

```php
ModalTableSelect::make('categories')
    ->relationship('categories', 'name')
    ->multiple()
    ->tableConfiguration(CategoriesTable::class)
    ->badgeColor('success')
```

El método `badgeColor()` también acepta una función para calcularlo dinámicamente.
[Más información sobre inyección de utilidades](#).

---



### Pasar argumentos adicionales a la tabla en un modal select

Puedes pasar argumentos de tu formulario a la clase de configuración de la tabla usando el método `tableArguments()`. Por ejemplo, esto puede usarse para modificar la consulta de la tabla según campos del formulario completados previamente:

```php
use Filament\Actions\Action;
use Filament\Forms\Components\ModalTableSelect;
use Filament\Schemas\Components\Utilities\Get;

ModalTableSelect::make('products')
    ->relationship('products', 'name')
    ->multiple()
    ->tableConfiguration(ProductsTable::class)
    ->tableArguments(function (Get $get): array {
        return [
            'category_id' => $get('category_id'),
            'budget_limit' => $get('budget'),
        ];
    })
```

El método `tableArguments()` puede inyectar varias utilidades en la función como parámetros.

En tu clase de configuración de tabla, puedes acceder a estos argumentos usando el método `$table->getArguments()`:

```php
use Filament\Forms\Components\TableSelect\Livewire\TableSelectLivewireComponent;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) use ($table): Builder {
                $arguments = $table->getArguments();

                if ($categoryId = $arguments['category_id'] ?? null) {
                    $query->where('category_id', $categoryId);
                }

                if ($budgetLimit = $arguments['budget_limit'] ?? null) {
                    $query->where('price', '<=', $budgetLimit);
                }

                return $query;
            })
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('price')
                    ->money(),
                TextColumn::make('category.name')
                    ->hidden(filled($table->getArguments()['category_id'])),
            ]);
    }
}
```

**Advertencia:** Filtrar la consulta de la tabla usando `modifyQueryUsing()` o `tableArguments()` es solo **presentacional**: solo afecta qué registros se muestran en la tabla para selección. **No es un límite de seguridad**: un usuario que manipule el estado del formulario enviado puede seleccionar un registro que fue excluido de la tabla visible, y aún pasará la validación y se guardará.
Para restringir qué registros pueden seleccionarse y guardarse realmente, limita la consulta de la relación del campo usando el argumento `modifyQueryUsing` del método `relationship()`. Filament valida los valores enviados contra esa consulta, por lo que los registros fuera de ella son rechazados:

```php
use Filament\Forms\Components\ModalTableSelect;
use Illuminate\Database\Eloquent\Builder;

ModalTableSelect::make('products')
    ->relationship(
        name: 'products',
        titleAttribute: 'name',
        modifyQueryUsing: fn (Builder $query) => $query->whereBelongsTo(auth()->user()),
    )
    ->multiple()
    ->tableConfiguration(ProductsTable::class)
```

---



## Validación del select

Además de todas las reglas listadas en la página de [validación](#), hay reglas adicionales específicas para los selects.

---



### Validación de opciones válidas (regla `in()`)

La regla `in()` asegura que los usuarios no puedan seleccionar una opción que no esté en la lista de opciones. Esta es una regla importante para la integridad de los datos, por lo que Filament la aplica por defecto a todos los campos select.

**La validación de opciones seleccionadas es crucial**, por lo que te recomendamos encarecidamente que escribas pruebas automatizadas para tus formularios para asegurarte de que la validación funcione como se espera.

Dado que hay muchas formas en que un campo select puede poblar sus opciones, y en muchos casos las opciones no se cargan todas en el select por defecto y requieren búsqueda para recuperarlas, Filament usa la presencia de una etiqueta de opción válida para determinar si el valor seleccionado existe. También verifica si esa opción está deshabilitada o no.

Si estás usando una consulta de búsqueda personalizada para recuperar opciones, debes asegurarte de que el método `getOptionLabelUsing()` esté definido, para que Filament pueda validar el valor seleccionado contra las opciones disponibles:

```php
Select::make('author_id')
    ->searchable()
    ->getSearchResultsUsing(fn (string $search): array => Author::query()
        ->where('name', 'like', "%{$search}%")
        ->limit(50)
        ->pluck('name', 'id')
        ->all())
    ->getOptionLabelUsing(fn (string $value): ?string => Author::find($value)?->name),
```

El método `getOptionLabelUsing()` debe devolver `null` si la opción no es válida, para permitir que Filament determine que el valor seleccionado no está en la lista de opciones. Si la opción es válida, debe devolver la etiqueta de la opción.

Si estás usando un select `multiple()` o multi-select, debes definir `getOptionLabelsUsing()` en lugar de `getOptionLabelUsing()`. `$values` se pasará al callback en lugar de `$value`, y debes devolver un array `$key => $value` de etiquetas y sus valores correspondientes:

```php
Select::make('technologies')
    ->multiple()
    ->searchable()
    ->getSearchResultsUsing(fn (string $search): array => Technology::query()
        ->where('name', 'like', "%{$search}%")
        ->limit(50)
        ->pluck('name', 'id')
        ->all())
    ->getOptionLabelsUsing(fn (array $values): array => Technology::query()
        ->whereIn('id', $values)
        ->pluck('name', 'id')
        ->all()),
```

Si estás usando el método `relationship()`, los métodos `getOptionLabelUsing()` o `getOptionLabelsUsing()` se definirán automáticamente por ti, por lo que no tienes que preocuparte por ellos.

---



### Validación del número de elementos seleccionados

Puedes validar el número mínimo y máximo de elementos que se pueden seleccionar en un multi-select usando los métodos `minItems()` y `maxItems()`:

```php
Select::make('technologies')
    ->multiple()
    ->options([
        'tailwind' => 'Tailwind CSS',
        'alpine' => 'Alpine.js',
        'laravel' => 'Laravel',
        'livewire' => 'Laravel Livewire',
    ])
    ->minItems(1)
    ->maxItems(3)
```

Los métodos `minItems()` y `maxItems()` también aceptan una función para calcularlos dinámicamente.
[Más información sobre inyección de utilidades](#).

---



## Personalizar los objetos de acción del select

Este campo usa objetos de acción para una personalización fácil de los botones dentro de él. Puedes personalizar estos botones pasando una función a un método de registro de acción. La función tiene acceso al objeto `$action`, que puedes usar para personalizarlo o personalizar su modal. Los siguientes métodos están disponibles para personalizar las acciones:

- `createOptionAction()`
- `editOptionAction()`
- `manageOptionActions()` (para personalizar tanto las acciones de crear como de editar opciones a la vez)

Aquí hay un ejemplo de cómo podrías personalizar una acción:

```php
use Filament\Actions\Action;
use Filament\Forms\Components\Select;

Select::make('author_id')
    ->relationship(name: 'author', titleAttribute: 'name')
    ->createOptionAction(
        fn (Action $action) => $action->modalWidth('3xl'),
    )
```

Los métodos de registro de acciones pueden inyectar varias utilidades en la función como parámetros.
[Más información sobre inyección de utilidades](#).

---



### Parámetros disponibles en los métodos de acción


| Variable     | Tipo                                          | Descripción                                                              |
| ------------ | --------------------------------------------- | ------------------------------------------------------------------------ |
| `$action`    | `Filament\Actions\Action`                     | El objeto de acción a personalizar.                                      |
| `$component` | `Filament\Forms\Components\Field`             | La instancia actual del componente de campo.                             |
| `$get`       | `Filament\Schemas\Components\Utilities\Get`   | Una función para recuperar valores de los datos actuales del formulario. |
| `$livewire`  | `Livewire\Component`                          | La instancia del componente Livewire.                                    |
| `$model`     | `?string<Illuminate\Database\Eloquent\Model>` | El FQN del modelo Eloquent para el esquema actual.                       |
| `$operation` | `string`                                      | La operación actual que se está realizando en el esquema.                |
| `$rawState`  | `mixed`                                       | El valor actual del campo, antes de aplicar los casts de estado.         |
| `$record`    | `?Illuminate\Database\Eloquent\Model`         | El registro Eloquent para el esquema actual.                             |
| `$state`     | `mixed`                                       | El valor actual del campo.                                               |


```

```

