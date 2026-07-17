# Colores

## Introducción

Filament utiliza variables CSS para definir su paleta de colores. Estas variables se mapean a clases de Tailwind en el archivo de "preset" que se carga al instalar Filament. El motivo de usar variables CSS es que permite al framework pasar una paleta de colores desde PHP a través de un elemento `<style>`, que se renderiza como parte de la directiva Blade `@filamentStyles`.

Por defecto, el archivo de "preset" de Tailwind en Filament incluye 6 colores:
- **primary**: color ámbar de Tailwind por defecto
- **success**: color verde de Tailwind por defecto
- **warning**: color ámbar de Tailwind por defecto
- **danger**: color rojo de Tailwind por defecto
- **info**: color azul de Tailwind por defecto
- **gray**: color zinc de Tailwind por defecto

Puedes aprender cómo cambiar estos colores y registrar nuevos.

---

## Cómo pasar un color a Filament

Un color registrado en Filament no es solo un tono, sino una paleta completa compuesta por 11 tonos: 50, 100, 200, 300, 400, 500, 600, 700, 800, 900 y 950. Cuando utilizas un color en Filament, el framework decide cuál tono usar según el contexto. Por ejemplo, puede usar el tono 600 para el fondo, el 500 al hacer "hover", y el 400 para el borde. Si el usuario tiene habilitado el modo oscuro, podría usar 700, 800 o 900.

Esto quiere decir que puedes especificar un color sin preocuparte por el tono exacto ni definir un tono para cada parte del componente. Filament elige un tono que cree contraste accesible con otros elementos.

Para personalizar el color de algo en Filament, puedes usar su nombre. Por ejemplo, para usar el color "success":

```php
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;

Action::make('proceed')
    ->color('success');

Toggle::make('is_active')
    ->onColor('success');
```

Si quieres usar un color en un componente Blade, lo pasas como atributo:

```blade
<x-filament::badge color="success">
    Activo
</x-filament::badge>
```

---

## Personalizando los colores por defecto

Desde el método `boot()` de un Service Provider o en un middleware, puedes llamar a `FilamentColor::register()` para configurar los colores que usará Filament en los elementos de la interfaz.

Estos son los 6 colores por defecto que puedes personalizar:

```php
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;

FilamentColor::register([
    'danger' => Color::Red,
    'gray' => Color::Zinc,
    'info' => Color::Blue,
    'primary' => Color::Amber,
    'success' => Color::Green,
    'warning' => Color::Amber,
]);
```

La clase `Color` contiene todos los colores de Tailwind CSS disponibles.

> También puedes pasar una función a `register()`, la cual solo se ejecutará cuando la app esté siendo renderizada. Esto es útil para obtener datos como el usuario autenticado, el cual suele estar disponible después del middleware.

---

## Registrar colores adicionales

Puedes registrar un nuevo color para usar en cualquier componente de Filament así:

```php
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;

FilamentColor::register([
    'secondary' => Color::Indigo,
]);
```

Ahora podrás utilizar `secondary` como color en cualquier componente de Filament.

---

## Usar colores personalizados (no-Tailwind)

Puedes usar colores personalizados que no estén en la paleta de Tailwind CSS pasando un arreglo de tonos (del 50 al 950) en formato OKLCH:

```php
use Filament\Support\Facades\FilamentColor;

FilamentColor::register([
    'danger' => [
        50 => 'oklch(0.969 0.015 12.422)',
        100 => 'oklch(0.941 0.03 12.58)',
        200 => 'oklch(0.892 0.058 10.001)',
        300 => 'oklch(0.81 0.117 11.638)',
        400 => 'oklch(0.712 0.194 13.428)',
        500 => 'oklch(0.645 0.246 16.439)',
        600 => 'oklch(0.586 0.253 17.585)',
        700 => 'oklch(0.514 0.222 16.935)',
        800 => 'oklch(0.455 0.188 13.697)',
        900 => 'oklch(0.41 0.159 10.272)',
        950 => 'oklch(0.271 0.105 12.094)',
    ],
]);
```

---

## Generar una paleta personalizada

Si quieres que Filament genere una paleta basada en un color HEX o RGB, puedes hacer:

```php
use Filament\Support\Facades\FilamentColor;

FilamentColor::register([
    'danger' => '#ff0000',
]);

FilamentColor::register([
    'danger' => 'rgb(255, 0, 0)',
]);
```

---

## Cómo Filament selecciona tonos accesibles

Cuando asignas un color a un componente Filament (por ejemplo, `->color('primary')`), Filament recibe la paleta completa de 11 tonos y decide en tiempo de ejecución qué tono aplicar al fondo, texto, hover, variantes para modo oscuro, etc. La selección se basa en las relaciones de contraste WCAG 2.1: para cada slot, recorre la paleta y elige el tono más claro (o más oscuro, según el contexto) que cumpla el mínimo de contraste requerido frente al fondo.

