Aquí tienes el contenido en **Markdown** organizado y con formato claro:

---

```markdown
# Editing Records

## Loading Preview
- **Resource Edit Page**

---

## Customizing Data Before Filling the Form
Puedes modificar los datos de un registro antes de que se rellene el formulario. Para ello, define el método `mutateFormDataBeforeFill()` en la clase de la página **Edit** para modificar el array `$data` y devolver la versión modificada antes de rellenar el formulario:

```php
protected function mutateFormDataBeforeFill(array $data): array
{
    $data['user_id'] = auth()->id();

    return $data;
}
```
> **Nota:** Si estás editando registros en una acción modal, consulta la documentación de [Actions](#).

---

## Customizing Data Before Saving
Si deseas modificar los datos del formulario antes de guardarlos en la base de datos, define el método `mutateFormDataBeforeSave()` en la clase **Edit**:

```php
protected function mutateFormDataBeforeSave(array $data): array
{
    $data['last_edited_by_id'] = auth()->id();

    return $data;
}
```
> **Nota:** Si estás editando registros en una acción modal, consulta la documentación de [Actions](#).

---

## Customizing the Saving Process
Puedes personalizar cómo se actualiza el registro utilizando el método `handleRecordUpdate()` en la clase **Edit**:

```php
use Illuminate\Database\Eloquent\Model;

protected function handleRecordUpdate(Model $record, array $data): Model
{
    $record->update($data);

    return $record;
}
```
> **Nota:** Si estás editando registros en una acción modal, consulta la documentación de [Actions](#).

---

## Customizing Redirects
Por defecto, guardar el formulario **no redirige** al usuario a otra página. Puedes configurar una redirección personalizada al guardar el formulario sobrescribiendo el método `getRedirectUrl()` en la clase **Edit**:

### Ejemplos de redirección:
- **Redirigir a la página de lista (index):**
  ```php
  protected function getRedirectUrl(): string
  {
      return $this->getResource()::getUrl('index');
  }
  ```

- **Redirigir a la página de visualización (view):**
  ```php
  protected function getRedirectUrl(): string
  {
      return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
  }
  ```

- **Redirigir a la página anterior o a la página de lista:**
  ```php
  protected function getRedirectUrl(): string
  {
      return $this->previousUrl ?? $this->getResource()::getUrl('index');
  }
  ```

### Configuración global para todos los recursos:
Puedes usar la configuración del panel para personalizar la página de redirección por defecto para todos los recursos:
```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->resourceEditPageRedirect('index') // o
        ->resourceEditPageRedirect('view');
}
```

---

## Customizing the Save Notification
Cuando un registro se actualiza correctamente, se envía una notificación al usuario. Puedes personalizar el título de esta notificación definiendo el método `getSavedNotificationTitle()` en la clase **Edit**:

```php
protected function getSavedNotificationTitle(): ?string
{
    return 'User updated';
}
```

### Personalizar toda la notificación:
Sobrescribe el método `getSavedNotification()` para personalizar la notificación completa:
```php
use Filament\Notifications\Notification;

protected function getSavedNotification(): ?Notification
{
    return Notification::make()
        ->success()
        ->title('User updated')
        ->body('The user has been saved successfully.');
}
```

### Desactivar la notificación:
Para desactivar la notificación, devuelve `null` en el método `getSavedNotification()`:
```php
use Filament\Notifications\Notification;

protected function getSavedNotification(): ?Notification
{
    return null;
}
```
> **Nota:** Si estás editando registros en una acción modal, consulta la documentación de [Actions](#).

---

## Lifecycle Hooks
Los **hooks** permiten ejecutar código en diferentes puntos del ciclo de vida de una página, como antes de guardar un formulario. Para configurar un hook, crea un método protegido en la clase **Edit** con el nombre del hook:

```php
protected function beforeSave(): void
{
    // Ejecuta código antes de guardar los datos en la base de datos.
}
```

### Hooks disponibles para las páginas **Edit**:
```php
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    // ...

    protected function beforeFill(): void
    {
        // Se ejecuta antes de que los campos del formulario se rellenen desde la base de datos.
    }

    protected function afterFill(): void
    {
        // Se ejecuta después de que los campos del formulario se rellenen desde la base de datos.
    }

    protected function beforeValidate(): void
    {
        // Se ejecuta antes de validar los campos del formulario al guardar.
    }

    protected function afterValidate(): void
    {
        // Se ejecuta después de validar los campos del formulario al guardar.
    }

    protected function beforeSave(): void
    {
        // Se ejecuta antes de guardar los campos del formulario en la base de datos.
    }

    protected function afterSave(): void
    {
        // Se ejecuta después de guardar los campos del formulario en la base de datos.
    }
}
```
> **Nota:** Si estás editando registros en una acción modal, consulta la documentación de [Actions](#).

---

## Saving a Part of the Form Independently
Puedes permitir que el usuario guarde una parte del formulario de forma independiente. Una forma de hacerlo es con una **acción de sección** en el encabezado o pie de página. Desde el método `action()`, puedes llamar a `saveFormComponentOnly()`, pasando el componente **Section** que deseas guardar:

```php
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;

Section::make('Rate limiting')
    ->schema([
        // ...
    ])
    ->footerActions([
        fn (string $operation): Action => Action::make('save')
            ->action(function (Section $component, EditRecord $livewire) {
                $livewire->saveFormComponentOnly($component);

                Notification::make()
                    ->title('Rate limiting saved')
                    ->body('The rate limiting settings have been saved successfully.')
                    ->success()
                    ->send();
            })
            ->visible($operation === 'edit'),
    ])
