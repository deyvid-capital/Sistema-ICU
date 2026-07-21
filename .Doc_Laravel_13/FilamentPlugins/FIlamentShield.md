# Filament Shield

El acceso más rápido e intuitivo para manejar **políticas** y **permisos** en los paneles de Filament.

> ⚡ Compatible con modo oscuro  
> 🌐 Soporte multilingüe  
> 🚀 Compatible con Filament v5.x, v4.x, 3.x y 2.x

**Etiquetas:**  
Filament Panels, Autorización de Panel, Integración con Spatie

**Versiones compatibles:**  
- 5.x  
- 4.x  
- 3.x  
- 2.x  

[Visítanos en GitHub](https://github.com/bezhansalleh/filament-shield)  
⭐ 1,677  

**Autor:** Bezhan Salleh  
**Salud del Paquete:** Beta

---

## Características principales

- 🛡️ Gestión completa de autorizaciones
- 📦 Permisos de recursos
- 📄 Permisos de páginas
- 🧩 Permisos de widgets
- 🛠️ Permisos personalizados (ad-hoc)
- 🤖 Generación automática de políticas
- 📜 Métodos por defecto de políticas para recursos Filament
- 🏷️ Definición de políticas por recurso
- 🔗 Generador de permisos para recursos de terceros
- 👑 Rol super admin y gate interception
- 👤 Rol de usuario base opcional para paneles
- 🔄 Soporte multi-tenancy
- 🔍 Descubrimiento de entidades en todos los paneles (si está habilitado)
- 🌐 Etiquetas de permisos y entidades localizadas
- 🌱 Generación de seeders (roles + permisos directos)
- 🎨 UI intuitiva
- 🖌️ Publica y personaliza el recurso integrado
- ⚡ CLI avanzado y seguro

---

## Instalación y configuración

### 1. Instalar el paquete

```bash
composer require bezhansalleh/filament-shield
```

### 2. Configurar el proveedor de autenticación

Publica el archivo de configuración y define tu modelo de usuario:

```bash
php artisan vendor:publish --tag="filament-shield-config"
```

En `config/filament-shield.php`:

```php
return [
    // ...
    'auth_provider_model' => 'App\\Models\\User',
    // ...
];
```

Agrega el trait `HasRoles` a tu modelo de usuario:

```php
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;
}
```

### 3. Configura Shield

Ejecuta el comando de setup (interactivo e inteligente):

```bash
php artisan shield:setup
```

---

## Permisos

Puedes personalizar cómo se generan las claves de permisos en `config/filament-shield.php`:

```php
'permissions' => [
    'separator' => ':',
    'case' => 'pascal',
    'generate' => true,
],
```

_Casos soportados_: `camel`, `kebab`, `snake`, `pascal` (default), `upper_snake`

#### Personalización avanzada de claves de permiso

Puedes pasar tu propio callback a `buildPermissionKeyUsing` en el método boot de `AppServiceProvider` para controlar completamente la composición de la clave.

_Ejemplo práctico para recursos con mismo nombre pero distinto namespace:_

```php
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Filament\Resources\Resource;

FilamentShield::buildPermissionKeyUsing(
    function (string $entity, string $affix, string $subject, string $case, string $separator) {
      if (is_subclass_of($entity, Resource::class) && in_array(
            needle: $entity, 
            haystack: [
                'App\Filament\Resources\Blog\Categories\CategoryResource',
                'App\Filament\Resources\Shop\Categories\CategoryResource'
            ],
            strict: true
      )) {
          $subject = str($subject)
              ->prepend($resource::getNavigationGroup())
              ->trim()
              ->toString();
      }

      return FilamentShield::defaultPermissionKeyBuilder(
          affix: $affix, 
          separator: $separator, 
          subject: $subject, 
          case: $case
      );
  }
);
```

---

## Políticas

Shield genera automáticamente políticas para los modelos de tus recursos.

```php
'policies' => [
    'path' => app_path('Policies'),
    'merge' => true,
    'generate' => true,
    'methods' => [
        'viewAny', 'view', 'create', 'update', 'delete', 'deleteAny', 'restore',
        'forceDelete', 'forceDeleteAny', 'restoreAny', 'replicate', 'reorder',
    ],
    'single_parameter_methods' => [
        'viewAny',
        'create',
        'deleteAny',
        'forceDeleteAny',
        'restoreAny',
        'reorder',
    ],
],
```

Puedes personalizar los métodos, decidir si los métodos globales se combinan con personalizados (`merge`), y cuáles requieren sólo el usuario como parámetro (`single_parameter_methods`).

#### Registro de políticas manual

Si tus modelos no están en `App\Models`, registra las políticas manualmente:

```php
Gate::policy(Awcodes\Curator\Models\Media::class, App\Policies\MediaPolicy::class);
```

O usa:

```php
Gate::guessPolicyNamesUsing(function (string $modelClass) {
    return str_replace('Models', 'Policies', $modelClass) . 'Policy';
});
```

---

## Recursos, páginas y widgets

Configura qué métodos tendrá cada recurso y/o excluye aquellos que no requieran permisos.

```php
'resources' => [
    'subject' => 'model',
    'manage' => [
        \BezhanSalleh\FilamentShield\Resources\Roles\RoleResource::class => [
            'viewAny', 'view', 'create', 'update', 'delete',
        ],
    ],
    'exclude' => [
        //
    ],
],
```

Páginas y widgets usan por defecto el prefijo `view:` para los permisos, pero puedes personalizar sujetos y exclusiones.

---

## Permisos personalizados

Para permisos ad-hoc:

```php
'custom_permissions' => [
    'Impersonate:User' => 'Impersonar Usuario',
    'Export:Order' => 'Exportar Pedidos',
],
```

---

## Asignar roles a usuarios

Añade un Select o CheckboxList en el recurso de usuarios usando Filament Forms para relacionar roles.

Ejemplo usando Select:

```php
Forms\Components\Select::make('roles')
    ->relationship('roles', 'name')
    ->multiple()
    ->preload()
    ->searchable(),
```

---

## Métodos del plugin

Puedes personalizar la navegación, etiquetas, búsqueda global, resource padre, tenencia y layout con métodos como:

```php
FilamentShieldPlugin::make()
    ->navigationLabel('Label')
    ->navigationIcon('heroicon-o-home')
    // ...otros métodos
;
```

---

## Comandos principales

- `shield:setup [--fresh] [--tenant=] [--force] [--starred]`
- `shield:install {panel} [--tenant]`
- `shield:generate [--all] [--option=] [--resource=] [--page=] [--widget=] [--exclude] [--ignore-existing-policies] [--panel=] [--relationships]`
- `shield:super-admin [--user=] [--panel=] [--tenant=]`
- `shield:seeder [--generate] [--option=permissions_via_roles|direct_permissions] [--force]`
- `shield:publish --panel={panel} [--cluster=] [--nested] [--force]`
- `shield:translation {locale} [--panel=] [--path=]`

---

## Localización

Shield soporta múltiples idiomas, puede generar el archivo de traducción con:

```bash
php artisan shield:translation es --panel=admin
```

Eso creará `lang/es/shield-permissions.php`:

```php
return [
    'create' => 'Crear',
    'delete' => 'Eliminar',
    'delete_any' => 'Eliminar Varios',
    // ...
    'view_dashboard' => 'Tablero',
    'view_stats_overview' => 'Resumen de Estadísticas',
    'approve_posts' => 'Aprobar Publicaciones',
];
```

---

## Actualización desde v3.x o v4.x-beta

Este release es una reescritura completa y **NO** es compatible hacia atrás.

### Pasos recomendados:
1. Haz backup de tu aplicación y base de datos.
2. Borra la configuración y recursos antiguos.
3. Requiere la nueva versión:  
   `composer require bezhansalleh/filament-shield`
4. Publica nueva config y recurso si lo necesitas:
   ```bash
   php artisan vendor:publish --tag="filament-shield-config"
   php artisan shield:publish --panel=admin
   ```
5. Adapta tu config.  
6. Si usabas `HasShieldPermissions`, revisa la sección de políticas y recursos para migrar.
7. Decide si arrancar de cero (`php artisan shield:setup --fresh`) o preservar los datos (ver ejemplo en la docu).
8. Revise y pruebe a fondo después de la migración.

---

## Contribuciones y Licencia

Este plugin es de la comunidad, **no es revisado ni avalado** por el equipo oficial de Filament. Úsalo bajo tu propio riesgo.

Contribuye, consulta el [CHANGELOG](https://github.com/bezhansalleh/filament-shield/blob/main/CHANGELOG.md) y revisa la [política de seguridad](https://github.com/bezhansalleh/filament-shield/security/policy).

**Licencia MIT**  
© Bezhan Salleh y colaboradores