Esto significa:
- Solo necesitas registrar una paleta por nombre de color. La selección de tonos es automática.
- El mismo color puede verse diferente en distintos componentes según las reglas de contraste.
- Si cambias una paleta (por ejemplo, cambias primary de ámbar a otro tono), todos los componentes usan sus tonos derivados, manteniendo la accesibilidad.

**Umbrales de contraste (según la WCAG 2.1):**
- Texto normal (`Color::WCAG_AA_TEXT`, 4.5:1): para botones, badges, links, items de dropdown, y columnas de texto.
- Componentes de UI y objetos gráficos (`Color::WCAG_AA_NON_TEXT`, 3:1): para botones de íconos, toggles, columnas de íconos, entradas de íconos.
- La clase `Filament\Support\Colors\Color` expone estos valores como constantes que puedes utilizar.

---

## Por qué algunos botones muestran texto oscuro en vez de blanco

Para botones sólidos, Filament crea previamente una tabla de "mejor texto para cada fondo", luego elige el fondo real revisando cuáles fondos posibles permiten usar texto claro.

- Para colores vibrantes (rojo, azul, índigo), el tono 600 es suficientemente oscuro para un texto blanco con contraste (4.5:1).
- Para colores pálidos (amarillo, ámbar, lima), incluso el 600 es muy claro, por lo que el texto oscuro tiene mejor contraste que el blanco. El sistema detecta esto y elige un fondo más pálido (bg: 400) y texto oscuro.

Esto es intencional. Si siempre se usara fondo 600 y texto blanco, algunos casos serían inapropiados para paletas cálidas o pálidas. Así, warning (usualmente ámbar) queda legible, mientras que success y danger mantienen su estilo sólido.

---

## Personalizar la selección de tonos

Si necesitas modificar cómo un componente decide sus tonos (por ejemplo, exigir contraste WCAG AAA en toda la app, o preferir fondos más oscuros), puedes extender el componente de vista y registrarlo en el contenedor de Laravel.

Filament ofrece tres clases "color-map" para este propósito, todas bajo el namespace `Filament\Support\View\Components\ColorMaps`:

- **ComponentColorMap**: para componentes que usan un tono por slot (badges, links, columnas de texto, íconos, dropdowns, toggles).
- **ButtonComponentColorMap**: para botones sólidos. Elige fondo y su texto correspondiente.
- **IconButtonComponentColorMap**: para botones solo de ícono. Elige un tono de ícono y su variante hover.

---

## Componentes que puedes sobrescribir

Cada componente de Filament que selecciona tonos de una paleta implementa `getColorMap()`. Para personalizarlo, extiende la clase, sobrescribe `getColorMap()` y vincula tu subclase en el contenedor de Laravel.

| Clase de componente | Usado por | Documentación |
|---------------------|-----------|--------------|
| Filament\Support\View\Components\BadgeComponent | Badges | Badge |
| Filament\Support\View\Components\ButtonComponent | Botones (sólido y outlined) | Button |
| Filament\Support\View\Components\IconButtonComponent | Botones solo ícono | Icon button |
| Filament\Support\View\Components\LinkComponent | Links | Link |
| Filament\Support\View\Components\ToggleComponent | Toggle de formulario | Toggle |
| Filament\Support\View\Components\DropdownComponent\HeaderComponent | Encabezados dropdown | Dropdown |
| Filament\Support\View\Components\DropdownComponent\ItemComponent | Ítems de dropdown | Dropdown |
| Filament\Schemas\View\Components\TextComponent | Primes de texto en schemas | Prime components |
| Filament\Infolists\View\Components\TextEntryComponent\ItemComponent | Entradas de texto en Infolist | Text entry |
| Filament\Infolists\View\Components\IconEntryComponent\IconComponent | Entradas de ícono en Infolist | Icon entry |
| Filament\Tables\View\Components\Columns\TextColumnComponent\ItemComponent | Columnas de texto en tabla | Text column |
| Filament\Tables\View\Components\Columns\IconColumnComponent\IconComponent | Columnas de íconos en tabla | Icon column |
| Filament\Tables\View\Components\Columns\Summarizers\CountComponent\IconComponent | Resumidores count de tablas | Summaries |
| Filament\Widgets\View\Components\StatsOverviewWidgetComponent\StatComponent\DescriptionComponent | Descripciones de widget Stats Overview | Stats overview |

La forma más rápida de escribir un `getColorMap()` personalizado es copiar la implementación de la clase original y ajustar los valores de configuración. Los archivos fuente están en `packages/<paquete>/src/View/Components/`.

---

## Ejemplo de uso de ComponentColorMap

Construye un mapa slot por slot. Llama a `slot()` por cada slot, luego `get()`:

```php
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\View\Components\ColorMaps\ComponentColorMap;

$gray = FilamentColor::getColor('gray');

ComponentColorMap::make($color)
    ->slot('text', surface: $gray[50], minRatio: Color::WCAG_AA_TEXT, fallback: 900)
    ->slot('dark:text', surface: $gray[700], maxShade: 500, shouldStartFromDarkest: true, fallback: 200)
    ->get();
```

