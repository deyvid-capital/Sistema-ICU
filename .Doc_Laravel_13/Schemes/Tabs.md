
# Tabs

## Introducción

Algunos esquemas pueden ser largos y complejos. Quizá quieras usar pestañas para reducir el número de componentes visibles a la vez:

```php
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

Tabs::make('Tabs')
    ->tabs([
        Tab::make('Tab 1')
            ->schema([
                // ...
            ]),
        Tab::make('Tab 2')
            ->schema([
                // ...
            ]),
        Tab::make('Tab 3')
            ->schema([
                // ...
            ]),
    ]);
```

Además de permitir un valor estático, el método `make()` también acepta una función para calcularlo dinámicamente. Puedes inyectar varias utilidades en la función como parámetros.

**Servicios inyectables:**

- **$component:** `Filamento\Esquemas\Componentes\Componente`<br>
  La instancia actual del componente.
- **$get:** `Filamento\Esquemas\Componentes\Utilidades\Get`<br>
  Función para recuperar valores de los datos actuales del esquema. La validación no se ejecuta en campos de formulario.
- **$livewire:** `Livewire\Component`<br>
  La instancia de componente Livewire.
- **$model:** `?cadena<Iluminar\Base de Datos\Elocuente\Modelo>`<br>
  El modelo Eloquent FQN para el esquema actual.
- **$operation:** `string`<br>
  La operación actual la realiza el esquema. Normalmente `create`, `edit` o `view`.
- **$record:** `? Illuminate\Database\Eloquent\Model`<br>
  El registro elocuente para el esquema actual.

---

## Establecer la pestaña activa predeterminada

La primera pestaña estará abierta por defecto. Puedes cambiar la pestaña abierta por defecto usando el método `activeTab()`:

```php
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

Tabs::make('Tabs')
    ->tabs([
        Tab::make('Tab 1')->schema([
            // ...
        ]),
        Tab::make('Tab 2')->schema([
            // ...
        ]),
        Tab::make('Tab 3')->schema([
            // ...
        ]),
    ])
    ->activeTab(2);
```

Este método también acepta una función para calcular dinámicamente la pestaña activa y admite inyección de dependencias.

---

## Establecer un icono de tabulación

Las pestañas pueden tener un icono, que puedes establecer usando el método `icon()`:

```php
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;

Tabs::make('Tabs')
    ->tabs([
        Tab::make('Notifications')
            ->icon(Heroicon::Bell)
            ->schema([
                // ...
            ]),
        // ...
    ]);
```

También puedes pasar una función dinámica, con los mismos parámetros inyectables.

---

## Configuración de la posición del icono de pestañas

El icono de la pestaña puede colocarse antes o después de la etiqueta con el método `iconPosition()`:

```php
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Icons\Heroicon;

Tabs::make('Tabs')
    ->tabs([
        Tab::make('Notifications')
            ->icon(Heroicon::Bell)
            ->iconPosition(IconPosition::After)
            ->schema([
                // ...
            ]),
        // ...
    ]);
```

Admite función dinámica y servicios inyectables.

---

## Establecer una insignia de pestaña

Las pestañas pueden tener una insignia con `badge()`:

```php
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

Tabs::make('Tabs')
    ->tabs([
        Tab::make('Notifications')
            ->badge(5)
            ->schema([
                // ...
            ]),
        // ...
    ]);
```

Puedes cambiar el color de la insignia usando `badgeColor()`:

```php
Tabs::make('Tabs')
    ->tabs([
        Tab::make('Notifications')
            ->badge(5)
            ->badgeColor('info')
            ->schema([
                // ...
            ]),
        // ...
    ]);
```

Parámetros inyectables para funciones dinámicas en ambos métodos.

- **$badge:** `?string` - El valor evaluado de la insignia.
- **$component, $get, $livewire, $model, $operation, $record:** igual que se explicó anteriormente.

---

### Aplazamiento de la carga de insignias de pestañas

Cuando la consulta es costosa, puedes usar `deferBadge()` para obtener el valor de la insignia de manera diferida:

