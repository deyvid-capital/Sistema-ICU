# Páginas Personalizadas

## Introducción

Filament te permite crear páginas completamente personalizadas para tu aplicación.

> **Vista previa de carga:**  
> _Una página personalizada con acciones en el encabezado_

---

## Creación de una página

Para crear una nueva página, puedes usar el siguiente comando:

```bash
php artisan make:filament-page Settings
```

Este comando creará dos archivos:
- Una clase de página en el directorio `Filament/Pages`.
- Una vista en el directorio de vistas `Filament/Pages/pages`.

Las clases de página son componentes Livewire de página completa, con herramientas extra que puedes usar.

---

## Autorización

Puedes evitar que las páginas aparezcan en el menú sobrescribiendo el método `canAccess()` en tu clase de página. Esto es útil si quieres controlar qué usuarios pueden ver la página en la navegación y también qué usuarios pueden visitarla directamente:

```php
public static function canAccess(): bool
{
    return auth()->user()->canManageSettings();
}
```

---

## Añadir acciones a las páginas

Las acciones son **botones** que pueden realizar tareas en la página o visitar una URL. Puedes leer más sobre sus capacidades [aquí](#).  
Todas las páginas son componentes Livewire, puedes añadir acciones en cualquier lugar. Las páginas ya tienen el trait `InteractsWithActions`, la interfaz y el componente Blade configurados para ti.

```blade
<x-filament-actions::modals />
```

---

### Acciones de cabecera

También puedes añadir fácilmente acciones al encabezado de cualquier página, incluyendo páginas de recursos. Solo tienes que devolver las acciones desde el método `getHeaderActions()` de tu clase de página:

```php
use Filament\Actions\Action;

protected function getHeaderActions(): array
{
    return [
        Action::make('edit')
            ->url(route('posts.edit', ['post' => $this->post])),
        Action::make('delete')
            ->requiresConfirmation()
            ->action(fn () => $this->post->delete()),
    ];
}
```

---

### Alineando acciones de cabecera

Por defecto, las acciones de encabezado están alineadas a la izquierda en el móvil. Para cambiar la alineación, establece `$headerActionsAlignment`:

```php
use Filament\Support\Enums\Alignment;

protected ?Alignment $headerActionsAlignment = Alignment::End;
```

---

## Abrir un modal de acción al cargar la página

Puedes abrir una acción automáticamente cuando se carga una página, asignando `$defaultAction` al nombre de la acción:

```php
use Filament\Actions\Action;

public $defaultAction = 'onboarding';

public function onboardingAction(): Action
{
    return Action::make('onboarding')
        ->modalHeading('Welcome')
        ->visible(fn (): bool => ! auth()->user()->isOnBoarded());
}
```

Puedes pasar argumentos a la acción por defecto usando `$defaultActionArguments`:

```php
public $defaultActionArguments = ['step' => 2];
```

O también puedes abrir la acción mediante la cadena de consulta en la URL:

```
/admin/products/edit/932510?action=onboarding
```

---

## Actualización de datos de formularios

Si usas acciones en una página de **Edición** o **Vista** de recursos, puedes actualizar los datos dentro del formulario con el método `refreshFormData()`:

```php
use App\Models\Post;
use Filament\Actions\Action;

Action::make('approve')
    ->action(function (Post $record) {
        $record->approve();

        $this->refreshFormData([
            'status',
        ]);
    });
```

Este método acepta un array de atributos del modelo a actualizar en el formulario.

---

## Añadir widgets a las páginas

Filament permite mostrar **widgets** dentro de las páginas, debajo del encabezado y encima del pie de página.

Para añadir un widget, usa los métodos `getHeaderWidgets()` o `getFooterWidgets()`:

```php
use App\Filament\Widgets\StatsOverviewWidget;

protected function getHeaderWidgets(): array
{
    return [
        StatsOverviewWidget::class
    ];
}
```

- `getHeaderWidgets()` devuelve un array de widgets para mostrar sobre el contenido.
- `getFooterWidgets()` para mostrarlos debajo.

Para más información sobre widgets, revisa la sección Widgets de la documentación.

---

### Personalización de la cuadrícula de los widgets

Puedes cambiar cuántas columnas de la cuadrícula se usan para mostrar widgets, sobrescribiendo los métodos `getHeaderWidgetsColumns()` o `getFooterWidgetsColumns()`:

```php
public function getHeaderWidgetsColumns(): int | array
{
    return 3;
}
```

#### Cuadrícula de widgets responsiva

Puedes ajustar el número de columnas según el tamaño de la pantalla:

```php
public function getHeaderWidgetsColumns(): int | array
{
    return [
        'md' => 4,
        'xl' => 5,
    ];
}
```
Esto combina bien con anchos de widgets responsivos.

---

### Pasar datos a los widgets desde la página

Para pasar datos a los widgets, usa el método `getWidgetData()`:

```php
public function getWidgetData(): array
{
    return [
        'stats' => [
            'total' => 100,
        ],
    ];
}
```

En el widget, define la propiedad correspondiente como pública:

```php
public $stats = [];
```

---

### Pasar propiedades a los widgets en las páginas

Al registrar un widget, puedes usar `make()` para pasar un array de propiedades Livewire al widget:

```php
use App\Filament\Widgets\StatsOverviewWidget;

protected function getHeaderWidgets(): array
{
    return [
        StatsOverviewWidget::make([
            'status' => 'active',
        ]),
    ];
}
```

Estas propiedades serán accesibles como propiedades públicas en la clase del widget:

```php
use Filament\Widgets\Widget;

class StatsOverviewWidget extends Widget
{
    public string $status;

    // ...
}

// Acceso a la propiedad:
$this->status
```

---

## Personalización del título de la página

Por defecto, Filament genera el título automáticamente según el nombre de la página. Puedes sobrescribirlo:

```php
protected static ?string $title = 'Custom Page Title';
```

O devolviendo una cadena desde el método `getTitle()`:

```php
use Illuminate\Contracts\Support\Htmlable;

public function getTitle(): string | Htmlable
{
    return __('Custom Page Title');
}
```

---

## Personalización de la etiqueta de navegación

Por defecto, se utiliza el título de la página como la etiqueta de navegación. Puedes sobrescribirlo:

```php
protected static ?string $navigationLabel = 'Custom Navigation Label';
```

O mediante el método `getNavigationLabel()`:

```php
public static function getNavigationLabel(): string
{
    return __('Custom Navigation Label');
}
```

---

## Personalización de la URL de la página

Por defecto, se genera un slug basado en el nombre. Puedes sobrescribirlo:

```php
protected static ?string $slug = 'custom-url-slug';
```

---

## Personalización del encabezado de la página

Por defecto, se usa el título como encabezado. Puedes sobrescribirlo:

```php
protected ?string $heading = 'Custom Page Heading';
```

O mediante el método `getHeading()`:

```php
public function getHeading(): string
{
    return __('Custom Page Heading');
}
```

---

## Añadir un subtítulo de página

Puedes añadir un subtítulo definiendo la propiedad `$subheading`:

```php
protected ?string $subheading = 'Custom Page Subheading';
```

O sobrescribiendo `getSubheading()`:

```php
public function getSubheading(): ?string
{
    return __('Custom Page Subheading');
}
```

> **Vista previa de carga:**  
> _Una página personalizada con un subtítulo_

---

## Sustituir el encabezado de página por una vista personalizada

Puedes reemplazar el encabezado por una vista personalizada devolviendo la vista desde el método `getHeader()`:

```php
use Illuminate\Contracts\View\View;

public function getHeader(): ?View
{
    return view('filament.settings.custom-header');
}
```

Este ejemplo asume que tienes la vista en `resources/views/filament/settings/custom-header.blade.php`.

---

## Renderizado de una vista personalizada en el pie de página

Añade un pie de página devolviendo la vista desde el método `getFooter()`:

```php
use Illuminate\Contracts\View\View;

public function getFooter(): ?View
{
    return view('filament.settings.custom-footer');
}
```

Se asume que la vista está en `resources/views/filament/settings/custom-footer.blade.php`.

---

## Personalización del ancho máximo de contenido

Filament limita el ancho del contenido por defecto. Para cambiarlo, sobrescribe el método `getMaxContentWidth()`. Las opciones corresponden a los anchos de Tailwind CSS:

```php
use Filament\Support\Enums\Width;

public function getMaxContentWidth(): Width
{
    return Width::Full;
}
```
> Valores posibles: `ExtraSmall`, `Small`, `Medium`, `Large`, `ExtraLarge`, `TwoExtraLarge`, ... , `Full`, etc.

---

## Generación de URLs a páginas

Filament provee el método estático `getUrl()` en las clases de página para generar URLs.  
Por ejemplo:

```php
use App\Filament\Pages\Settings;

// Sin parámetros
Settings::getUrl(); // /admin/settings

// Con parámetros
Settings::getUrl(['section' => 'notifications']); // /admin/settings?section=notifications
```

---

### Generación de URLs en otros paneles

Si tienes múltiples paneles, puedes pasar el ID del panel al argumento `panel`:

```php
use App\Filament\Pages\Settings;

Settings::getUrl(panel: 'marketing');
```

---

## Añadiendo subnavegación entre páginas

Puedes añadir subnavegación común a varias páginas definiendo un **Cluster**. Los clusters pueden incluir recursos, y navegar entre páginas o recursos dentro de un grupo.

---

### Establecimiento de la posición de subnavegación

La subnavegación se muestra por defecto al principio de la página. Puedes cambiar la posición estableciendo la propiedad `$subNavigationPosition`. Los valores pueden ser `SubNavigationPosition::Start`, `SubNavigationPosition::End`, o `SubNavigationPosition::Top` para pestañas:

```php
use Filament\Pages\Enums\SubNavigationPosition;

protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::End;
```

> **Vista previa:**  
> _Página con subnavegación de tipo End o Top (como pestañas sobre el contenido)_

---

## Añadir atributos extra al `<body>`

Puedes añadir atributos extra a la etiqueta `<body>` estableciendo el array `$extraBodyAttributes`:

```php
protected array $extraBodyAttributes = [];
```

O devolviendo un array desde el método `getExtraBodyAttributes()`:

```php
public function getExtraBodyAttributes(): array
{
    return [
        'class' => 'settings-page',
    ];
}
}