```
> **Nota:** El helper `$operation` está disponible para asegurarte de que la acción solo sea visible cuando el formulario esté siendo editado.

---

## Halting the Saving Process
En cualquier momento, puedes llamar a `$this->halt()` dentro de un **hook de ciclo de vida** o método de mutación para detener todo el proceso de guardado:

```php
use Filament\Actions\Action;
use Filament\Notifications\Notification;

protected function beforeSave(): void
{
    if (! $this->getRecord()->team->subscribed()) {
        Notification::make()
            ->warning()
            ->title('You don\'t have an active subscription!')
            ->body('Choose a plan to continue.')
            ->persistent()
            ->actions([
                Action::make('subscribe')
                    ->button()
                    ->url(route('subscribe'), shouldOpenInNewTab: true),
            ])
            ->send();

        $this->halt();
    }
}
```
> **Nota:** Si estás editando registros en una acción modal, consulta la documentación de [Actions](#).

---

## Authorization
Filament observará cualquier política de modelo registrada en tu aplicación:
- Los usuarios podrán acceder a la página **Edit** si el método `update()` de la política del modelo devuelve `true`.
- También podrán eliminar el registro si el método `delete()` de la política devuelve `true`.

---

## Custom Actions
Las **acciones** son botones que se muestran en las páginas y permiten al usuario ejecutar un método de Livewire o visitar una URL. En las páginas de recursos, las acciones suelen estar en dos lugares: en la parte superior derecha de la página y debajo del formulario.

### Ejemplo: Añadir un botón de acción personalizado junto a "Delete" en la página **Edit**:
```php
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    // ...

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('impersonate')
                ->action(function (): void {
                    // ...
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
```

### Ejemplo: Añadir un botón junto a "Save" debajo del formulario:
```php
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    // ...

    protected function getFormActions(): array
    {
        return [
            ...parent::getFormActions(),
            Action::make('close')->action('saveAndClose'),
        ];
    }

    public function saveAndClose(): void
    {
        // ...
    }
}
```
> **Nota:** Para ver toda la API de acciones, visita la sección de [páginas](#).

---

## Adding a Save Action Button to the Header
El botón **"Save"** puede añadirse al encabezado de la página sobrescribiendo el método `getHeaderActions()` y usando `getSaveFormAction()`. Debes pasar `formId()` a la acción para especificar que la acción debe enviar el formulario con el ID de `form`:

```php
protected function getHeaderActions(): array
{
    return [
        $this->getSaveFormAction()
            ->formId('form'),
    ];
}
```

### Eliminar todas las acciones del formulario:
Sobrescribe el método `getFormActions()` para devolver un array vacío:
```php
protected function getFormActions(): array
{
    return [];
}
```

---

## Creating Another Edit Page
Una página **Edit** puede no ser suficiente para permitir a los usuarios navegar por muchos campos de formulario. Puedes crear tantas páginas **Edit** para un recurso como desees. Esto es especialmente útil si estás usando la sub-navegación de recursos.

### Pasos para crear una página **Edit**:
1. Usa el comando `make:filament-page`:
   ```bash
   php artisan make:filament-page EditCustomerContact --resource=CustomerResource --type=EditRecord
   ```

2. Registra la nueva página en el método `getPages()` de tu recurso:
   ```php
   public static function getPages(): array
   {
       return [
           'index' => Pages\ListCustomers::route('/'),
           'create' => Pages\CreateCustomer::route('/create'),
           'view' => Pages\ViewCustomer::route('/{record}'),
           'edit' => Pages\EditCustomer::route('/{record}/edit'),
           'edit-contact' => Pages\EditCustomerContact::route('/{record}/edit/contact'),
       ];
   }
   ```

3. Define el formulario para esta página:
   ```php
   use Filament\Schemas\Schema;

   public function form(Schema $schema): Schema
   {
       return $schema
           ->components([
               // ...
           ]);
   }
   ```

---

## Adding Edit Pages to Resource Sub-Navigation
Si estás usando la sub-navegación de recursos, puedes registrar esta página normalmente en `getRecordSubNavigation()` del recurso:

```php
use App\Filament\Resources\Customers\Pages;
use Filament\Resources\Pages\Page;

public static function getRecordSubNavigation(Page $page): array
{
    return $page->generateNavigationItems([
        // ...
        Pages\EditCustomerContact::class,
    ]);
}
```

---

## Custom Page Content
Cada página en Filament tiene su propio esquema, que define la estructura y el contenido general. Puedes sobrescribir el esquema de la página definiendo un método `content()` en ella. El método `content()` para la página **Edit** contiene los siguientes componentes por defecto:

```php
use Filament\Schemas\Schema;

public function content(Schema $schema): Schema
{
    return $schema
        ->components([
            $this->getFormContentComponent(), // Muestra el formulario definido en este recurso.
            $this->getRelationManagersContentComponent(), // Muestra los gestores de relaciones definidos en este recurso.
        ]);
}
```
> **Nota:** Puedes insertar cualquier componente de esquema dentro del array `components()`, reordenarlos o eliminar los que no necesites.

---

## Using a Custom Blade View
Para más oportunidades de personalización, puedes sobrescribir la propiedad estática `$view` en la clase de la página con una vista personalizada en tu aplicación:

```php
protected string $view = 'filament.resources.users.pages.edit-user';
```
Esto asume que has creado una vista en:
`resources/views/filament/resources/users/pages/edit-user.blade.php`

### Ejemplo de vista personalizada:
```blade
<x-filament-panels::page>
    {{-- `$this->getRecord()` devolverá el registro Eloquent actual para esta página --}}

    {{ $this->content }} {{-- Esto renderizará el contenido de la página definido en el método `content()`, que puede eliminarse si deseas empezar desde cero --}}
</x-filament-panels::page>
```
```