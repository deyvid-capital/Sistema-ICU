# Clústeres

## Introducción

Los **clústeres** son una estructura jerárquica en los paneles de administración que te permite agrupar **Recursos** y **Páginas personalizadas**. Son útiles para organizar el panel en secciones lógicas y ayudan a reducir el tamaño de la barra lateral.

> **Ejemplo visual:**  
> Vista previa de una página de clúster con subnavegación.

### ¿Qué ocurre al usar un clúster?

- Se añade un nuevo elemento de navegación, que enlaza al primer recurso o página del clúster.
- Los elementos individuales de cada recurso/página agrupado ya no aparecen en la navegación principal.
- Se crea una interfaz de subnavegación para cada elemento del clúster con los elementos agrupados.
- Los recursos y páginas del clúster reciben una URL nueva, con el nombre del clúster como prefijo. Si las URLs se generan automáticamente, este cambio es transparente.
- El nombre del clúster aparece en la "miga de pan" (breadcrumb) de sus recursos y páginas. Al hacer clic, lleva al primer recurso o página del clúster.

---

## Creación de un clúster

Antes de crear tu primer clúster, debes indicar en tu panel donde encontrar las clases de clúster. Junto a métodos como `discoverResources()` y `discoverPages()`, puedes usar `discoverClusters()` en tu configuración:

```php
public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
        ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
        ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters');
}
```

Ahora puedes crear un clúster con el siguiente comando Artisan:

```shell
php artisan make:filament-cluster Settings
```

Esto creará una nueva clase de clúster en el directorio `app/Filament/Clusters`.

```php
<?php

namespace App\Filament\Clusters\Settings;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class SettingsCluster extends Cluster
{
    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedSquares2x2;
}
```

La propiedad `$navigationIcon` está definida por defecto y puedes personalizarla. Otras propiedades útiles para personalizar la navegación del clúster son: `$navigationLabel`, `$navigationSort` y `$navigationGroup`.

---

## Añadir recursos y páginas a un clúster

Para añadir un recurso o página a un clúster, solo debes definir la propiedad `$cluster` en la clase correspondiente, apuntando a la clase del clúster:

```php
use App\Filament\Clusters\SettingsCluster;

protected static ?string $cluster = SettingsCluster::class;
```

---

## Recomendaciones de estructura de código

Se recomienda mover todos los recursos y páginas agrupados a un directorio con el nombre del clúster. Ejemplo de estructura para un clúster `Settings` con un recurso y dos páginas personalizadas:

```
Clusters/
└── Settings/
    ├── SettingsCluster.php
    ├── Pages/
    │   ├── ManageBranding.php
    │   └── ManageNotifications.php
    └── Resources/
        └── Colors/
            ├── ColorResource.php
            └── Pages/
                ├── CreateColor.php
                ├── EditColor.php
                └── ListColors.php
```

Esto solo es una recomendación; puedes estructurar tu panel como prefieras, siempre que los recursos y páginas usen la propiedad `$cluster`.

Cuando ya existe un clúster y generas nuevos Recursos o Páginas con `make:filament-resource` o `make:filament-page`, se te preguntará si deseas crearlos dentro de un directorio clúster. Si respondes afirmativamente, Filament asignará la propiedad `$cluster` automáticamente; si no, deberás hacerlo de forma manual.

---

## Configuración de la posición de la subnavegación

Por defecto, la subnavegación aparece al inicio de cada página agrupada, pero puedes personalizarlo de dos formas: por página o para todo el clúster con la siguiente propiedad:

```php
use Filament\Pages\Enums\SubNavigationPosition;

protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::End;
```

Valores posibles:

- `SubNavigationPosition::Start`: Al inicio de la página.
- `SubNavigationPosition::End`: Al final de la página.
- `SubNavigationPosition::Top`: Como pestañas arriba del contenido principal.

> **Ejemplo visual:**  
> Vista previa con subnavegación en posición final o en la parte superior.

---

## Personalización del breadcrumb del clúster

El nombre del clúster aparece en la miga de pan (breadcrumb) de los recursos y páginas agrupados. Puedes personalizar este nombre:

```php
protected static ?string $clusterBreadcrumb = 'nombre_del_cluster';
```

O de forma dinámica sobrescribiendo el método:

```php
public static function getClusterBreadcrumb(): string
{
    return __('filament/clusters/cluster.name');
}
```

---

## Ocultar la subnavegación en un clúster

Por defecto, todos los recursos y páginas de un clúster mostrarán la subnavegación. Para deshabilitarla globalmente para el clúster:

```php
protected static bool $shouldRegisterSubNavigation = false;
```

O bien, definir dinámicamente el comportamiento sobrescribiendo:

```php
public static function shouldRegisterSubNavigation(): bool
{
    return FeatureFlag::active();
}
```