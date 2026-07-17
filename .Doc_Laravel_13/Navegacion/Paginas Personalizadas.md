
---

```markdown
# Páginas Personalizadas en Filament

---

## Introducción
Filament te permite crear **páginas completamente personalizadas** para tu aplicación.

---
![Vista previa de una página personalizada con acciones en el encabezado](https://via.placeholder.com/600x400)

---

## Creación de una página
Para crear una nueva página, utiliza el comando Artisan:

```bash
php artisan make:filament-page Settings
```

Este comando generará dos archivos:
- Una **clase de página** en el directorio `app/Filament/Pages/`.
- Una **vista Blade** en el directorio `resources/views/filament/pages/`.

**Nota:** Las clases de página son **componentes Livewire de página completa** con herramientas adicionales que puedes usar en el panel.

---

## Autorización
Puedes controlar qué usuarios pueden acceder a una página sobrescribiendo el método `canAccess()` en tu clase de página. Esto es útil para:
- Controlar qué usuarios pueden **ver la página en el menú de navegación**.
- Controlar qué usuarios pueden **visitar la página directamente**.

### Ejemplo:
```php
public static function canAccess(): bool
{
    return auth()->user()->canManageSettings();
}
```

---

## Añadir acciones a las páginas
Las **acciones** son botones que pueden realizar tareas en la página o redirigir a una URL. Dado que todas las páginas son componentes Livewire, puedes añadir acciones en cualquier lugar.

Filament ya configura automáticamente:
- El **trait** `InteractsWithActions`.
- La **interfaz** `HasActions`.
- El **componente Blade** `<x-filament-actions::modals />`.

---

### Acciones en el encabezado
Puedes añadir acciones al **encabezado** de cualquier página (incluyendo páginas de recursos) sin modificar la plantilla Blade. Simplemente devuelve tus acciones desde el método `getHeaderActions()`:

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
### Alineación de acciones en el encabezado
Por defecto, las acciones del encabezado están alineadas a la **izquierda en móviles**. Para cambiar la alineación, define la propiedad `$headerActionsAlignment`:

```php
use Filament\Support\Enums\Alignment;

protected ?Alignment $headerActionsAlignment = Alignment::End; // Alineación a la derecha
```

---

## Abrir un modal de acción al cargar la página
Puedes abrir automáticamente un modal de acción al cargar la página asignando el nombre de la acción a la propiedad `$defaultAction`:

```php
use Filament\Actions\Action;

public $defaultAction = 'onboarding';

public function onboardingAction(): Action
{
    return Action::make('onboarding')
        ->modalHeading('Bienvenido')
        ->visible(fn (): bool => !auth()->user()->isOnBoarded());
}
```

### Pasar argumentos a la acción por defecto
Puedes pasar un array de argumentos a la acción usando la propiedad `$defaultActionArguments`:

```php
public $defaultActionArguments = ['step' => 2];
```

### Abrir un modal mediante la URL
También puedes abrir un modal al cargar la página pasando el parámetro `action` en la URL:
```
/admin/products/edit/932510?action=onboarding
```

---
## Actualización de datos de formularios
Si estás usando acciones en una página de **Edición** o **Vista** de recursos, puedes actualizar los datos del formulario principal usando el método `refreshFormData()`:

```php
use App\Models\Post;
use Filament\Actions\Action;

Action::make('approve')
    ->action(function (Post $record) {
        $record->approve();

        $this->refreshFormData([
            'status', // Atributos del modelo a actualizar
        ]);
    });
```

---
## Añadir widgets a las páginas
Filament permite mostrar **widgets** dentro de las páginas, **debajo del encabezado** y **encima del pie de página**.

### Añadir widgets al encabezado o pie de página
Usa los métodos `getHeaderWidgets()` o `getFooterWidgets()`:

```php
use App\Filament\Widgets\StatsOverviewWidget;

protected function getHeaderWidgets(): array
{
    return [
        StatsOverviewWidget::class,
    ];
}
```
- `getHeaderWidgets()`: Muestra widgets **encima del contenido** de la página.
- `getFooterWidgets()`: Muestra widgets **debajo del contenido** de la página.

**Nota:** Para aprender a crear y personalizar widgets, consulta la sección de [Widgets en la documentación](https://filamentphp.com/docs).

---
### Personalización de la cuadrícula de widgets
Puedes cambiar el número de **columnas** en la cuadrícula de widgets sobrescribiendo los métodos `getHeaderWidgetsColumns()` o `getFooterWidgetsColumns()`:

```php
public function getHeaderWidgetsColumns(): int | array
{
    return 3; // 3 columnas
}
```

---
### Cuadrícula de widgets responsiva
Puedes ajustar el número de columnas según el **punto de ruptura** (breakpoint) del navegador usando un array:

```php
public function getHeaderWidgetsColumns(): int | array
{
    return [
        'md' => 4, // 4 columnas en pantallas medianas
        'xl' => 5, // 5 columnas en pantallas grandes
    ];
}
```
**Nota:** Esto funciona bien con **anchos de widgets responsivos**.

---
### Pasar datos a los widgets desde la página
Puedes pasar datos a los widgets usando el método `getWidgetData()`:

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
En el widget, define una propiedad pública con el mismo nombre para recibir los datos:
```php
public $stats = [];
```

---
### Pasar propiedades a los widgets
Al registrar un widget, puedes pasar propiedades usando el método `make()`:

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
En el widget, define la propiedad pública para recibir el valor:
```php
use Filament\Widgets\Widget;

class StatsOverviewWidget extends Widget
{
    public string $status;

