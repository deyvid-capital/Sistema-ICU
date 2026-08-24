# Configuración del Panel

---

## Introducción

Por defecto, el archivo de configuración del panel se encuentra en `app/Providers/Filament/AdminPanelProvider.php`. Continúa leyendo para aprender más sobre los paneles y cómo cada uno tiene su propio archivo de configuración.

---

## ¿Qué son los paneles?

Cuando instalas Filament, se configura un panel por defecto accesible en `/admin`. Todos los recursos, páginas personalizadas y widgets que crees se registrarán en este panel.

Sin embargo, **puedes crear tantos paneles como necesites**, y cada uno puede tener su propio conjunto de recursos, páginas y widgets. Por ejemplo:

- Un panel para usuarios en `/app` donde puedan acceder a su dashboard.
- Un panel para administradores en `/admin` donde puedan gestionar la aplicación.

Cada panel puede tener **recursos diferentes**, ya que cada grupo de usuarios tiene requisitos distintos. Filament te permite hacerlo mediante la creación de múltiples paneles.

---



## El panel de administración por defecto

Cuando ejecutas `php artisan filament:install`, se crea un nuevo archivo en `app/Providers/Filament/AdminPanelProvider.php`. Este archivo contiene la configuración del panel `/admin`.

Cuando esta documentación se refiere a la **"configuración"**, este es el archivo que debes editar. Te permite personalizar completamente la aplicación.

---



## Crear un nuevo panel

Para crear un nuevo panel, usa el comando `make:filament-panel`, pasando el nombre único del nuevo panel:

```bash
php artisan make:filament-panel app
```

Este comando creará un nuevo panel llamado **"app"**. Se generará un archivo de configuración en `app/Providers/Filament/AppPanelProvider.php`. Podrás acceder a este panel en `/app`, pero puedes personalizar la ruta si lo deseas.

Dado que este archivo de configuración también es un **proveedor de servicios de Laravel**, debe registrarse en:

- `bootstrap/providers.php` (para Laravel 11 o superior).
- `config/app.php` (para Laravel 10 o inferior).

Filament intentará hacerlo automáticamente, pero si obtienes un error al intentar acceder al panel, es probable que este proceso haya fallado.

---



## Cambiar la ruta del panel

En el archivo de configuración del panel, puedes cambiar la ruta de acceso usando el método `path()`:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->path('app'); // El panel será accesible en /app
}
```

Si deseas que el panel sea accesible **sin prefijo**, puedes establecer una cadena vacía:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->path(''); // Accesible en la raíz del dominio
}
```

**Nota:** Asegúrate de que tu archivo `routes/web.php` no defina ya la ruta `''` o `'/'`, ya que tendría prioridad.

---



## Ganchos de renderizado (Render Hooks)

Los **ganchos de renderizado** te permiten insertar contenido Blade en diferentes puntos de las vistas del framework. Puedes registrar ganchos globales en un proveedor de servicios o middleware, pero también puedes registrar ganchos específicos para un panel usando el método `renderHook()`.

### Ejemplo: Integración de `wire-elements/modal` con Filament

```php
use Filament\Panel;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->renderHook(
            PanelsRenderHook::BODY_START, // Punto de inserción
            fn (): string => Blade::render('@livewire(\'livewire-ui-modal\')'),
        );
}
```

Puedes encontrar una lista completa de los ganchos de renderizado disponibles en la [documentación oficial](https://filamentphp.com/docs).

---



## Configurar un dominio específico

Por defecto, Filament responderá a solicitudes desde **todos los dominios**. Si deseas limitarlo a un dominio específico, usa el método `domain()`, similar a `Route::domain()` en Laravel:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->domain('admin.tudominio.com');
}
```

---



## Personalizar el ancho máximo del contenido

Filament limita el ancho del contenido en la página para que no sea demasiado amplio en pantallas grandes. Para cambiar esto, usa el método `maxContentWidth()`. Las opciones corresponden a la escala de ancho máximo de **Tailwind CSS**.

### Opciones disponibles:

- `ExtraSmall`, `Small`, `Medium`, `Large`, `ExtraLarge`
- `TwoExtraLarge`, `ThreeExtraLarge`, `FourExtraLarge`, `FiveExtraLarge`, `SixExtraLarge`, `SevenExtraLarge`
- `Full`, `MinContent`, `MaxContent`, `FitContent`, `Prose`
- `ScreenSmall`, `ScreenMedium`, `ScreenLarge`, `ScreenExtraLarge`, `ScreenTwoExtraLarge`

**Valor por defecto:** `SevenExtraLarge`

### Ejemplo:

```php
use Filament\Panel;
use Filament\Support\Enums\Width;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->maxContentWidth(Width::Full); // Ancho completo
}
```



### Personalizar el ancho máximo para páginas simples

Para páginas como **login** o **registro** (que usan `SimplePage`), puedes usar `simplePageMaxContentWidth()`:

```php
use Filament\Panel;
use Filament\Support\Enums\Width;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->simplePageMaxContentWidth(Width::Small); // Ancho pequeño para páginas simples
}
```

---



## Configurar la posición de la sub-navegación

La **sub-navegación** se renderiza al inicio de cada página por defecto. Puedes personalizarla por página, recurso o cluster, pero también para todo el panel usando el método `subNavigationPosition()`.

### Opciones:

- `SubNavigationPosition::Start` (inicio)
- `SubNavigationPosition::End` (final)
- `SubNavigationPosition::Top` (como pestañas en la parte superior)



### Ejemplo:

```php
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->subNavigationPosition(SubNavigationPosition::End); // Sub-navegación al final
}
```

---



## Ganchos del ciclo de vida (Lifecycle Hooks)

Los **ganchos del ciclo de vida** te permiten ejecutar código durante el ciclo de vida de un panel. El método `bootUsing()` se ejecuta en cada solicitud dentro de ese panel. Si tienes múltiples paneles, solo se ejecutará el `bootUsing()` del panel actual.

### Ejemplo:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->bootUsing(function (Panel $panel) {
            // Código a ejecutar en cada solicitud del panel
        });
}
```

