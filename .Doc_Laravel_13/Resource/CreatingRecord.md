Aquí tienes el contenido en formato **Markdown** con la extensión `.md` para que puedas guardarlo directamente en un archivo:

---

```markdown
# Creating Records

---

## Resource Create Page

---

## Customizing Data Before Saving
Puedes modificar los datos del formulario antes de guardarlos en la base de datos. Para ello, define el método `mutateFormDataBeforeCreate()` en la clase **Create**, que acepta el array `$data` y devuelve la versión modificada:

```php
protected function mutateFormDataBeforeCreate(array $data): array
{
    $data['user_id'] = auth()->id();

    return $data;
}
```
> **Nota:** Si estás creando registros en una acción modal, consulta la documentación de [Actions](#).

---

## Customizing the Creation Process
Puedes personalizar cómo se crea el registro utilizando el método `handleRecordCreation()` en la clase **Create**:

```php
use Illuminate\Database\Eloquent\Model;

protected function handleRecordCreation(array $data): Model
{
    return static::getModel()::create($data);
}
```
> **Nota:** Si estás creando registros en una acción modal, consulta la documentación de [Actions](#).

---

## Customizing Redirects
Por defecto, después de guardar el formulario, el usuario será redirigido a la página **Edit** del recurso, o a la página **View** si está presente.

Puedes configurar una redirección personalizada al guardar el formulario sobrescribiendo el método `getRedirectUrl()` en la clase **Create**:

### Ejemplos de redirección:
- **Redirigir a la página de lista (index):**
  ```php
  protected function getRedirectUrl(): string
  {
      return $this->getResource()::getUrl('index');
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
        ->resourceCreatePageRedirect('index') // o
        ->resourceCreatePageRedirect('view') // o
        ->resourceCreatePageRedirect('edit');
}
```

---

## Customizing the Save Notification
Cuando un registro se crea correctamente, se envía una notificación al usuario. Puedes personalizar el título de esta notificación definiendo el método `getCreatedNotificationTitle()` en la clase **Create**:

```php
protected function getCreatedNotificationTitle(): ?string
{
    return 'User registered';
}
```

### Personalizar toda la notificación:
Sobrescribe el método `getCreatedNotification()` para personalizar la notificación completa:
```php
use Filament\Notifications\Notification;

protected function getCreatedNotification(): ?Notification
{
    return Notification::make()
        ->success()
        ->title('User registered')
        ->body('The user has been created successfully.');
}
```

### Desactivar la notificación:
Para desactivar la notificación, devuelve `null` en el método `getCreatedNotification()`:
```php
use Filament\Notifications\Notification;

protected function getCreatedNotification(): ?Notification
{
    return null;
}
```
> **Nota:** Si estás creando registros en una acción modal, consulta la documentación de [Actions](#).

---

## Creating Another Record

---

## Disabling "Create Another"
Para desactivar la función **"Crear y crear otro"**, define la propiedad `$canCreateAnother` como `false` en la clase **Create**:

```php
protected static bool $canCreateAnother = false;
```

O, si prefieres especificar una condición dinámica, sobrescribe el método `canCreateAnother()`:
```php
public function canCreateAnother(): bool
{
    return false;
}
```

---

## Preserving Data When Creating Another
Por defecto, cuando el usuario utiliza la función **"Crear y crear otro"**, todos los datos del formulario se borran. Si deseas preservar algunos datos, sobrescribe el método `preserveFormDataWhenCreatingAnother()` y devuelve la parte del array `$data` que deseas mantener:

```php
use Illuminate\Support\Arr;

