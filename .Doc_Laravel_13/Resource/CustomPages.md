# Recursos  
## Páginas personalizadas de recursos

### Introducción

Filament permite crear páginas completamente personalizadas para tus recursos. Para crear una nueva página, puedes usar el siguiente comando:

```bash
php artisan make:filament-page SortUsers --resource=UserResource --type=custom
```

Este comando creará dos archivos:  
- Una clase de página en el directorio `/Pages` de tu recurso  
- Una vista en el directorio `/pages` del directorio de vistas del recurso

Debes registrar las páginas personalizadas asociándolas a una ruta en el método estático `getPages()` de tu recurso:

```php
public static function getPages(): array
{
    return [
        // ...
        'sort' => Pages\SortUsers::route('/sort'),
    ];
}
```

El orden en que registres las páginas en este método es importante: cualquier segmento de ruta comodín que definas antes que rutas con nombre fijo será tomado por el router de Laravel primero.

Todos los parámetros definidos en la ruta estarán disponibles en la clase de la página, de forma idéntica a como sucede con Livewire.

---

### Usando un registro de recurso

Si deseas crear una página que trabaje con un registro (record), similar a las páginas de Edición o Vista, puedes usar el trait `InteractsWithRecord`:

```php
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;

class ManageUser extends Page
{
    use InteractsWithRecord;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    // ...
}
```

El método `mount()` debe resolver el registro a partir de la URL y almacenarlo en `$this->record`. Puedes acceder al registro en cualquier momento usando `$this->getRecord()` tanto en la clase como en la vista.

Para agregar el registro como un parámetro en la ruta, debes definir `{record}` en el método `getPages()`:

```php
public static function getPages(): array
{
    return [
        // ...
        'manage' => Pages\ManageUser::route('/{record}/manage'),
    ];
}
```