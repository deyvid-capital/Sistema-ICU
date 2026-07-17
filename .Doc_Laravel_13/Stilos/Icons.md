# Iconos

## Introducción

Los iconos se utilizan en toda la interfaz de Filament para comunicar visualmente partes clave de la experiencia de usuario. Para renderizar los iconos, se utiliza el paquete **Blade Icons** de Blade UI Kit.

Puedes buscar todos los iconos disponibles en la [página de Blade Icons](https://blade-ui-kit.com/blade-icons#icon-sets), donde encontrarás diferentes paquetes de iconos. Cada paquete trae un set distinto que puedes elegir. **Filament instala por defecto el set de iconos "Heroicons"**, así que, si usas estos, no necesitas instalar nada extra.

---

## Uso de Heroicons en Filament

Filament ya incluye por defecto el set de **Heroicons**. Puedes utilizar cualquiera de sus iconos sin instalar paquetes adicionales. La clase `Heroicon` (un enum) te permite aprovechar el autocompletado de tu IDE para encontrar el icono deseado.

```php
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Support\Icons\Heroicon;

Action::make('star')
    ->icon(Heroicon::OutlinedStar);

Toggle::make('is_starred')
    ->onIcon(Heroicon::Star);
```

Cada icono tiene una variante "contorneada" (`Outlined`) y una "sólida" (`Solid`). Por ejemplo, `Heroicon::Star` es sólido y `Heroicon::OutlinedStar` es la versión contorneada.

Heroicons ofrece varios tamaños (16px, 20px y 24px) y Filament selecciona automáticamente el tamaño adecuado según el contexto.

Si quieres usar el icono en un componente Blade, pásalo como atributo:

```blade
@php
    use Filament\Support\Icons\Heroicon;
@endphp

<x-filament::badge :icon="Heroicon::Star">
    Estrella
</x-filament::badge>
```

---

## Usar otros sets de iconos en Filament

Si encuentras un icono de otro set (no Heroicon), primero instala el paquete y luego simplemente usa el nombre del icono. Ejemplo:

```php
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;

Action::make('star')
    ->icon('iconic-star');

Toggle::make('is_starred')
    ->onIcon('iconic-check-circle');
```

En Blade:

```blade
<x-filament::badge icon="iconic-star">
    Estrella
</x-filament::badge>
```

---

## Usar SVG personalizados como iconos

Blade Icons te deja registrar SVGs personalizados como iconos, útil si quieres tus propios diseños.

1. Publica la configuración de Blade Icons:
   ```
   php artisan vendor:publish --tag=blade-icons
   ```
2. Abre `config/blade-icons.php` y descomenta el set por defecto en el array `sets`.
3. Coloca tus SVGs en la carpeta `resources/svg/`. Por ejemplo: si tienes `star.svg`, lo podrás referenciar como `icon-star` (el prefijo `icon-` es configurable).

Para usar en Blade:
```php
use Filament\Actions\Action;

Action::make('star')
    ->icon('icon-star');
```
O en Blade:
```blade
@svg('icon-star')
```

---

## Reemplazar iconos por defecto

Filament ofrece un sistema para reemplazar cualquier icono del UI. Normalmente esto se hace en `boot()` de tu Service Provider, como `AppServiceProvider` o uno dedicado a iconos.

Utiliza el facade `FilamentIcon` y su método `register()` para registrar un array de iconos a reemplazar, donde la **clave** es el alias del icono y el **valor** es el nuevo nombre de icono o un view Blade.

```php
use Filament\Support\Facades\FilamentIcon;
use Filament\View\PanelsIconAlias;

FilamentIcon::register([
    PanelsIconAlias::GLOBAL_SEARCH_FIELD => 'fas-magnifying-glass',
    PanelsIconAlias::SIDEBAR_GROUP_COLLAPSE_BUTTON => view('icons.chevron-up'),
]);
```

---

## Aliases de iconos disponibles

### Acciones (Filament\Actions\View\ActionsIconAlias)
- `ActionsIconAlias::ACTION_GROUP` - Botón de grupo de acciones
- `ActionsIconAlias::CREATE_ACTION_GROUPED` - Crear acción agrupada
- `ActionsIconAlias::DELETE_ACTION` - Botón de eliminar
- `ActionsIconAlias::DELETE_ACTION_GROUPED` - Botón de eliminar agrupado
- `ActionsIconAlias::DELETE_ACTION_MODAL` - Modal de eliminar
- ... *(continúa con la misma estructura para el resto de acciones)*

### Formularios (Filament\Forms\View\FormsIconAlias)
- `FormsIconAlias::COMPONENTS_BUILDER_ACTIONS_CLONE` - Clonar ítem en builder
- `FormsIconAlias::COMPONENTS_BUILDER_ACTIONS_COLLAPSE` - Colapsar ítem en builder
- ... *(y así sucesivamente para todos los iconos de formulario)*

### Infolists (Filament\Infolists\View\InfolistsIconAlias)
- `InfolistsIconAlias::COMPONENTS_ICON_ENTRY_FALSE` - Estado falso en entry de icono
- `InfolistsIconAlias::COMPONENTS_ICON_ENTRY_TRUE` - Estado verdadero en entry de icono

### Notificaciones (Filament\Notifications\View\NotificationsIconAlias)
- `NotificationsIconAlias::DATABASE_MODAL_EMPTY_STATE` - Estado vacío en el modal de notificaciones de BD
- `NotificationsIconAlias::NOTIFICATION_CLOSE_BUTTON` - Botón de cerrar notificación
- ... *(etc.)*

### Paneles (Filament\View\PanelsIconAlias)
- `PanelsIconAlias::GLOBAL_SEARCH_FIELD` - Campo de búsqueda global
- `PanelsIconAlias::PAGES_DASHBOARD_ACTIONS_FILTER` - Filtro en acciones del dashboard
- ... *(etc.)*

### Esquemas (Filament\Schemas\View\SchemaIconAlias)
- `SchemaIconAlias::COMPONENTS_WIZARD_COMPLETED_STEP` - Paso completado en wizard

### Tablas (Filament\Tables\View\TablesIconAlias)
- `TablesIconAlias::ACTIONS_DISABLE_REORDERING` - Desactivar reordenamiento
- `TablesIconAlias::ACTIONS_ENABLE_REORDERING` - Activar reordenamiento
- ... *(etc.)*

### Componentes UI (Filament\Support\View\SupportIconAlias)
- `SupportIconAlias::BADGE_DELETE_BUTTON` - Botón para borrar badge
- `SupportIconAlias::BREADCRUMBS_SEPARATOR` - Separador de migas de pan
- ... *(etc.)*

### Widgets (Filament\Widgets\View\WidgetsIconAlias)
- `WidgetsIconAlias::CHART_WIDGET_FILTER` - Filtro del widget de gráficas
- `WidgetsIconAlias::CHART_WIDGET_EMPTY_STATE` - Estado vacío del widget de gráficas

---

**Nota:** Este listado es solo una referencia rápida. Puedes consultar la [documentación oficial de Filament (en inglés)](https://filamentphp.com/docs/3.x/support/icons) para más detalles o actualizaciones.