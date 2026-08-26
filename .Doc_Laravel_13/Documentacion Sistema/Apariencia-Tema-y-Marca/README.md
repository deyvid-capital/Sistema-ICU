# Apariencia: tema, colores y marca del panel

El super administrador cambia **colores del sidebar, de la barra superior, color primario, paleta
gris, logo (claro/oscuro), alto del logo y favicon** desde una página del propio panel, **sin tocar
código y sin recompilar assets**.

Los cambios se aplican al recargar la página. Implementado solo con APIs públicas de Filament 5
(`FilamentColor`, render hooks, `brandLogo()`, `favicon()`); sin plugins de terceros.

Guías genéricas relacionadas: [`../../Stilos/Colores.md`](../../Stilos/Colores.md) y
[`../../Stilos/CssHooks.md`](../../Stilos/CssHooks.md). Este documento describe **cómo quedó
implementado en ICU**.

---

## 1. Resumen

| Pieza | Rol |
|---|---|
| `config/apariencia.php` | **Define por código** los temas disponibles, los grises seleccionables y la paleta corporativa |
| Tabla `appearance_settings` | Una sola fila con el tema elegido y los valores personalizados |
| `App\Models\AppearanceSetting` | Modelo de fila única (`AppearanceSetting::actual()`) |
| `App\Services\Apariencia\AparienciaService` | Resuelve tema + overrides, cachea y calcula contraste |
| `App\Observers\AppearanceSettingObserver` | Invalida caché y memo al guardar |
| `resources/views/filament/apariencia/estilos.blade.php` | El **único** CSS acoplado a clases `.fi-*`, inyectado en `panels::styles.after` |
| `ConfiguracionCluster` | Cluster oculto del sidebar, con sub-navegación a la izquierda |
| Páginas `Apariencia` y `Marca` | Formularios de tema/colores y de logo/favicon |
| `AppearanceSetting::registerMediaCollections()` | Colecciones `singleFile()` de logo claro, logo oscuro y favicon |
| `App\Models\Concerns\HasIcuMedia` | URLs, conversiones y purga de rutas heredadas |
| Ítem **Apariencia** en `userMenuItems` | Única vía de acceso (no aparece en el sidebar) |

Flujo de una petición:

```text
Panel::boot()                                    (vendor/filament/filament/src/Panel.php:95)
  ├─► ->colors(fn () => AparienciaService::coloresDelPanel())
  │      └─► FilamentColor::register(...)
  │             └─► AssetManager::renderStyles()  →  <style> :root { --primary-600: oklch(...) }
  │
  ├─► ->brandLogo() / ->darkModeBrandLogo() / ->brandLogoHeight() / ->favicon()   (closures)
  │
  └─► renderHook(STYLES_AFTER)
         └─► estilos.blade.php  →  <style> :root { --icu-sidebar-bg: #003973; ... }
                                            .fi-sidebar { background-color: var(--icu-sidebar-bg) }
```

Todo se resuelve **en tiempo de request** desde una sola lectura cacheada de la tabla. Vite no
participa: los colores nunca pasan por el build.

---

## 2. Qué se ve en el panel

Acceso: **menú de usuario → Apariencia** (visible solo si `Apariencia::canAccess()`).
La página vive en el cluster `Configuración`, que **no se registra en el sidebar**
(`$shouldRegisterNavigation = false`) y usa `SubNavigationPosition::Start`, de modo que la columna
izquierda lista sus páginas:

| Página | Ruta | Contenido |
|---|---|---|
| **Tema y colores** | `/admin/configuracion/apariencia` | Radio de temas con descripción, vista previa en vivo (modo claro y oscuro), colores personalizados y paleta corporativa de referencia |
| **Marca e identidad** | `/admin/configuracion/marca` | Logo claro/oscuro, fondo detrás del logo, alto del logo y favicon |
| **Archivos** | `/admin/configuracion/archivos` | Disco, prefijo, optimización y límites ([guía](../Archivos-y-Media-Library/README.md)) |

Ambas tienen **Guardar cambios** y una acción de restaurar con confirmación:

- En *Tema y colores*, restaurar vuelve al tema por defecto y **conserva** logo y favicon.
- En *Marca e identidad*, restaurar quita los archivos subidos —borrándolos del disco— y
  **conserva** el tema.