protected function preserveFormDataWhenCreatingAnother(array $data): array
{
    return Arr::only($data, ['is_admin', 'organization']);
}
```

Para preservar **todos los datos**, devuelve el array `$data` completo:
```php
protected function preserveFormDataWhenCreatingAnother(array $data): array
{
    return $data;
}
```

---

## Lifecycle Hooks
Los **hooks** permiten ejecutar código en diferentes puntos del ciclo de vida de una página, como antes de guardar un formulario. Para configurar un hook, crea un método protegido en la clase **Create** con el nombre del hook:

```php
protected function beforeCreate(): void
{
    // Ejecuta código antes de guardar los datos en la base de datos.
}
```

### Hooks disponibles para la página **Create**:
```php
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    // ...

    protected function beforeFill(): void
    {
        // Se ejecuta antes de que los campos del formulario se rellenen con sus valores por defecto.
    }

    protected function afterFill(): void
    {
        // Se ejecuta después de que los campos del formulario se rellenen con sus valores por defecto.
    }

    protected function beforeValidate(): void
    {
        // Se ejecuta antes de validar los campos del formulario al enviar el formulario.
    }

    protected function afterValidate(): void
    {
        // Se ejecuta después de validar los campos del formulario al enviar el formulario.
    }

    protected function beforeCreate(): void
    {
        // Se ejecuta antes de guardar los campos del formulario en la base de datos.
    }

    protected function afterCreate(): void
    {
        // Se ejecuta después de guardar los campos del formulario en la base de datos.
    }
}
```
> **Nota:** Si estás creando registros en una acción modal, consulta la documentación de [Actions](#).

---

## Halting the Creation Process
En cualquier momento, puedes llamar a `$this->halt()` dentro de un **hook de ciclo de vida** o método de mutación para detener todo el proceso de creación:

```php
use Filament\Actions\Action;
use Filament\Notifications\Notification;