    // ...
}
```
Ahora puedes acceder a `$this->status` en el widget.

---
## Personalización del título de la página
Por defecto, Filament genera automáticamente un título para tu página basado en su nombre. Puedes personalizarlo:

### Opción 1: Usar una propiedad estática
```php
protected static ?string $title = 'Título Personalizado';
```

### Opción 2: Usar un método
```php
use Illuminate\Contracts\Support\Htmlable;

public function getTitle(): string | Htmlable
{
    return __('Título Personalizado');
}
```

---
## Personalización de la etiqueta de navegación
Por defecto, Filament usa el **título de la página** como etiqueta en el menú de navegación. Puedes personalizarlo:

### Opción 1: Usar una propiedad estática
```php
protected static ?string $navigationLabel = 'Etiqueta de Navegación Personalizada';
```

### Opción 2: Usar un método
```php
public static function getNavigationLabel(): string
{
    return __('Etiqueta de Navegación Personalizada');
}
```

---
## Personalización de la URL de la página
Por defecto, Filament genera una URL (slug) para tu página basada en su nombre. Puedes personalizarla:

```php
protected static ?string $slug = 'url-personalizada';
```

---
## Personalización del encabezado de la página
Por defecto, Filament usa el **título de la página** como encabezado (`<h1>`). Puedes personalizarlo:

### Opción 1: Usar una propiedad
```php
protected ?string $heading = 'Encabezado Personalizado';
```

### Opción 2: Usar un método
```php
public function getHeading(): string
{
    return __('Encabezado Personalizado');
}
```

---
## Añadir un subtítulo a la página
Puedes añadir un subtítulo a tu página:

### Opción 1: Usar una propiedad
```php
protected ?string $subheading = 'Subtítulo Personalizado';
```

### Opción 2: Usar un método
```php
public function getSubheading(): ?string
{
    return __('Subtítulo Personalizado');
}
```

---
## Reemplazar el encabezado con una vista personalizada
Puedes reemplazar el **título**, **subtítulo** y **acciones** por una vista personalizada usando el método `getHeader()`:

```php
use Illuminate\Contracts\View\View;

public function getHeader(): ?View
{
    return view('filament.settings.custom-header');
}
```
**Nota:** Asegúrate de que la vista exista en `resources/views/filament/settings/custom-header.blade.php`.

---
## Renderizar una vista personalizada en el pie de página
Puedes añadir un **pie de página** personalizado debajo del contenido de la página usando el método `getFooter()`:

```php
use Illuminate\Contracts\View\View;

public function getFooter(): ?View
{
    return view('filament.settings.custom-footer');
}
```
**Nota:** Asegúrate de que la vista exista en `resources/views/filament/settings/custom-footer.blade.php`.

---
## Personalización del ancho máximo de contenido
Por defecto, Filament limita el ancho del contenido en la página para evitar que sea demasiado amplio en pantallas grandes. Puedes cambiar esto sobrescribiendo el método `getMaxContentWidth()`. Las opciones corresponden a la escala de **Tailwind CSS**:

- `ExtraSmall`, `Small`, `Medium`, `Large`, `ExtraLarge`
- `TwoExtraLarge`, `ThreeExtraLarge`, `FourExtraLarge`, `FiveExtraLarge`, `SixExtraLarge`, `SevenExtraLarge`
- `Full`, `MinContent`, `MaxContent`, `FitContent`, `Prose`
- `ScreenSmall`, `ScreenMedium`, `ScreenLarge`, `ScreenExtraLarge`, `ScreenTwoExtraLarge`

**Valor por defecto:** `SevenExtraLarge`

### Ejemplo:
```php
use Filament\Support\Enums\Width;

public function getMaxContentWidth(): Width
{
    return Width::Full; // Ancho completo
}
```

---
## Generación de URLs a páginas
Filament proporciona un método estático `getUrl()` en las clases de página para generar URLs hacia ellas. Esto evita tener que construir la URL manualmente o usando el helper `route()` de Laravel.

### Ejemplo básico:
```php
use App\Filament\Pages\Settings;

Settings::getUrl(); // /admin/settings
```

### Con parámetros de consulta:
```php
Settings::getUrl(['section' => 'notifications']); // /admin/settings?section=notifications
```

---
### Generación de URLs a páginas en otros paneles
Si tienes **múltiples paneles**, `getUrl()` generará una URL dentro del panel actual. Para generar una URL en otro panel, pasa el `ID del panel`:

```php
Settings::getUrl(panel: 'marketing'); // /marketing/settings
```

---
## Añadir subnavegación entre páginas
Puedes añadir una **subnavegación común** a varias páginas para permitir a los usuarios navegar rápidamente entre ellas. Esto se hace definiendo un **clúster** (agrupación de páginas y recursos).

---
### Posición de la subnavegación
Por defecto, la subnavegación se renderiza al **inicio de la página**. Puedes cambiar su posición usando la propiedad `$subNavigationPosition`:

```php
use Filament\Pages\Enums\SubNavigationPosition;

protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::End; // Al final
```
**Opciones:**
- `SubNavigationPosition::Start` (inicio).
- `SubNavigationPosition::End` (final).
- `SubNavigationPosition::Top` (como pestañas en la parte superior).

---
## Añadir atributos extra a la etiqueta `<body>`
Puedes añadir atributos adicionales a la etiqueta `<body>` de una página:

### Opción 1: Usar una propiedad
```php
protected array $extraBodyAttributes = [
    'class' => 'settings-page',
];
```

### Opción 2: Usar un método
```php
public function getExtraBodyAttributes(): array
{
    return [
        'class' => 'settings-page',
    ];
}
```

---
```

---
