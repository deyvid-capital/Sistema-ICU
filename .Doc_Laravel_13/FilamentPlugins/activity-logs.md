# Documentación: Spatie/Laravel-activitylog para Filament

---

## 📌 Información general

**Versión más reciente en Packagist** | **Licencia de software** | **Estilo de código en GitHub** | **Estado de acciones** | **Descargas totales**

Este paquete proporciona un **recurso de Filament** que muestra todos los registros de actividad y una vista detallada de cada registro creado utilizando el paquete `[spatie/laravel-activitylog](https://github.com/spatie/laravel-activitylog)`. También incluye un **gestor de relaciones** para modelos relacionados.

---

## 🌿 Ramas


| Rama | Versión de Filament |
| ---- | ------------------- |
| 1.x  | v3                  |
| 2.x  | v4                  |
| 3.x  | v5                  |


---

## 📋 Requisitos

- **Laravel v12**
- **Filament v5**
- **Spatie/Laravel-activitylog v4**

---

## 🌍 Idiomas soportados

El plugin **ActivityLog** está traducido a los siguientes idiomas:

- 🇧🇷 Portugués de Brasil
- 🇺🇸 Inglés
- 🇩🇪 Alemán
- 🇪🇸 Español
- 🇫🇷 Francés
- 🇮🇷 Persa
- 🇦🇪 Árabe
- 🇵🇹 Portugués
- 🇮🇱 Hebreo
- 🇳🇱 Holandés
- 🇱🇻 Letón

---

## 📥 Instalación



### 1. Instalar el paquete

Puedes instalar el paquete mediante **Composer**:

```bash
composer require rmsramos/activitylog
```

### 2. Ejecutar el comando de instalación

```bash
php artisan activitylog:install
```

Esto publicará la configuración y las migraciones de `spatie/laravel-activitylog`.

### 3. Ejecutar las migraciones

```bash
php artisan migrate
```

### 4. Publicar el archivo de configuración manualmente

Si lo deseas, puedes publicar el archivo de configuración manualmente:

```bash
php artisan vendor:publish --tag="activitylog-config"
```

### 5. Contenido del archivo de configuración publicado

```php
return [
    'resources' => [
        'label'                     => 'Registro de Actividad',
        'plural_label'              => 'Registros de Actividad',
        'hide_restore_action'       => false,
        'restore_action_label'      => 'Restaurar',
        'hide_resource_action'      => false,
        'hide_restore_model_action' => true,
        'resource_action_label'     => 'Ver',
        'navigation_item'           => true,
        'navigation_group'          => null,
        'navigation_icon'           => 'heroicon-o-shield-check',
        'navigation_sort'           => null,
        'default_sort_column'       => 'id',
        'default_sort_direction'    => 'desc',
        'navigation_count_badge'    => false,
        'resource'                  => \Rmsramos\Activitylog\Resources\Activitylog\ActivitylogResource::class,
    ],
    'date_format'     => 'd/m/Y',
    'datetime_format' => 'd/m/Y H:i:s',
];
```

### 6. Publicar las vistas (opcional)

```bash
php artisan vendor:publish --tag="activitylog-views"
```

---

## 🎨 Estilos (Filament v4/v5 + Tailwind v4)

**Tailwind v4** solo genera CSS para las clases que encuentra al escanear los archivos de tu proyecto. Dado que las vistas Blade de este paquete están en `vendor/`, el proceso de construcción de Tailwind de tu panel no las detectará a menos que se lo indiques. Sin este paso, el modal de **Timeline Action** se renderizará sin sus iconos, líneas de tiempo y espaciado.

### Solución:

Agrega esta directiva `@source` al archivo `theme.css` de tu panel (por ejemplo, `resources/css/filament/admin/theme.css`), junto con otras fuentes de paquetes de proveedores:

```css
@source '../../../../vendor/rmsramos/activitylog/resources/**/*';
```

Luego, reconstruye tus activos:

```bash
npm run build
```

---

## 🛠️ Uso

---

### Uso básico de Spatie ActivityLog

En tu **Modelo**, agrega el **trait** `Spatie\Activitylog\Traits\LogsActivity` y configura la función `getActivitylogOptions`:

```php
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class NewsItem extends Model
{
    use LogsActivity;

    protected $fillable = ['name', 'text'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'text']);
    }
}
```