---



## Modo SPA (Single Page Application)

El modo **SPA** utiliza la función `wire:navigate` de Livewire para que tu panel renderizado en el servidor se comporte como una **aplicación de una sola página**, con menos retraso entre cargas de página y una barra de carga para solicitudes más largas.

### Habilitar el modo SPA:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->spa();
}
```



### Deshabilitar la navegación SPA para URLs específicas

Por defecto, al habilitar el modo SPA, cualquier URL en el mismo dominio se navegará usando `wire:navigate`. Si deseas excluir URLs específicas, usa el método `spaUrlExceptions()`:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->spa()
        ->spaUrlExceptions([
            url('/admin'),
            '*/admin/posts/*', // Usa * como comodín
        ]);
}
```

**Nota:** Las URLs deben coincidir exactamente con la URL a la que el usuario está navegando, incluyendo dominio y protocolo.

---



### Habilitar la precarga en modo SPA

La **precarga en modo SPA** mejora la experiencia del usuario al precargar automáticamente las páginas cuando el usuario pasa el ratón sobre los enlaces, haciendo que la navegación sea más rápida. Esto utiliza la funcionalidad `wire:navigate.hover` de Livewire.

#### Habilitar precarga:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->spa(hasPrefetching: true);
}
```

**Advertencia:** La precarga de páginas pesadas puede aumentar el uso de ancho de banda y la carga del servidor. Úsalo con moderación.

---



## Alertas de cambios no guardados

Puedes alertar a los usuarios si intentan navegar fuera de una página sin guardar sus cambios. Esto se aplica en las páginas **Create** y **Edit** de un recurso, así como en cualquier modal de acción abierto.

### Habilitar alertas:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->unsavedChangesAlerts();
}
```

---



## Transacciones de base de datos

Por defecto, Filament **no envuelve las operaciones en transacciones de base de datos**. Sin embargo, puedes habilitar las transacciones para todas las operaciones usando el método `databaseTransactions()`:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->databaseTransactions();
}
```



### Deshabilitar transacciones para acciones específicas

Si no deseas que ciertas acciones usen transacciones, usa el método `databaseTransaction(false)`:

```php
CreateAction::make()
    ->databaseTransaction(false);
```



### Deshabilitar transacciones para páginas específicas

Para páginas como **Create** o **Edit**, define la propiedad `$hasDatabaseTransactions` como `false` en la clase de la página:

```php
use Filament\Resources\Pages\CreateRecord;

class CreatePost extends CreateRecord
{
    protected ?bool $hasDatabaseTransactions = false;
}
```

---



## Optar por transacciones en acciones y páginas específicas

En lugar de habilitar transacciones en todas partes y deshabilitarlas para casos específicos, puedes optar por habilitarlas solo donde sea necesario.

### Para acciones:

```php
CreateAction::make()
    ->databaseTransaction();
```



### Para páginas:

```php
use Filament\Resources\Pages\CreateRecord;

class CreatePost extends CreateRecord
{
    protected ?bool $hasDatabaseTransactions = true;
}
```

---



## Registrar activos para un panel

Puedes registrar **CSS y JavaScript** que solo se cargarán en las páginas de un panel específico, no en el resto de la aplicación. Usa el método `assets()`:

```php
use Filament\Panel;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->assets([
            Css::make('custom-stylesheet', resource_path('css/custom.css')),
            Js::make('custom-script', resource_path('js/custom.js')),
        ]);
}
```

**Nota:** Después de registrar los activos, ejecuta:

```bash
php artisan filament:assets
```

---



## Aplicar middleware

Puedes aplicar **middleware adicional** a todas las rutas del panel usando el método `middleware()`:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->middleware([
            // Middleware personalizado
        ]);
}
```



