# Búsqueda global

## Introducción

La búsqueda global te permite buscar en todos tus registros de recursos, desde cualquier lugar de la aplicación.

---

## Establecer títulos globales de resultados de búsqueda

Para habilitar la búsqueda global en tu modelo, debes establecer un atributo de título para tu recurso:

```php
protected static ?string $recordTitleAttribute = 'title';
```

Este atributo se utiliza para recuperar el título del resultado de búsqueda de ese registro.

Tu recurso necesita tener una página de **Editar** o **Ver** para permitir que los resultados de búsqueda global enlacen a una URL. De lo contrario, no se devolverán resultados para este recurso.

Puedes personalizar aún más el título sobrescribiendo el método `getGlobalSearchResultTitle()`. Este método puede devolver una cadena de texto plano, o una instancia de [`Illuminate\Support\HtmlString`](https://laravel.com/docs/9.x/helpers#method-str-html) o [`Illuminate\Contracts\Support\Htmlable`](https://laravel.com/api/9.x/Illuminate/Contracts/Support/Htmlable.html). Esto te permite renderizar HTML o incluso Markdown en el título del resultado de búsqueda.

```php
use Illuminate\Contracts\Support\Htmlable;

public static function getGlobalSearchResultTitle(Model $record): string | Htmlable
{
    return $record->name;
}
```

---

## Búsqueda global en múltiples columnas

Si quieres buscar en varias columnas de tu recurso, puedes anular el método `getGloballySearchableAttributes()`. La "notación punto" te permite buscar dentro de las relaciones:

```php
public static function getGloballySearchableAttributes(): array
{
    return ['title', 'slug', 'author.name', 'category.name'];
}
```

---

## Añadir detalles adicionales a los resultados de búsqueda globales

Los resultados de búsqueda pueden mostrar "detalles" debajo de su título, proporcionando así más información al usuario. Para activar esta función, debes anular el método `getGlobalSearchResultDetails()`:

```php
public static function getGlobalSearchResultDetails(Model $record): array
{
    return [
        'Author' => $record->author->name,
        'Category' => $record->category->name,
    ];
}
```

En este ejemplo, la categoría y el autor del registro se mostrarán debajo de su título en el resultado de búsqueda.

> **Nota:** Las relaciones `category` y `author` serán cargadas de forma perezosa (*lazy loading*), lo que puede afectar el rendimiento. Para usar *eager loading* y optimizar los resultados, debes sobrescribir el método `getGlobalSearchEloquentQuery()`:

```php
public static function getGlobalSearchEloquentQuery(): Builder
{
    return parent::getGlobalSearchEloquentQuery()->with(['author', 'category']);
}
```

---

## Personalización de URLs globales de resultados de búsqueda

Los resultados globales de búsqueda enlazarán con la página de **Editar** de tu recurso, o la página de **Ver** si el usuario no tiene permisos de edición. Para personalizar esto, puedes anular el método `getGlobalSearchResultUrl()` y devolver la ruta que elijas:

```php
public static function getGlobalSearchResultUrl(Model $record): string
{
    return UserResource::getUrl('edit', ['record' => $record]);
}
```

---

## Añadir acciones a los resultados de búsqueda globales

La búsqueda global soporta acciones, que son botones mostrados debajo de cada resultado de búsqueda. Estos botones pueden abrir una URL o despachar un evento de Livewire.

Las acciones pueden definirse así:

```php
use Filament\Actions\Action;

public static function getGlobalSearchResultActions(Model $record): array
{
    return [
        Action::make('edit')
            ->url(static::getUrl('edit', ['record' => $record])),
    ];
}
```

Puedes aprender más sobre cómo estilizar los botones de acción [aquí](#).

---

## Abrir URLs desde acciones de búsqueda globales

Puedes abrir una URL, opcionalmente en una pestaña nueva, al hacer clic en una acción:

```php
use Filament\Actions\Action;

Action::make('view')
    ->url(static::getUrl('view', ['record' => $record]), shouldOpenInNewTab: true)
```

---

## Despacho de eventos Livewire desde acciones de búsqueda globales

Si quieres ejecutar código adicional al hacer clic en una acción, puedes configurar un evento Livewire. Opcionalmente, puedes pasar un array de datos como parámetros al evento en tu componente Livewire:

```php
use Filament\Actions\Action;

Action::make('quickView')
    ->dispatch('quickView', [$record->id])
```

---

## Limitación del número de resultados globales de búsqueda

Por defecto, la búsqueda global devolverá hasta 50 resultados por recurso. Puedes personalizar esto sobrescribiendo la propiedad `$globalSearchResultsLimit` en la clase del recurso:

```php
protected static int $globalSearchResultsLimit = 20;
```

---

## Moviendo la búsqueda global a la barra lateral

Por defecto, el campo de búsqueda global está situado en la barra superior. Si la barra superior está desactivada, se añade a la barra lateral. Puedes elegir moverlo siempre a la barra lateral pasando un argumento al método `globalSearch()` en la configuración:

```php
use Filament\Enums\GlobalSearchPosition;
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->globalSearch(position: GlobalSearchPosition::Sidebar);
}
```

---

## Ordenación de resultados de búsqueda globales

Por defecto, los resultados globales de búsqueda están ordenados alfabéticamente por nombre de recurso. Puedes personalizar esto configurando la propiedad `$globalSearchSort` en tu recurso:

```php
protected static ?int $globalSearchSort = 3;
```

Los elementos de navegación con un valor de ordenamiento más bajo aparecerán antes que aquellos con un valor más alto (orden ascendente).

---

## Deshabilitando la búsqueda global

La búsqueda global se activa automáticamente al configurar un atributo de título para tu recurso. Si prefieres desactivarla, puedes hacerlo en la configuración:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->globalSearch(false);
}
```

---

## Exigir que los recursos opten por la búsqueda global

Por defecto, todos los recursos con un atributo del título se incluyen en los resultados de búsqueda globales. Si prefieres que los recursos opten explícitamente, puedes usar el método `globalSearchResourceOptIn()` en la configuración:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->globalSearchResourceOptIn();
}
```

Ahora, solo los recursos que configuren explícitamente `$isGloballySearchable = true` serán incluidos en la búsqueda global:

```php
protected static bool $isGloballySearchable = true;
```

---

## Registro de asignaciones globales de teclas de búsqueda

El campo de búsqueda global puede abrirse usando atajos de teclado. Para configurarlos, pasa el método `globalSearchKeyBindings()` en la configuración:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->globalSearchKeyBindings(['command+k', 'ctrl+k']);
}
```

---

## Configuración del debounce global de búsqueda

La búsqueda global tiene un tiempo de debounce por defecto de 500 ms. Puedes modificarlo usando el método `globalSearchDebounce()` en la configuración:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->globalSearchDebounce('750ms');
}
```

---

## Configuración del sufijo global de campo de búsqueda

El campo de búsqueda global por defecto no incluye ningún sufijo. Puedes personalizarlo usando `globalSearchFieldSuffix()` en la configuración.

Para mostrar la asignación actual de teclas, puedes usar el método `globalSearchFieldKeyBindingSuffix()`:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->globalSearchFieldKeyBindingSuffix();
}
```

También puedes personalizar el sufijo usando una función o cadena. Por ejemplo, para mostrar un sufijo personalizado según la plataforma:

```php
use Filament\Panel;
use Filament\Support\Enums\Platform;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->globalSearchFieldSuffix(fn (): ?string => match (Platform::detect()) {
            Platform::Windows, Platform::Linux => 'CTRL+K',
            Platform::Mac => '⌘K',
            default => null,
        });
}
```

---

## Desactivando la división de términos de búsqueda

Por defecto, la búsqueda global divide el término de búsqueda en palabras individuales y busca cada palabra por separado, haciendo las consultas más flexibles. Puedes desactivar este comportamiento configurando la propiedad `$shouldSplitGlobalSearchTerms` en el recurso:

```php
protected static ?bool $shouldSplitGlobalSearchTerms = false;
```