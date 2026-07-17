
---

```markdown
# Vista General de la Personalización en Filament

---

## Cambiar los colores

En la configuración del panel, puedes personalizar fácilmente los **6 colores predefinidos** que Filament utiliza en todo el framework. Estos colores son:

- `danger` (peligro)
- `gray` (gris)
- `info` (información)
- `primary` (primario)
- `success` (éxito)
- `warning` (advertencia)

### Ejemplo de personalización:
```php
use Filament\Panel;
use Filament\Support\Colors\Color;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->colors([
            'danger' => Color::Rose,
            'gray' => Color::Gray,
            'info' => Color::Blue,
            'primary' => Color::Indigo,
            'success' => Color::Emerald,
            'warning' => Color::Orange,
        ]);
}
```
La clase `Filament\Support\Colors\Color` contiene opciones de color para todas las paletas de **Tailwind CSS**.

---
![Vista previa de un panel con colores personalizados](https://via.placeholder.com/600x400)

---

### Usar una función para registrar colores dinámicamente
Puedes pasar una **función** al método `colors()` para registrar colores dinámicamente. Esto es útil si necesitas acceder a objetos como el **usuario autenticado**, que se inicializan más tarde en el middleware.

```php
->colors(fn () => [
    'primary' => auth()->user()->preferred_color,
]);
```

---
### Usar una paleta personalizada en formato OKLCH
También puedes definir tu propia paleta como un array de colores en formato **OKLCH** (un espacio de color moderno que ofrece mejor consistencia en diferentes dispositivos):

```php
$panel
    ->colors([
        'primary' => [
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
### Generar una paleta de colores automáticamente
Si deseas que Filament intente generar una paleta de colores basada en un **valor hexadecimal o RGB**, puedes pasarlo directamente:

```php
$panel
    ->colors([
        'primary' => '#6366f1', // Formato hexadecimal
    ]);
```

```php
$panel
    ->colors([
        'primary' => 'rgb(99, 102, 241)', // Formato RGB
    ]);
```

---

## Cambiar la fuente

Por defecto, Filament utiliza la fuente **Inter**. Puedes cambiarla usando el método `font()` en el archivo de configuración:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->font('Poppins'); // Usa cualquier fuente de Google Fonts
}
```

---
![Vista previa de un panel con fuente personalizada](https://via.placeholder.com/600x400)

---

## Cambiar el proveedor de fuentes

Filament utiliza **Bunny Fonts CDN** (compatible con GDPR) para servir las fuentes. Si prefieres usar **Google Fonts CDN**, puedes hacerlo con el argumento `provider` del método `font()`:

```php
use Filament\FontProviders\GoogleFontProvider;

$panel->font('Inter', provider: GoogleFontProvider::class);
```

### Usar fuentes locales
Si deseas servir las fuentes desde una **hoja de estilos local**, usa `LocalFontProvider`:

```php
use Filament\FontProviders\LocalFontProvider;

$panel->font(
    'Inter',
    url: asset('css/fonts.css'), // Ruta a tu archivo CSS
    provider: LocalFontProvider::class,
);
```

---

## Crear un tema personalizado

Filament te permite personalizar el **CSS** utilizado para renderizar la interfaz de usuario compilando una hoja de estilos personalizada que reemplace la predeterminada. Estos temas personalizados usan **Tailwind CSS**.

### Crear un tema personalizado
Para crear un tema personalizado para un panel, usa el comando:

```bash
php artisan make:filament-theme
```

Si tienes **múltiples paneles**, puedes especificar el panel para el que deseas crear el tema:

```bash
php artisan make:filament-theme admin
```

Por defecto, este comando usa **NPM** para instalar dependencias. Si deseas usar otro gestor de paquetes (como **Bun** o **Yarn**), usa la opción `--pm`:

```bash
php artisan make:filament-theme --pm=bun
```

### ¿Qué hace el comando?
1. Instala las dependencias necesarias de **Tailwind CSS**.
2. Genera un archivo CSS en `resources/css/filament/{panel}/theme.css`.
3. Intenta agregar automáticamente el tema al array `input` de `vite.config.js`.
4. Intenta registrar automáticamente `->viteTheme()` en el proveedor del panel.
5. Ofrece compilar el tema con **Vite**.

Si el comando no puede configurar automáticamente tus archivos (debido a un formato no estándar), mostrará instrucciones manuales.

---
### Configuración manual

#### Paso 1: Añadir el archivo CSS del tema a `vite.config.js`
```javascript
input: [
    // ...
    'resources/css/filament/admin/theme.css',
]
```

#### Paso 2: Registrar el tema compilado con Vite en el proveedor del panel
```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->viteTheme('resources/css/filament/admin/theme.css');
}
```

#### Paso 3: Compilar el tema con Vite
```bash
npm run build
```
**Nota:** Verifica la salida del comando para confirmar la ruta exacta del archivo (ej: `admin/theme.css`), ya que puede variar según el ID del panel.

Ahora puedes personalizar el tema editando el archivo CSS en `resources/css/filament/`.

---
### Usar clases de Tailwind CSS en tus vistas Blade o archivos PHP
Un **tema personalizado** es necesario para usar **clases de Tailwind CSS** en tu propio código. La hoja de estilos compilada por defecto de Filament **no incluye clases arbitrarias de Tailwind**, solo los estilos necesarios para los componentes de la interfaz de Filament.

Si deseas usar **clases de utilidad de Tailwind CSS** (como `text-primary-600`, `bg-gray-100`, `p-4`, etc.) en tus propias vistas Blade, componentes Livewire o archivos PHP, **debes crear un tema personalizado primero**.

---
### Configurar Tailwind CSS para tu proyecto
Para usar clases de Tailwind CSS en tu proyecto, sigue estos pasos:

1. **Ejecuta el comando para crear un tema personalizado**:
   ```bash
   php artisan make:filament-theme
   ```

2. **En el archivo `theme.css` generado**, encontrarás directivas `@source` que le indican a Tailwind CSS dónde buscar clases:
   ```css
   @source '../../../../app/Filament/**/*';
   @source '../../../../resources/views/filament/**/*';
   ```

3. **Añade tus propios directorios** donde uses clases de Tailwind. Por ejemplo:
   ```css
   @source '../../../../app/Filament/**/*';
   @source '../../../../resources/views/filament/**/*';
   @source '../../../../resources/views/components/**/*';
   @source '../../../../resources/views/livewire/**/*';
   @source '../../../../app/Livewire/**/*';
   ```

4. **Recompila tu tema**:
   ```bash
   npm run build
   ```

Puedes aprender más sobre la directiva `@source` en la [documentación de Tailwind CSS](https://tailwindcss.com/docs/content-configuration).

---

## Modo oscuro

Por defecto, Filament permite a los usuarios **cambiar entre el modo claro y oscuro**. Las siguientes secciones cubren cómo personalizar este comportamiento.

---
### Deshabilitar el modo oscuro
Para deshabilitar completamente el modo oscuro, usa el archivo de configuración:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->darkMode(false);
}
```

---
### Ocultar el selector de tema
Por defecto, los usuarios pueden cambiar entre el modo claro y oscuro usando el **selector de tema** en el menú de usuario. Si deseas mantener el modo oscuro habilitado pero **evitar que los usuarios lo cambien manualmente** (para que Filament siga el modo predeterminado o la preferencia del sistema del usuario), puedes ocultar el selector de tema usando el método `themeSwitcher(false)`:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->themeSwitcher(false);
}
```
**Diferencia con `darkMode(false)`**:
- `darkMode(false)` **deshabilita completamente** el modo oscuro.
- `themeSwitcher(false)` **mantiene el modo oscuro activo** pero oculta el selector.

---
### Forzar el modo oscuro
Si deseas que el panel **siempre use el modo oscuro**, independientemente de la preferencia del usuario, puedes pasar `isForced: true` al método `darkMode()`. Esto también oculta el selector de tema:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->darkMode(isForced: true);
}
```