Para más configuraciones, revisa la [documentación de Spatie](https://spatie.be/docs/laravel-activitylog).

---

### Uso del plugin

![Captura de pantalla de la función de la aplicación](#)

En tu **Panel ServiceProvider** (por ejemplo, `App\Providers\Filament`), activa el plugin:

```php
use Rmsramos\Activitylog\ActivitylogPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            ActivitylogPlugin::make(),
        ]);
}
```

---

## ⚙️ Personalización

### Personalizar el `ActivitylogResource`

Puedes reemplazar el `ActivitylogResource` utilizado actualizando el valor de `->resource()`. Usa esto para crear tu propia clase `CustomResource` y extender la original en `\Rmsramos\Activitylog\Resources\Activitylog\ActivitylogResource::class`. Esto te permitirá personalizar todo, como las vistas, tablas, formularios y permisos.

> **⚠️ Nota:** Si deseas cambiar el recurso en las páginas **List** y **View**, asegúrate de reemplazar el método `getPages` en el nuevo recurso y crear tus propias versiones de las clases `ListPage` y `ViewPage` para referenciar el `CustomResource`.

```php
use Rmsramos\Activitylog\ActivitylogPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            ActivitylogPlugin::make()
                ->resource(\Path\For\Your\CustomResource::class),
        ]);
}
```

---

### Personalizar las etiquetas del recurso

Puedes cambiar las etiquetas del recurso actualizando los valores de `->label()` y `->pluralLabel()`:

```php
use Rmsramos\Activitylog\ActivitylogPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            ActivitylogPlugin::make()
                ->label('Registro')
                ->pluralLabel('Registros'),
        ]);
}
```

---



### Mostrar el recurso en la navegación

Puedes habilitar o deshabilitar el elemento de navegación del recurso actualizando el valor de `->navigationItem()`:

```php
use Rmsramos\Activitylog\ActivitylogPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            ActivitylogPlugin::make()
                ->navigationItem(false), // Por defecto es true
        ]);
}
```

---



### Agrupar elementos de navegación del recurso

Puedes agregar un grupo de navegación para el recurso actualizando el valor de `->navigationGroup()`:

```php
use Rmsramos\Activitylog\ActivitylogPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            ActivitylogPlugin::make()
                ->navigationGroup('Registro de Actividad'),
        ]);
}
```

---



### Personalizar el icono de navegación del recurso

Puedes cambiar el icono de navegación del recurso actualizando el valor de `->navigationIcon()`:

```php
use Rmsramos\Activitylog\ActivitylogPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            ActivitylogPlugin::make()
                ->navigationIcon('heroicon-o-shield-check'),
        ]);
}
```

---



### Activar un badge de conteo

Puedes activar un **badge de conteo** en la navegación actualizando el valor de `->navigationCountBadge()`:

```php
use Rmsramos\Activitylog\ActivitylogPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            ActivitylogPlugin::make()
                ->navigationCountBadge(true),
        ]);
}
```

---



### Establecer el orden de navegación

Puedes establecer el orden de navegación del recurso actualizando el valor de `->navigationSort()`:

```php
use Rmsramos\Activitylog\ActivitylogPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            ActivitylogPlugin::make()
                ->navigationSort(3),
        ]);
}
```

---



## 🔒 Autorización

Si deseas evitar que ciertos usuarios accedan al recurso de registros, puedes agregar un callback de autorización en la cadena del `ActivitylogPlugin`:

```php
use Rmsramos\Activitylog\ActivitylogPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            ActivitylogPlugin::make()
                ->authorize(
                    fn () => auth()->user()->id === 1
                ),
        ]);
}
```

---

## 🌐 Traducción

---

### Traducir nombres de recursos

Para traducir los nombres de los recursos en el registro de actividad, agrega un callback `translateSubject` en la cadena del `ActivitylogPlugin`:

```php
use Rmsramos\Activitylog\ActivitylogPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            ActivitylogPlugin::make()
                ->translateSubject(fn($label) => __("yourCustomLangFile.".$label)),
        ]);
}
```

---

### Traducir nombres de claves del registro de actividad

Para traducir los nombres de las claves en el registro de actividad, agrega un callback `translateLogKey`:

```php
use Rmsramos\Activitylog\ActivitylogPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            ActivitylogPlugin::make()
                ->translateLogKey(fn($label) => __("yourCustomLangFile.".$label)),
        ]);
}
```

---

## 📅 Personalización de fechas

---

### Personalizar el análisis de fechas

Para personalizar cómo se analizan las fechas según las preferencias del usuario:

```php
use Rmsramos\Activitylog\ActivitylogPlugin;
use Morilog\Jalali\Jalalian;
use Carbon\Carbon;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            ActivitylogPlugin::make()
                ->dateParser(
                    fn($date) => auth()->user()->isJalaliCalendar() ?
                        Jalalian::fromDateTime($date) :
                        Carbon::parse($date)
                )
        ]);
}
```

---

### Personalizar formatos de fecha y fecha-hora

Para personalizar el formato de las columnas de fecha y fecha-hora según la configuración del usuario:

```php
use Rmsramos\Activitylog\ActivitylogPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            ActivitylogPlugin::make()
                ->dateFormat('Y-m-d')
                ->datetimeFormat(fn() => auth()->user()->getFilamentDateTimeFormat())
        ]);
}
```

---

### Personalizar columnas de fecha-hora

Para personalizar condicionalmente las columnas de fecha-hora en la interfaz según la preferencia de calendario del usuario:

```php
use Rmsramos\Activitylog\ActivitylogPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            ActivitylogPlugin::make()
                ->customizeDatetimeColumn(function ($column) {
                    return $column->when(
                        auth()->user()->isJalaliCalendar(),
                        function ($column) {
                            return $column->jalaliDateTime();
                        }
                    );
                })
        ]);
}
```

---

### Personalizar campos de selector de fecha

Para personalizar los campos de selector de fecha en los formularios según las preferencias del usuario:

```php
use Rmsramos\Activitylog\ActivitylogPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            ActivitylogPlugin::make()
                ->customizeDatePicker(function ($field) {
                    return $field->when(
                        auth()->user()->isJalaliCalendar(),
                        function ($field) {
                            return $field->jalali();
                        }
                    );
                })
        ]);
}
```

---

## 🔧 Manipular la acción de vista usando un trait personalizado

Implementa `getFilamentActualResourceModel` en el **trait** `HasCustomActivityResource` para determinar el modelo real relacionado con el registro de actividad y generar URLs válidas.

```php
use Rmsramos\Activitylog\Traits\HasCustomActivityResource;

trait HasCustomActivityResource
{
    public function getFilamentActualResourceModel($record)
    {
        $record = $record->subject->translatable;
        $model = null;

        switch ($record::class) {
            case FirstTranslatableModel::class:
                $model = $record->firstModel;
                break;

            case SecondTranslatableModel::class:
                $model = $record->secondModel;
                break;

            default:
                throw new Exception("Error: Modelo de sujeto traducible no encontrado. Registro = ".$record::class, 1);
                break;
        }

        return $model;
    }
}
```

---

## 🚫 Ocultar acciones de restaurar/ver

Para ocultar globalmente las acciones de **restaurar** o **ver** en un recurso dentro del `ActivitylogPlugin`, puedes usar los métodos `isRestoreActionHidden` y `isResourceActionHidden`. También puedes personalizar la etiqueta de la acción de vista:

```php
use Rmsramos\Activitylog\ActivitylogPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            ActivitylogPlugin::make()
                ->isRestoreActionHidden(true)
                ->isResourceActionHidden(true)
                ->resourceActionLabel("Etiqueta personalizada"),
        ]);
}
```

---

### Mostrar acción de restaurar (Soft Deletes)

En el archivo de configuración de `laravel-activitylog` (`config/activitylog.php`):

```php
return [
    'subject_returns_soft_deleted_models' => true,
];
```

Para mostrar globalmente la acción de **restaurar** (soft delete) de un recurso dentro del `ActivitylogPlugin`, puedes usar el método `isRestoreModelActionHidden`:

```php
use Rmsramos\Activitylog\ActivitylogPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            ActivitylogPlugin::make()
                ->isRestoreModelActionHidden(false)
        ]);
}
```

---

## 🛡️ Política de roles

Para asegurar el acceso al `ActivitylogResource` mediante **RolePolicy**, debes agregar lo siguiente a tu `AppServiceProvider`:

```php
use App\Policies\ActivityPolicy;
use Illuminate\Support\Facades\Gate;
use Spatie\Activitylog\Models\Activity;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Activity::class, ActivityPolicy::class);
    }
}
```

---

## ⚙️ Configuración completa

```php
use Rmsramos\Activitylog\ActivitylogPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            ActivitylogPlugin::make()
                ->resource(\Path\For\Your\CustomResource::class)
                ->label('Registro')
                ->pluralLabel('Registros')
                ->navigationItem(true)
                ->navigationGroup('Registro de Actividad')
                ->navigationIcon('heroicon-o-shield-check')
                ->navigationCountBadge(true)
                ->navigationSort(2)
                ->authorize(
                    fn () => auth()->user()->id === 1
                )
                ->translateSubject(fn($label) => __("yourCustomLangFile.".$label))
                ->dateParser(
                    fn($date) => auth()->user()->isJalaliCalendar() ?
                        Jalalian::fromDateTime($date) :
                        Carbon::parse($date)
                )
                ->dateFormat('Y-m-d')
                ->datetimeFormat(fn() => auth()->user()->getFilamentDateTimeFormat())
                ->customizeDatetimeColumn(function ($column) {
                    return $column->when(
                        auth()->user()->isJalaliCalendar(),
                        function ($column) {
                            return $column->jalaliDateTime();
                        }
                    );
                })
                ->customizeDatePicker(function ($field) {
                    return $field->when(
                        auth()->user()->isJalaliCalendar(),
                        function ($field) {
                            return $field->jalali();
                        }
                    );
                })
                ->isRestoreActionHidden(true)
                ->isResourceActionHidden(true)
                ->isRestoreModelActionHidden(false)
                ->resourceActionLabel("Etiqueta personalizada"),
        ]);
}
```

---

## 🔗 Gestor de relaciones

Si tienes un modelo que utiliza el **trait** `Spatie\Activitylog\Traits\LogsActivity`, puedes agregar el gestor de relaciones `Rmsramos\Activitylog\RelationManagers\ActivitylogRelationManager` a tu recurso de Filament para mostrar todos los registros de actividad realizados en tu modelo.

![Captura de pantalla de la función de la aplicación](#)

```php
use Rmsramos\Activitylog\RelationManagers\ActivitylogRelationManager;

public static function getRelations(): array
{
    return [
        ActivitylogRelationManager::class,
    ];
}
```

---

## ⏳ Acción de línea de tiempo

![Captura de pantalla de la función de la aplicación](#)

Para facilitar la visualización de los registros de actividad, puedes usar una **acción personalizada**. En tu `UserResource`, en la función `table`, agrega `ActivityLogTimelineTableAction`:

```php
use Rmsramos\Activitylog\Actions\ActivityLogTimelineTableAction;

public static function table(Table $table): Table
{
    return $table
        ->actions([
            ActivityLogTimelineTableAction::make('Actividades'),
        ]);
}
```



### Pasar relaciones

Puedes pasar una matriz con las relaciones (asegúrate de configurar tus modelos):

```php
public static function table(Table $table): Table
{
    return $table
        ->actions([
            ActivityLogTimelineTableAction::make('Actividades')
                ->withRelations(['profile', 'address']), // Opcional
        ]);
}
```



### Personalizar iconos y colores

Por defecto, se usa el icono `'heroicon-m-check'` y el color `'primary'`. Puedes personalizarlos:

```php
use Rmsramos\Activitylog\Actions\ActivityLogTimelineTableAction;

public static function table(Table $table): Table
{
    return $table
        ->actions([
            ActivityLogTimelineTableAction::make('Actividades')
                ->timelineIcons([
                    'created' => 'heroicon-m-check-badge',
                    'updated' => 'heroicon-m-pencil-square',
                ])
                ->timelineIconColors([
                    'created' => 'info',
                    'updated' => 'warning',
                ])
        ]);
}
```



### Limitar el número de resultados

Puedes limitar el número de resultados en la consulta pasando un `limit`. Por defecto, se devuelven los últimos **10 registros**:

```php
use Rmsramos\Activitylog\Actions\ActivityLogTimelineTableAction;

public static function table(Table $table): Table
{
    return $table
        ->actions([
            ActivityLogTimelineTableAction::make('Actividades')
                ->limit(30),
        ]);
}
```

---



### Configuración completa de la línea de tiempo

```php
use Rmsramos\Activitylog\Actions\ActivityLogTimelineTableAction;

public static function table(Table $table): Table
{
    return $table
        ->actions([
            ActivityLogTimelineTableAction::make('Actividades')
                ->withRelations(['profile', 'address'])
                ->timelineIcons([
                    'created' => 'heroicon-m-check-badge',
                    'updated' => 'heroicon-m-pencil-square',
                ])
                ->timelineIconColors([
                    'created' => 'info',
                    'updated' => 'warning',
                ])
                ->limit(10),
        ]);
}
```

---

## 📜 Registro de cambios

Consulta el archivo **[CHANGELOG](#)** para más información sobre los cambios recientes.

---

## 🤝 Contribuciones

Consulta el archivo **[CONTRIBUTING](#)** para más detalles.

---

## 🛡️ Vulnerabilidades de seguridad

Revisa nuestra **[política de seguridad](#)** para reportar vulnerabilidades de seguridad.

---

## 🙏 Reconocimientos

Un agradecimiento especial a estas herramientas y personas (desarrolladores), ya que el plugin **Activity Log** solo existe gracias a la inspiración y, en algún momento, el uso de los códigos de estas personas:

- Jay-Are Ocero
- Alex Justesen
- z3d0x
- Filament
- Colaboradores de Spatie Activitylog

---

## 👨‍💻 Créditos

- **Rômulo Ramos**
- Todos los colaboradores

---

## 📄 Licencia

**Licencia MIT (MIT)**. Consulta el archivo **[LICENSE](#)** para más información.

