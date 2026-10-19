# Widgets

## Widgets de resumen de estadísticas (stats overview)

### Introducción

Filament incluye una plantilla de widget de "resumen de estadísticas", que puedes usar para mostrar varias estadísticas diferentes en un solo widget, sin necesidad de escribir una vista personalizada.

Comienza creando un widget con el comando:

```bash
php artisan make:filament-widget StatsOverview --stats-overview
```

Este comando creará un nuevo archivo `StatsOverview.php`. Ábrelo y devuelve instancias de `Stat` desde el método `getStats()`:

```php
<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Unique views', '192.1k'),
            Stat::make('Bounce rate', '21%'),
            Stat::make('Average time on page', '3:12'),
        ];
    }
}
```

Ahora, revisa tu widget en el panel de control.

### Añadir una descripción y un icono a una estadística

Puedes añadir una `description()` para proporcionar información adicional, junto con un `descriptionIcon()`:

```php
use Filament\Widgets\StatsOverviewWidget\Stat;

protected function getStats(): array
{
    return [
        Stat::make('Unique views', '192.1k')
            ->description('32k increase')
            ->descriptionIcon('heroicon-m-arrow-trending-up'),
        Stat::make('Bounce rate', '21%')
            ->description('7% decrease')
            ->descriptionIcon('heroicon-m-arrow-trending-down'),
        Stat::make('Average time on page', '3:12')
            ->description('3% increase')
            ->descriptionIcon('heroicon-m-arrow-trending-up'),
    ];
}
```

El método `descriptionIcon()` también acepta un segundo parámetro para colocar el icono antes de la descripción en lugar de después:

```php
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget\Stat;

Stat::make('Unique views', '192.1k')
    ->description('32k increase')
    ->descriptionIcon('heroicon-m-arrow-trending-up', IconPosition::Before)
```

### Cambiar el color de la estadística

También puedes darle un color a las estadísticas:

```php
use Filament\Widgets\StatsOverviewWidget\Stat;

protected function getStats(): array
{
    return [
        Stat::make('Unique views', '192.1k')
            ->description('32k increase')
            ->descriptionIcon('heroicon-m-arrow-trending-up')
            ->color('success'),
        Stat::make('Bounce rate', '21%')
            ->description('7% increase')
            ->descriptionIcon('heroicon-m-arrow-trending-down')
            ->color('danger'),
        Stat::make('Average time on page', '3:12')
            ->description('3% increase')
            ->descriptionIcon('heroicon-m-arrow-trending-up')
            ->color('success'),
    ];
}
```

### Añadir atributos HTML extra a una estadística

También puedes pasar atributos HTML adicionales a las estadísticas usando `extraAttributes()`:

```php
use Filament\Widgets\StatsOverviewWidget\Stat;

protected function getStats(): array
{
    return [
        Stat::make('Processed', '192.1k')
            ->color('success')
            ->extraAttributes([
                'class' => 'cursor-pointer',
                'wire:click' => "\$dispatch('setStatusFilter', { filter: 'processed' })",
            ]),
        // ...
    ];
}
```

En este ejemplo, escapamos deliberadamente el `$` en `$dispatch()`, ya que debe pasarse directamente al HTML; no es una variable de PHP.

### Establecer un marcador de posición (placeholder) para una estadística

A veces el valor de una estadística puede no estar disponible — por ejemplo, cuando un panel no tiene nada que mostrar para un periodo específico. Puedes usar `placeholder()` para definir qué mostrar en su lugar cuando el valor esté vacío:

```php
use Filament\Widgets\StatsOverviewWidget\Stat;

Stat::make('Unique views', $uniqueViews)
    ->placeholder('-')
```

Un valor se considera vacío según el helper `blank()` de Laravel. Por ejemplo, `null` y las cadenas vacías lo son, mientras que `0` y `'0'` no.

### Establecer un marcador de posición por defecto globalmente

Si quieres que todas las estadísticas de tu aplicación usen el mismo marcador de posición por defecto, puedes usar el método estático `configureUsing()` en el método `boot()` de un service provider:

```php
use Filament\Widgets\StatsOverviewWidget\Stat;

Stat::configureUsing(function (Stat $component): void {
    $component->placeholder('-');
});
```

Las estadísticas individuales pueden sobrescribirlo llamando a `placeholder()` por sí mismas.

### Añadir un gráfico a una estadística

También puedes añadir o encadenar un `chart()` a cada estadística para proporcionar datos históricos. El método `chart()` acepta un array de puntos de datos a graficar:

```php
use Filament\Widgets\StatsOverviewWidget\Stat;

protected function getStats(): array
{
    return [
        Stat::make('Unique views', '192.1k')
            ->description('32k increase')
            ->descriptionIcon('heroicon-m-arrow-trending-up')
            ->chart([7, 2, 10, 3, 15, 4, 17])
            ->color('success'),
        // ...
    ];
}
```

### Estilizar los gráficos de las estadísticas en un tema

Para estilizar los gráficos de las estadísticas desde un tema personalizado, define estas propiedades personalizadas de CSS en `.fi-wi-stats-overview-stat` o en un elemento padre:

```css
.fi-wi-stats-overview-stat {
    --stat-chart-border-width: 1;
    --stat-chart-line-tension: 0;
    --stat-chart-fill: none;
}
```

- `--stat-chart-border-width`: el grosor de la línea.
- `--stat-chart-line-tension`: la curvatura, desde `0` para segmentos rectos hasta `1`.
- `--stat-chart-fill`: el destino del relleno: `start`, `end`, `origin` o `stack`. Usa `none` para desactivar el relleno.

Usa números sin unidades y las palabras clave admitidas, no longitudes CSS. Puedes definir valores distintos para el modo claro y el oscuro.

El gráfico toma sus colores del color de la estadística. Para cambiarlos en un tema, estiliza los elementos `.fi-wi-stats-overview-stat-chart-bg-color` y `.fi-wi-stats-overview-stat-chart-border-color` con una declaración de color normal:

```css
.fi-wi-stats-overview-stat {
    & .fi-wi-stats-overview-stat-chart-border-color {
        @apply text-gray-400 dark:text-gray-500;
    }
}
```

Estas propiedades solo afectan a los gráficos dentro de las estadísticas. Los widgets de gráficos se estilizan con su propio conjunto, con el prefijo `--chart-`.

### Actualización en vivo de las estadísticas (polling)

Por defecto, los widgets de resumen de estadísticas refrescan sus datos cada 5 segundos.

Para personalizarlo, puedes sobrescribir la propiedad `$pollingInterval` en la clase con un nuevo intervalo:

```php
protected ?string $pollingInterval = '10s';
```

Alternativamente, puedes desactivar el polling por completo:

```php
protected ?string $pollingInterval = null;
```

### Desactivar la carga diferida (lazy loading)

Por defecto, los widgets se cargan de forma diferida. Esto significa que solo se cargarán cuando sean visibles en la página.

Para desactivar este comportamiento, puedes sobrescribir la propiedad `$isLazy` en la clase del widget:

```php
protected static bool $isLazy = false;
```

### Añadir un encabezado y una descripción

También puedes añadir texto de encabezado y descripción encima del widget sobrescribiendo las propiedades `$heading` y `$description`:

```php
protected ?string $heading = 'Analytics';

protected ?string $description = 'An overview of some analytics.';
```

Si necesitas generar dinámicamente el texto del encabezado o de la descripción, puedes en su lugar sobrescribir los métodos `getHeading()` y `getDescription()`:

```php
protected function getHeading(): ?string
{
    return 'Analytics';
}

protected function getDescription(): ?string
{
    return 'An overview of some analytics.';
}
```