Al guardar se recarga la página (`$this->redirect(static::getUrl())`) para que los colores nuevos
se apliquen de inmediato.

---

## 3. Temas y paleta corporativa

Los temas **se definen en código**, en `config/apariencia.php`. Añadir un tema = añadir una entrada;
aparece automáticamente en el radio de la página.

| Clave | Etiqueta | Primario | Sidebar claro | Barra superior clara |
|---|---|---|---|---|
| `safi` *(por defecto)* | Corporativo SAFI | `#003973` | `#003973` | `#FFFFFF` |
| `confianza` | Azul Confianza | `#0081C1` | `#FFFFFF` | `#006B96` |
| `turquesa` | Turquesa Lealtad | `#008A83` | `#006B96` | `#FFFFFF` |
| `neutro` | Neutro Claro | `#003973` | `#FFFFFF` | `#FFFFFF` |

Los tonos claros son los hexadecimales corporativos exactos. Los tonos oscuros de cada tema
(`#041E36`, `#0E1A22`, `#062A38`, `#17191C`…) son variantes elegidas para acompañar esa paleta.

Colores semánticos compartidos por todos los temas (`apariencia.semanticos`):

```php
'info'    => '#0081C1',   // Azul Cielo
'success' => '#13A538',   // Verde Naturaleza
'warning' => 'Amber',     // paleta de Filament
'danger'  => 'Rose',
```

Paleta corporativa completa (`apariencia.paleta_marca`), que la página muestra como muestras con su
hexadecimal para elegir dentro de la marca:

| Color | HEX | Color | HEX |
|---|---|---|---|
| Azul SAFI | `#003973` | Turquesa Integridad | `#42A6AC` |
| Azul Cielo | `#0081C1` | Verde Naturaleza | `#13A538` |
| Azul Confianza | `#006B96` | Verde Próspero | `#9AC31C` |
| Turquesa Lealtad | `#008A83` | Gris SAFI | `#949494` |
| Turquesa Respeto | `#48B9B2` | | |

Grises seleccionables (`apariencia.grises_disponibles`): `Slate` (recomendado), `Zinc`, `Gray`,
`Neutral`, `Stone` y `#949494` (Gris SAFI). El gris afecta a **toda** la interfaz —fondos, bordes,
textos—, no solo a las barras; el campo lo advierte con un `helperText`.

Con el tema `personalizado` se habilitan los `ColorPicker` de sidebar y barra superior (claro y
oscuro), el color primario y el selector de gris. Con cualquier tema curado, los valores guardados
de esas columnas **se ignoran**: manda el config.

---

## 4. Por qué no se usó un plugin

Se revisó el ecosistema antes de implementar:

| Plugin | Resultado |
|---|---|
| [`muazzambuilds/filament-white-label`](https://github.com/muazzambuilds/filament-white-label) (Panel Branding) | Cubre el caso, pero restringe `illuminate/* ^11|^12`: **no instala en Laravel 13** |
| [`andreia/filament-ui-switcher`](https://github.com/andreia/filament-ui-switcher) | Compatible, pero es **por usuario**, sin configuración global, y exige `pnpm build` |
| [`octopyid/filament-palette`](https://github.com/OctopyID/FilamentPalette) | Solo colores semánticos: ni barras, ni logo, ni favicon |
| `Hasnayeen/themes` | Solo Filament v3 |

Ninguno resolvía sidebar + barra superior + logo en Laravel 13, así que se implementó en casa sin
dependencias nuevas.

---

## 5. Cómo se recolorean las barras (y por qué así)

Los colores de las barras **no** salen de `->colors()`: en el CSS compilado de Filament 5.7 son
valores fijos. Lo verificado en `vendor/filament/filament/dist/theme.css`:

```css
.fi-topbar                        { background-color: var(--color-white) }
.fi-topbar:where(.dark,.dark *)   { background-color: var(--gray-900) }
.fi-sidebar                       { background-color: var(--color-white) }
@media (min-width:64rem) {
  .fi-sidebar                     { background-color: #0000 }   /* transparente */
}
```

Dos consecuencias que condicionan la solución:

1. **En escritorio el sidebar es transparente** y lo que se ve es `.fi-body` (`--gray-50`). Por eso
   el override del fondo **se repite dentro de `@media (min-width: 64rem)`**; sin eso, el color solo
   se vería en móvil.
2. El `<style>` del render hook `STYLES_AFTER` se emite **después** del `<link>` del tema
   (`components/layout/base.blade.php`, línea 93 vs 68), así que gana los empates de especificidad
   sin necesidad de `!important`.

Tokens que emite `estilos.blade.php`:

```css
:root  { --icu-sidebar-bg; --icu-sidebar-fg; --icu-sidebar-activo; --icu-topbar-bg; --icu-topbar-fg }
.dark  { …los mismos con los valores del modo oscuro… }
```

Y todo lo legible se deriva de ellos con `color-mix(in oklab, …)` —la misma técnica que usa Filament
para sus propios anillos y hovers—:

| Elemento | Regla |
|---|---|
| Etiqueta de ítem, nombre del usuario | `var(--icu-sidebar-fg)` |
| Etiqueta de grupo | `fg` al 62 % |
| Iconos, botón de colapsar | `fg` al 72 % |
| Hover de ítem / menú de usuario | `fg` al 8 % |
| Ítem activo (fondo) | `fg` al 13 % |
| Ítem activo (texto e icono) | `var(--icu-sidebar-activo)` |
| Líneas de ítems agrupados | `fg` al 35 % |
| Búsqueda global (dentro de la barra superior) | fondo `fg` 8 %, anillo `fg` 18 %, placeholder `fg` 60 % |

### Ámbito obligatorio: `.fi-sidebar`

**Todas** las reglas de ítems van acotadas a `.fi-sidebar`:

```css
.fi-sidebar .fi-sidebar-item-label { color: var(--icu-sidebar-fg) }
```

Motivo: la sub-navegación lateral de una página **reutiliza los componentes del sidebar**
(`vendor/filament/filament/resources/views/components/page/sub-navigation/sidebar.blade.php` renderiza
`<x-filament-panels::sidebar.group>`), así que genera `fi-sidebar-item-label`, `fi-sidebar-item-btn`,
etc. **fuera** de `.fi-sidebar`, sobre el fondo blanco de la página. Sin acotar, esa columna heredaba
texto blanco sobre blanco y quedaba invisible, con la píldora del ítem activo también invisible.
Acotada, la sub-navegación conserva su estilo nativo de Filament (etiqueta gris, activo con píldora
gris y texto en `--primary-700`, es decir el color de marca del tema).

Hay un test de regresión que falla si vuelve a aparecer un selector sin acotar.

---

## 6. Contraste automático

El texto y el acento de cada barra **no se configuran**: se calculan con la API pública de contraste
de Filament, de modo que ningún fondo elegido deja el menú ilegible.

```php
// AparienciaService::tokensDeBarra()
$textoClaroEsLegible = Color::calculateContrastRatio($fondo, '#FFFFFF')
    >= Color::calculateContrastRatio($fondo, '#0F1419');

$tono = Color::findShade(
    palette: $paletaPrimaria,
    surface: $fondo,
    minRatio: Color::WCAG_AA_TEXT,              // 4.5:1
    shouldStartFromDarkest: $textoClaroEsLegible,
);
```

- `fg` → `#FFFFFF` o `var(--gray-950)`, el que más contraste dé contra el fondo.
- `activo` → `var(--primary-{$tono})`, el primer tono de la paleta primaria que cumple AA contra ese
  fondo (recorriendo de oscuro a claro cuando el fondo es oscuro). Si ningún tono cumple, cae a `fg`.
- `activo_hex` / `fg_hex` → los mismos valores resueltos a hexadecimal, para la vista previa del
  formulario (que muestra colores del estado del formulario, no de la paleta ya registrada).

Además, todo hexadecimal se valida con `/^#[0-9A-Fa-f]{6}$/` antes de llegar a la hoja de estilos
(`normalizarColor()`), y los `ColorPicker` aplican la misma regla en el formulario.

---

## 7. Placa clara detrás del logo

El logo institucional (`public/images/Capital-Safi-SA-logo-.png`) es **azul oscuro sobre fondo
transparente**: sobre una barra oscura desaparece. Se detectó en la verificación visual con el tema
*Azul Confianza* (barra superior `#006B96`), donde el logo quedaba ilegible.

`AparienciaService::placaDeLogo()` decide **por barra y por modo** si hay que pintar una placa blanca
redondeada detrás del logo. Es necesario decidirlo por barra porque el logo se dibuja en la **barra
superior** cuando el sidebar es colapsable (el caso de este panel, con
`sidebarFullyCollapsibleOnDesktop()`) y en la cabecera del sidebar cuando no lo es.

Campo **Fondo detrás del logo** (columna `fondo_logo`), en *Marca e identidad*:

| Valor | Comportamiento |
|---|---|
| `auto` *(por defecto)* | Placa solo si la barra es oscura **y** se está usando el logo incluido en la app (del que sí se sabe que es oscuro). En modo oscuro, además, solo si no se subió un logo específico para modo oscuro |
| `claro` | Placa siempre que la barra sea oscura, también con logo propio |
| `ninguno` | Nunca |

Así se evitan los dos fallos posibles: logo oscuro invisible sobre barra oscura, y placa blanca
inútil (o contraproducente) detrás de un logo ya claro subido por el administrador.

---

## 8. Base de datos y caché

```text
appearance_settings   (una sola fila)
├─ tema                                   safi | confianza | turquesa | neutro | personalizado
├─ color_primario, color_gris             solo se usan con tema = personalizado
├─ color_sidebar_claro / _oscuro
├─ color_topbar_claro  / _oscuro
├─ logo_claro, logo_oscuro, favicon       rutas heredadas (pre Media Library); ver §8.1
├─ alto_logo                              valor CSS (`2.5rem`, `40px`…), validado por regex
└─ fondo_logo                             auto | claro | ninguno
```

Caché: `Cache::rememberForever('apariencia.resuelta')` más un memo en el propio servicio (que está
registrado como **singleton**). `AppearanceSettingObserver` llama a `AparienciaService::olvidar()`,
que limpia **ambos** —la caché y el memo—; con solo `Cache::forget()`, un proceso largo (cola, test,
Octane) seguiría sirviendo los colores anteriores.

`Panel::boot()` también se ejecuta en consola (`migrate`, `queue:work`), cuando la tabla puede no
existir todavía: `leerFila()` comprueba `Schema::hasTable()` y captura cualquier `Throwable`,
devolviendo el tema del config. Es lo que permite migrar desde cero sin romper el panel.

### 8.1 Archivos: Media Library

El logo y el favicon **no** se guardan en las columnas: viven en **Spatie Media Library**, en tres
colecciones declaradas en `AppearanceSetting::registerMediaCollections()`, todas `->singleFile()`:

| Colección | Tipos aceptados | Límite | Conversiones |
|---|---|---|---|
| `logo_claro` | png, jpeg, webp, svg | `ArchivosService::maxLogoKb()` | `thumb`, y `webp` si supera el umbral |
| `logo_oscuro` | png, jpeg, webp, svg | `ArchivosService::maxLogoKb()` | `thumb`, y `webp` si supera el umbral |
| `favicon` | ico, png, svg | `ArchivosService::maxFaviconKb()` | ninguna (no se convierten svg/ico) |

El disco, el prefijo de rutas y los límites los resuelve `App\Services\Archivos\ArchivosService`
(config `config/archivos.php` + fila opcional en `media_settings`, configurable en
**Configuración → Archivos**). Documentación completa del subsistema de archivos:
[`../Archivos-y-Media-Library/README.md`](../Archivos-y-Media-Library/README.md).

Las rutas las construye `App\Media\IcuPathGenerator`:

```text
{prefijo}/{coleccion}/{Modelo}/{id}/{media_id}/archivo.png
{prefijo}/{coleccion}/{Modelo}/{id}/{media_id}/conversions/archivo-thumb.png
```

Las **columnas heredadas** `logo_claro`, `logo_oscuro` y `favicon` son de la primera versión de esta
página (subida directa con `FileUpload` a `apariencia/`). `HasIcuMedia::storedImageUrl()` y
`AparienciaService` prefieren la media y solo caen a la columna si la colección está vacía, de modo
que una instalación sin migrar sigue mostrando su logo. El comando
`icu:migrar-imagenes-a-media` pasa esos archivos a Media Library con `preservingOriginal()`, así que
tras migrar **la ruta y el archivo antiguo siguen existiendo** hasta que se purguen (ver §8.2).

### 8.2 Ciclo de vida de los archivos: sin residuos

Garantías al reemplazar logo claro, logo oscuro o favicon:

| Situación | Qué pasa con el archivo anterior |
|---|---|
| Se sube un archivo nuevo en una colección | Media Library lo reemplaza: `singleFile()` es `collectionSizeLimit = 1`, así que `FileAdder` llama a `clearMediaCollectionExcept()`, se borra la fila de `media` y `MediaObserver::deleted` borra del disco **el original, sus conversiones y la carpeta** |
| Se quita el archivo con la **X** de la interfaz y se guarda | Filament elimina esa media al guardar relaciones, con el mismo borrado en disco |
| La colección ya tiene media y además hay una **ruta heredada** | `Marca::purgarArchivosHeredados()` borra el archivo del disco y pone la columna a `null` |
| No hay media pero sí ruta heredada | **No se toca**: ese archivo es el que el panel está usando |
| **Quitar personalización** | `clearBrandMedia()` borra las tres colecciones y se purgan las tres rutas heredadas: no queda nada en disco ni en `media` |

La purga heredada vive en `HasIcuMedia::purgarArchivoHeredado()` —reutilizable por cualquier modelo
con el trait— y se ejecuta en `guardar()` **después** de `saveRelationships()`, para que solo se
borre cuando ya existe el archivo nuevo que la sustituye.

---

## 9. Permisos (Shield)

Las páginas usan `BezhanSalleh\FilamentShield\Traits\HasPageShield`. **Sin ese trait Shield genera
el permiso pero no lo aplica** y cualquier usuario del panel entraría (verificado: un test esperaba
403 y recibía 200).

Permisos generados por la configuración de Shield (`pages.prefix = view`):

```text
View:Apariencia
View:Marca
```

Tras desplegar:

```bash
vendor/bin/sail artisan shield:generate --all --panel=admin --option=permissions
```

El rol `super_admin` los recibe al sincronizar permisos. El ítem del menú de usuario se oculta solo
(`->visible(fn () => Apariencia::canAccess())`).

---

## 10. Cómo ampliarlo

### Añadir un tema

En `config/apariencia.php`:

```php
'institucional' => [
    'etiqueta' => 'Institucional',
    'descripcion' => 'Sidebar Verde Naturaleza con barra superior blanca.',
    'primario' => '#13A538',
    'gris' => 'Slate',
    'sidebar' => ['claro' => '#13A538', 'oscuro' => '#0B2E16'],
    'topbar' => ['claro' => '#FFFFFF', 'oscuro' => '#0A1F10'],
],
```

Aparece en el radio, en la vista previa y en el `restaurar` sin tocar nada más. El contraste del
texto y del ítem activo se recalcula solo.

### Añadir una página de configuración

Crear la clase en `app/Filament/Clusters/Configuracion/Pages/`, con
`$cluster = ConfiguracionCluster::class` y `$navigationSort`. Se descubre sola
(`discoverClusters()` también descubre las páginas del directorio del cluster) y entra en la
sub-navegación izquierda. Recordar `HasPageShield` y regenerar permisos.

### Nuevo CSS acoplado a clases de Filament

Va **solo** en `estilos.blade.php`, siempre acotado (`.fi-sidebar …`, `.fi-topbar …`). Nunca en
`theme.css`: lo que va al tema compilado obliga a `pnpm build` en cada cambio de color.

---

## 11. Qué no hace

- No hay apariencia **por usuario**: la configuración es global.
- No configura fuente tipográfica, `brandName()`, navegación superior vs lateral, ancho del sidebar
  ni CSS libre. La tabla y el servicio admiten añadirlo sin migración destructiva.
- No recolorea el modo oscuro de forma independiente por elemento: cada barra tiene un color para
  modo claro y otro para modo oscuro, y el resto se deriva por contraste.
- No toca la pantalla de login más allá del logo, el favicon y los colores registrados.
- Cambiar el disco o el prefijo en **Configuración → Archivos** no mueve los archivos ya subidos:
  las rutas guardadas en `media` siguen apuntando al disco anterior.

---

## 12. Archivos

**Nuevos**

```text
config/apariencia.php
app/Models/AppearanceSetting.php
app/Observers/AppearanceSettingObserver.php
app/Services/Apariencia/AparienciaService.php
app/Filament/Clusters/Configuracion/ConfiguracionCluster.php
app/Filament/Clusters/Configuracion/Pages/Apariencia.php
app/Filament/Clusters/Configuracion/Pages/Marca.php
resources/views/filament/apariencia/estilos.blade.php
resources/views/filament/apariencia/vista-previa.blade.php
resources/views/filament/apariencia/muestras-marca.blade.php
resources/views/filament/clusters/configuracion/pages/apariencia.blade.php
resources/views/filament/clusters/configuracion/pages/marca.blade.php
database/migrations/2026_08_25_153251_create_appearance_settings_table.php
database/migrations/2026_08_25_154829_add_fondo_logo_to_appearance_settings_table.php
tests/Unit/Services/AparienciaServiceTest.php
tests/Feature/Filament/AparienciaPageTest.php
tests/Feature/Filament/AparienciaRenderTest.php
tests/Feature/Filament/MarcaPageTest.php
```

**Modificados (puntos de enganche)**

```text
app/Providers/AppServiceProvider.php            # singleton del servicio + observer + path generator
app/Providers/Filament/AdminPanelProvider.php   # colors()/brandLogo()/favicon() como closures,
                                                # renderHook(STYLES_AFTER), ítem "Apariencia" en userMenuItems
app/Models/Concerns/HasIcuMedia.php             # purgarArchivoHeredado()
app/Models/AppearanceSetting.php                # colecciones de media + clearBrandMedia()
```

Piezas compartidas con el subsistema de archivos (no exclusivas de apariencia): ver
[`../Archivos-y-Media-Library/README.md`](../Archivos-y-Media-Library/README.md)
(`ArchivosService`, `IcuPathGenerator`, `MediaSetting`, `config/archivos.php`,
`icu:migrar-imagenes-a-media`, página **Configuración → Archivos**).

`resources/css/filament/admin/theme.css` **no se modificó**. Las utilidades Tailwind de la vista
previa se compilan por el `@source '../../../../resources/views/filament/**/*'` que ya existía; hizo
falta un `pnpm build` una única vez al implementar. **Cambiar colores no requiere compilar nada.**

---

## 13. Pruebas

```bash
vendor/bin/sail artisan test --compact tests/Unit/Services/AparienciaServiceTest.php \
  tests/Feature/Filament/AparienciaPageTest.php \
  tests/Feature/Filament/MarcaPageTest.php \
  tests/Feature/Filament/AparienciaRenderTest.php
```

26 tests. Cubren:

- **Resolución**: sin fila se usa el tema por defecto; un tema curado ignora los colores
  personalizados guardados; `personalizado` los respeta.
- **Contraste**: fondo oscuro → texto blanco; fondo blanco → `var(--gray-950)`; el tono del ítem
  activo cambia según el fondo.
- **Saneamiento**: un color inválido (`rojo; background: url(x)`) nunca llega al CSS.
- **Caché**: guardar invalida la resolución cacheada.
- **Placa del logo**: se activa por barra según el tema, se desactiva con logo propio y se puede
  forzar o anular.
- **Páginas**: carga con sub-navegación, guardado de tema curado y de colores personalizados,
  rechazo de hexadecimal inválido, restaurar conserva la marca, y al elegir tema se precargan sus
  colores.
- **Marca**: subida de logo con `Storage::fake`, alto de logo inválido rechazado, restaurar vuelve a
  los archivos de la aplicación.
- **Sin residuos** (`MarcaPageTest`): reemplazar logo claro, logo oscuro o favicon borra del disco el
  archivo anterior y su fila en `media` (queda exactamente uno); quitar el archivo con la X lo borra;
  subir uno nuevo purga la ruta heredada; una ruta heredada **sin** media no se toca porque es la que
  el panel usa; y *Quitar personalización* no deja ningún archivo ni fila.
- **Render**: el panel inyecta los tokens del tema guardado, renderiza sin fila en la tabla, resuelve
  aunque la tabla no exista, **las reglas del menú están acotadas a `.fi-sidebar`**, y solo quien
  tiene el permiso de Shield entra (403 en caso contrario).

Verificación visual realizada con Browsershot sobre el panel real (los cuatro temas y las dos
páginas de configuración); las capturas fueron temporales y no se versionan.