protected function beforeCreate(): void
{
    if (! auth()->user()->team->subscribed()) {
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
> **Nota:** Si estás creando registros en una acción modal, consulta la documentación de [Actions](#).

---

## Authorization
Filament observará cualquier política de modelo registrada en tu aplicación:
- Los usuarios podrán acceder a la página **Create** si el método `create()` de la política del modelo devuelve `true`.

---

## Using a Wizard
Puedes transformar fácilmente el proceso de creación en un **asistente de varios pasos**. En la clase de la página, añade el trait `HasWizard`:

```php
use App\Filament\Resources\Categories\CategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCategory extends CreateRecord
{
    use CreateRecord\Concerns\HasWizard;

    protected static string $resource = CategoryResource::class;

    protected function getSteps(): array
    {
        return [
            // ...
        ];
    }
}
```

### Definir los pasos del asistente:
Dentro del array `getSteps()`, devuelve los pasos del asistente:

```php
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Wizard\Step;

protected function getSteps(): array
{
    return [
        Step::make('Name')
            ->description('Give the category a clear and unique name')
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                TextInput::make('slug')
                    ->disabled()
                    ->required()
                    ->unique(Category::class, 'slug', fn ($record) => $record),
            ]),
        Step::make('Description')
            ->description('Add some extra details')
            ->schema([
                MarkdownEditor::make('description')
                    ->columnSpan('full'),
            ]),
        Step::make('Visibility')
            ->description('Control who can view it')
            ->schema([
                Toggle::make('is_visible')
                    ->label('Visible to customers.')
                    ->default(true),
            ]),
    ];
}
```

### Permitir navegación libre:
Si deseas permitir la navegación libre (que todos los pasos sean omitibles), sobrescribe el método `hasSkippableSteps()`:
```php
public function hasSkippableSteps(): bool
{
    return true;
}
```
> **Nota:** Si estás creando registros en una acción modal, consulta la documentación de [Actions](#).

---

## Sharing Fields Between the Form Schema and Wizards
Para reducir la repetición entre el formulario del recurso y los pasos del asistente, es una buena idea extraer funciones públicas estáticas para los campos del formulario. Así podrás recuperar fácilmente una instancia de un campo del esquema del formulario o del asistente:

```php
use Filament\Forms;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                static::getNameFormField(),
                static::getSlugFormField(),
                // ...
            ]);
    }

    public static function getNameFormField(): Forms\Components\TextInput
    {
        return TextInput::make('name')
            ->required()
            ->live()
            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state)));
    }

    public static function getSlugFormField(): Forms\Components\TextInput
    {
        return TextInput::make('slug')
            ->disabled()
            ->required()
            ->unique(Category::class, 'slug', fn ($record) => $record);
    }
}
```

### Usar los campos en el asistente:
```php
use App\Filament\Resources\Categories\Schemas\CategoryForm;
use Filament\Resources\Pages\CreateRecord;

class CreateCategory extends CreateRecord
{
    use CreateRecord\Concerns\HasWizard;

    protected static string $resource = CategoryResource::class;

    protected function getSteps(): array
    {
        return [
            Step::make('Name')
                ->description('Give the category a clear and unique name')
                ->schema([
                    CategoryForm::getNameFormField(),
                    CategoryForm::getSlugFormField(),
                ]),
            // ...
        ];
    }
}
```

---

## Importing Resource Records
Filament incluye una acción `ImportAction` que puedes añadir a `getHeaderActions()` en la página **List**. Permite a los usuarios subir un archivo CSV para importar datos al recurso:

```php
use App\Filament\Imports\ProductImporter;
use Filament\Actions;

protected function getHeaderActions(): array
{
    return [
        Actions\ImportAction::make()
            ->importer(ProductImporter::class),
        Actions\CreateAction::make(),
    ];
}
```
> **Nota:** La clase **"importer"** debe crearse para indicar a Filament cómo importar cada fila del CSV. Puedes aprender todo sobre `ImportAction` en la documentación de [Actions](#).

---

## Custom Actions
Las **acciones** son botones que se muestran en las páginas y permiten al usuario ejecutar un método de Livewire o visitar una URL. En las páginas de recursos, las acciones suelen estar en dos lugares: en la parte superior derecha de la página y debajo del formulario.

### Ejemplo: Añadir una nueva acción de botón en el encabezado de la página **Create**:
```php
use App\Filament\Imports\UserImporter;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    // ...

    protected function getHeaderActions(): array
    {
        return [
            Actions\ImportAction::make()
                ->importer(UserImporter::class),
        ];
    }
}
```

### Ejemplo: Añadir un nuevo botón junto a **"Create"** debajo del formulario:
```php
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    // ...

    protected function getFormActions(): array
    {
        return [
            ...parent::getFormActions(),
            Action::make('close')->action('createAndClose'),
        ];
    }

    public function createAndClose(): void
    {
        // ...
    }
}
```
> **Nota:** Para ver toda la API de acciones, visita la sección de [páginas](#).

---

## Adding a Create Action Button to the Header
El botón **"Create"** puede moverse al encabezado de la página sobrescribiendo el método `getHeaderActions()` y usando `getCreateFormAction()`. Debes pasar `formId()` a la acción para especificar que la acción debe enviar el formulario con el ID de `form`:

```php
protected function getHeaderActions(): array
{
    return [
        $this->getCreateFormAction()
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

## Custom Page Content
Cada página en Filament tiene su propio esquema, que define la estructura y el contenido general. Puedes sobrescribir el esquema de la página definiendo un método `content()` en ella. El método `content()` para la página **Create** contiene los siguientes componentes por defecto:

```php
use Filament\Schemas\Schema;

public function content(Schema $schema): Schema
{
    return $schema
        ->components([
            $this->getFormContentComponent(), // Este método devuelve un componente para mostrar el formulario definido en este recurso.
        ]);
}
```
> **Nota:** Puedes insertar cualquier componente de esquema dentro del array `components()`, reordenarlos o eliminar los que no necesites.

---

## Using a Custom Blade View
Para más oportunidades de personalización, puedes sobrescribir la propiedad estática `$view` en la clase de la página con una vista personalizada en tu aplicación:

```php
protected string $view = 'filament.resources.users.pages.create-user';
```
Esto asume que has creado una vista en:
`resources/views/filament/resources/users/pages/create-user.blade.php`

### Ejemplo de vista personalizada:
```blade
<x-filament-panels::page>
    {{ $this->content }} {{-- Esto renderizará el contenido de la página definido en el método `content()`, que puede eliminarse si deseas empezar desde cero --}}
</x-filament-panels::page>
```
```