**Parámetros de `slot()`:**
- `$name` — salida (por ejemplo: text, bg, hover:text, dark:hover:bg)
- `$surface` — color de fondo con el que este tono debe contrastar, normalmente `$gray[50]`, `$gray[700]`, o `'oklch(1 0 0)'`
- `$minRatio` — relación mínima de contraste WCAG. Por defecto es `Color::WCAG_AA_TEXT (4.5)`. Usa `WCAG_AA_NON_TEXT (3.0)` para íconos o `WCAG_AAA_TEXT (7.0)` para AAA.
- `$maxShade` — tope máximo de tono considerado (opcional).
- `$minShade` — tope mínimo de tono considerado (opcional).
- `$shouldStartFromDarkest` — si debe empezar de más oscuro a claro (utiliza en modo oscuro).
- `$fallback` — el tono elegido si ninguno califica. Típicamente 900 para modo claro, 200 para modo oscuro.

---

## ButtonComponentColorMap

Devuelve los ocho slots que necesita un botón sólido (bg, hover:bg, dark:bg, dark:hover:bg, text, hover:text, dark:text, dark:hover:text) con un solo `get()`. Configuras los fondos; el tono de texto correspondiente se determina automáticamente. Al menos uno para fondo claro y uno para modo oscuro son requeridos.

```php
use Filament\Support\Colors\Color;
use Filament\Support\View\Components\ColorMaps\ButtonComponentColorMap;

ButtonComponentColorMap::make($color)
    ->minContrastRatio(Color::WCAG_AA_TEXT)
    ->lightBackground(bg: 600, hover: 500)
    ->lightBackground(bg: 400, hover: 300, alternateHover: 500)
    ->darkBackground(bg: 600, hover: 500, alternateHover: 700)
    ->get();
```

**Notas:**
- `minContrastRatio()` define el contraste mínimo entre fondo y texto. Por defecto es `Color::WCAG_AA_TEXT (4.5)`. Usa `WCAG_AAA_TEXT (7.0)` para AAA.
- `lightBackground()` y `darkBackground()` comparten la misma estructura (bg, hover, alternateHover).
- Elige el primer candidato que permita texto claro. Si ninguno califica, usa el último, aunque produzca texto oscuro con fondo oscuro.
- Es importante terminar con un candidato apto para colores pálidos (usualmente bg en 400) para colores como amarillo, ámbar y lima.

---

## IconButtonComponentColorMap

Devuelve los slots para botones solo de ícono (text, hover:text, dark:text, dark:hover:text). Los variantes hover se generan con un desplazamiento de 100 tonos. Se requiere al menos un `lightSurface()` y un `darkSurface()`.

```php
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\View\Components\ColorMaps\IconButtonComponentColorMap;

$gray = FilamentColor::getColor('gray');

IconButtonComponentColorMap::make($color)
    ->minContrastRatio(Color::WCAG_AA_NON_TEXT)
    ->lightSurface($gray[50])
    ->darkSurface($gray[700])
    ->darkMaxShade(500)
    ->get();
```

---

## Ejemplo completo: Contraste AAA en botones

Aquí un ejemplo para exigir contraste AAA y preferir fondos oscuros en botones sólidos:

```php
namespace App\View\Components;

use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\View\Components\ButtonComponent as BaseButtonComponent;
use Filament\Support\View\Components\ColorMaps\ButtonComponentColorMap;
use Filament\Support\View\Components\ColorMaps\ComponentColorMap;

class ButtonComponent extends BaseButtonComponent
{
    public function getColorMap(array $color): array
    {
        $gray = FilamentColor::getColor('gray');

        if ($this->isOutlined) {
            return ComponentColorMap::make($color)
                ->slot('text', surface: $gray[50], minRatio: Color::WCAG_AAA_TEXT, fallback: 900)
                ->slot('dark:text', surface: $gray[700], minRatio: Color::WCAG_AAA_TEXT, maxShade: 500, shouldStartFromDarkest: true, fallback: 200)
                ->get();
        }

        return ButtonComponentColorMap::make($color)
            ->minContrastRatio(Color::WCAG_AAA_TEXT)
            ->lightBackground(bg: 700, hover: 600)
            ->lightBackground(bg: 400, hover: 300, alternateHover: 500)
            ->darkBackground(bg: 600, hover: 500, alternateHover: 700)
            ->get();
    }
}
```

Vincula tu subclase en el método `register()` de tu service provider:

```php
use App\View\Components\ButtonComponent;
use Filament\Support\View\Components\ButtonComponent as BaseButtonComponent;

public function register(): void
{
    $this->app->bind(BaseButtonComponent::class, ButtonComponent::class);
}
```

El mismo patrón funciona para cualquiera de los componentes antes listados: extiende, sobrescribe `getColorMap()` y vincula en el contenedor.

> Los valores predeterminados funcionan para la mayoría de paletas. Sobrescribe estas clases solo si tienes requisitos de accesibilidad especiales (como AAA) o una guía de diseño que requiera ajustes.