```php
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

Tabs::make('Tabs')
    ->key('notifications-tabs')
    ->tabs([
        Tab::make('Notifications')
            ->badge(static fn (): int => Notification::query()->where('unread', true)->count())
            ->deferBadge()
            ->schema([
                // ...
            ]),
        // ...
    ]);
```

- El valor debe ser retornado por una función usando `badge()`.
- Es obligatorio definir un `key()` cuando se usa `deferBadge()` para identificar el componente.
- Admite parámetros inyectables.

Mientras las insignias esperan su carga, aparecerá un pequeño indicador de carga.

---

## Uso de columnas de cuadrícula dentro de una pestaña

Puedes personalizar el grid dentro de una pestaña con el método `columns()`:

```php
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

Tabs::make('Tabs')
    ->tabs([
        Tab::make('Tab 1')
            ->schema([
                // ...
            ])
            ->columns(3),
        // ...
    ]);
```

Admite cálculo dinámico con parámetros inyectables.

---

## Desactivar las pestañas desplazables

Las pestañas se muestran horizontalmente y, por defecto, son desplazables si hay muchas. Puedes desactivar el desplazamiento así:

```php
use Filament\Schemas\Components\Tabs;

Tabs::make('Tabs')
    ->tabs([
        // ...
    ])
    ->scrollable(false);
```

Si no caben todas, aparecerá un desplegable.

---

## Uso de pestañas verticales

Puedes renderizar las pestañas en vertical usando `vertical()`:

```php
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

Tabs::make('Tabs')
    ->tabs([
        Tab::make('Tab 1')->schema([/*...*/]),
        Tab::make('Tab 2')->schema([/*...*/]),
        Tab::make('Tab 3')->schema([/*...*/]),
    ])
    ->vertical();
```

Se puede pasar un booleano o una función. Admite servicios inyectables.

---

## Retirar el contenedor con estilo

Las pestañas y su contenido se envuelven en un contenedor estilo tarjeta por defecto. Puedes retirarlo usando `contained(false)`:

```php
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

Tabs::make('Tabs')
    ->tabs([
        Tab::make('Tab 1')->schema([/*...*/]),
        Tab::make('Tab 2')->schema([/*...*/]),
        Tab::make('Tab 3')->schema([/*...*/]),
    ])
    ->contained(false);
```

Método dinámico, admite servicios inyectables.

---

## Persistir la pestaña actual en la sesión del usuario

Por defecto, la pestaña actual **no** se guarda en el almacenamiento local del navegador. Puedes habilitarlo con `persistTab()`, además de un `id()` único para el set de tabs:

```php
use Filament\Schemas\Components\Tabs;

Tabs::make('Tabs')
    ->tabs([
        // ...
    ])
    ->persistTab()
    ->id('order-tabs');
```

Puedes pasar un booleano o función a `persistTab()`. Ambos métodos permiten cálculo dinámico e inyección de servicios.

---

## Persistir la pestaña actual en la cadena de consulta de la URL

Puedes guardar la pestaña actual en la URL usando `persistTabInQueryString()`:

```php
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

Tabs::make('Tabs')
    ->tabs([
        Tab::make('Tab 1')->schema([/*...*/]),
        Tab::make('Tab 2')->schema([/*...*/]),
        Tab::make('Tab 3')->schema([/*...*/]),
    ])
    ->persistTabInQueryString();
```

Puedes definir la clave de la query string así:

```php
Tabs::make('Tabs')
    ->tabs([
        Tab::make('Tab 1')->schema([/*...*/]),
        Tab::make('Tab 2')->schema([/*...*/]),
        Tab::make('Tab 3')->schema([/*...*/]),
    ])
    ->persistTabInQueryString('settings-tab');
```

Opciones dinámicas disponibles como siempre, con posibilidad de inyección de servicios.

**Servicios inyectables para todos los métodos que aceptan funciones:**
- `$component`
- `$get`
- `$livewire`
- `$model`
- `$operation`
- `$record`

Infórmate más sobre la [inyección de servicios públicos](#).