### Middleware persistente

Por defecto, el middleware se ejecuta solo en la primera carga de la página, no en solicitudes AJAX posteriores de Livewire. Si deseas que se ejecute en **todas las solicitudes**, usa `isPersistent: true`:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->middleware([
            // Middleware
        ], isPersistent: true);
}
```

---



## Aplicar middleware a rutas autenticadas

Puedes aplicar middleware **solo a las rutas autenticadas** usando el método `authMiddleware()`:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->authMiddleware([
            // Middleware para rutas autenticadas
        ]);
}
```



### Middleware persistente para rutas autenticadas

Al igual que con `middleware()`, puedes hacer que el middleware sea persistente:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->authMiddleware([
            // Middleware
        ], isPersistent: true);
}
```

---



## Deshabilitar Broadcasting

Por defecto, **Laravel Echo** se conectará automáticamente para cada panel si las credenciales están configuradas en el archivo `config/filament.php`. Para deshabilitar esta conexión automática en un panel, usa el método `broadcasting(false)`:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->broadcasting(false);
}
```

---



## Modo de autorización estricta

Por defecto, cuando Filament autoriza el acceso de un usuario a un recurso:

1. Verifica si existe una **política** para ese modelo.
2. Si existe, verifica si hay un **método en la política** para realizar la acción.
3. Si la política o el método no existen, **otorga acceso** al usuario, asumiendo que no has configurado la autorización o no la necesitas.

Si prefieres que Filament **lance una excepción** si la política o el método no existen, habilita el modo de autorización estricta con `strictAuthorization()`:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->strictAuthorization();
}
```

---



## Configurar notificaciones de error

Cuando el modo **debug** de Laravel está deshabilitado, Filament reemplaza los modales de error de pantalla completa de Livewire con **notificaciones flash más limpias**. Puedes deshabilitar este comportamiento con `errorNotifications(false)`:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->errorNotifications(false);
}
```



### Personalizar el texto de las notificaciones de error

Puedes personalizar el título y el cuerpo de las notificaciones usando el método `registerErrorNotification()`:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->registerErrorNotification(
            title: 'Ocurrió un error',
            body: 'Por favor, inténtalo más tarde.',
        );
}
```



### Notificaciones para códigos de estado HTTP específicos

Puedes registrar notificaciones para códigos de estado específicos, como **404** o **403**:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->registerErrorNotification(
            title: 'Ocurrió un error',
            body: 'Por favor, inténtalo más tarde.',
        )
        ->registerErrorNotification(
            title: 'Registro no encontrado',
            body: 'El registro que buscas no existe.',
            statusCode: 404,
        );
}
```



### Ocultar notificaciones para códigos de estado específicos

Si deseas **ocultar** una notificación para un código de estado (ej: 403), usa `hiddenErrorNotification()`:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->hiddenErrorNotification(403); // No muestra notificación para 403
}
```



### Deshabilitar notificaciones para códigos de estado específicos

Si deseas **deshabilitar** las notificaciones de Filament y usar el manejo de errores de Livewire para un código de estado específico, usa `disabledErrorNotification()`:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->disabledErrorNotification(503); // Usa el manejo de errores de Livewire para 503
}
```

---



### Configurar notificaciones de error por página

Puedes habilitar o deshabilitar notificaciones de error para páginas específicas usando la propiedad `$hasErrorNotifications` en la clase de la página:

```php
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected ?bool $hasErrorNotifications = true; // o false
}
```



#### Usar lógica personalizada para notificaciones de error

Si deseas ejecutar código personalizado para decidir si se deben mostrar notificaciones de error, usa el método `hasErrorNotifications()` en la clase de la página:

```php
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function hasErrorNotifications(): bool
    {
        return FeatureFlag::active(); // Ejemplo: Basado en una bandera de característica
    }
}
```



#### Registrar notificaciones de error en una página

Puedes registrar notificaciones de error en una página usando el método `setUpErrorNotifications()`:

```php
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected function setUpErrorNotifications(): void
    {
        $this->registerErrorNotification(
            title: 'Ocurrió un error',
            body: 'Por favor, inténtalo más tarde.',
        );

        $this->registerErrorNotification(
            title: 'Registro no encontrado',
            body: 'El registro que buscas no existe.',
            statusCode: 404,
        );

        $this->hiddenErrorNotification(403);
        $this->disabledErrorNotification(503);
    }
}
```