---
### Cambiar el modo de tema predeterminado
Por defecto, Filament usa el **modo de tema del sistema** del usuario (ej: si el sistema está en modo oscuro, Filament usará el modo oscuro). Este modo es **reactivo**: si el usuario cambia el modo de su sistema, Filament lo detectará.

Si deseas forzar el **modo claro u oscuro** como predeterminado, usa el método `defaultThemeMode()` con `ThemeMode::Light` o `ThemeMode::Dark`:

```php
use Filament\Enums\ThemeMode;
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->defaultThemeMode(ThemeMode::Light); // Fuerza el modo claro
}
```

---
## Añadir un logo

Por defecto, Filament utiliza el **nombre de tu aplicación** para renderizar un logo basado en texto. Sin embargo, puedes personalizarlo fácilmente.

---
### Cambiar el texto del logo
Si solo deseas cambiar el texto del logo, usa el método `brandName()`:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->brandName('Demo de Filament');
}
```

---
![Vista previa de un panel con nombre de marca personalizado](https://via.placeholder.com/600x400)

---
### Usar una imagen como logo
Para renderizar una imagen, pasa una **URL** al método `brandLogo()`:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->brandLogo(asset('images/logo.svg'));
}
```

---
![Vista previa de un panel con logo personalizado](https://via.placeholder.com/600x400)

---
### Usar HTML personalizado para el logo
También puedes pasar **HTML** directamente al método `brandLogo()` para renderizar, por ejemplo, un SVG en línea:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->brandLogo(fn () => view('filament.admin.logo'));
}
```
**Ejemplo de vista `filament.admin.logo`**:
```html
<svg
    viewBox="0 0 128 26"
    xmlns="http://www.w3.org/2000/svg"
    class="h-full fill-gray-500 dark:fill-gray-400"
>
    <!-- Contenido del SVG -->
</svg>
```

---
### Logo para el modo oscuro
Si necesitas un **logo diferente para el modo oscuro**, usa el método `darkModeBrandLogo()` de la misma manera:

```php
->darkModeBrandLogo(asset('images/logo-dark.svg'));
```

---
### Personalizar la altura del logo
La altura del logo tiene un valor predeterminado, pero puedes personalizarla usando el método `brandLogoHeight()`:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->brandLogo(fn () => view('filament.admin.logo'))
        ->brandLogoHeight('2rem'); // Altura personalizada
}
```

---
## Añadir un favicon
Para añadir un **favicon**, usa el método `favicon()` en el archivo de configuración, pasando la **URL pública** del favicon:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->favicon(asset('images/favicon.png'));
}
```

---
```

---
