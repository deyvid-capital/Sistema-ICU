
---

```markdown
# Modales

---

## Introducción
Las acciones pueden requerir confirmación adicional o entrada por parte del usuario antes de ejecutarse. Puedes abrir un **modal** antes de que se ejecute una acción para manejar esto.

---

## Modales de confirmación
Puedes necesitar confirmación antes de ejecutar una acción usando el método `requiresConfirmation()`. Esto es útil para acciones destructivas, como eliminar registros.

### Ejemplo básico:
```php
use App\Models\Post;
use Filament\Actions\Action;

Action::make('delete')
    ->action(fn (Post $record) => $record->delete())
    ->requiresConfirmation();
```
**Nota:**
- El modal de confirmación **no está disponible** si usas `url()` en lugar de `action()`.
- Los modales de confirmación usan el rol ARIA `alertdialog` (no `dialog`), por lo que los lectores de pantalla los anuncian como alertas y leen automáticamente la descripción del modal al abrirse.

---

## Control del contenido del modal

---

### Personalización del encabezado, descripción y etiqueta de acción
Puedes personalizar el encabezado, descripción y etiqueta del botón de envío en el modal:

```php
use App\Models\Post;
use Filament\Actions\Action;

Action::make('delete')
    ->action(fn (Post $record) => $record->delete())
    ->requiresConfirmation()
    ->modalHeading('Delete post')
    ->modalDescription('Are you sure you\'d like to delete this post? This cannot be undone.')
    ->modalSubmitActionLabel('Yes, delete it');
```

---

### Renderizado de un esquema en el modal
Filament permite renderizar un **esquema** en el modal, lo que te permite usar cualquier componente disponible para construir una interfaz de usuario. Útil para formularios que recopilen información adicional antes de ejecutar la acción.

#### Ejemplo con `Grid` y `Section`:
```php
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;

Action::make('viewUser')
    ->schema([
        Grid::make(2)
            ->schema([
                Section::make('Details')
                    ->schema([
                        TextInput::make('name'),
                        Select::make('position')
                            ->options([
                                'developer' => 'Developer',
                                'designer' => 'Designer',
                            ]),
                        Checkbox::make('is_admin'),
                    ]),
                Section::make('Auditing')
                    ->schema([
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->dateTime(),
                    ]),
            ]),
    ]);
```
**Nota:** El método `schema()` también acepta una **función** para calcular el valor dinámicamente.

---

### Renderizado de un formulario en el modal
Puedes usar campos de formulario para crear modales con acciones. Los datos del formulario están disponibles en el array `$data` del cierre `action()`.

#### Ejemplo:
```php
use App\Models\Post;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;

Action::make('updateAuthor')
    ->schema([
        Select::make('authorId')
            ->label('Author')
            ->options(User::query()->pluck('name', 'id'))
            ->required(),
    ])
    ->action(function (array $data, Post $record): void {
        $record->author()->associate($data['authorId']);
        $record->save();
    });
```

#### Rellenar el formulario con datos existentes
Usa el método `fillForm()` para prellenar el formulario:
```php
Action::make('updateAuthor')
    ->fillForm(fn (Post $record): array => [
        'authorId' => $record->author->id,
    ])
    ->schema([
        Select::make('authorId')
            ->label('Author')
            ->options(User::query()->pluck('name', 'id'))
            ->required(),
    ])
    ->action(function (array $data, Post $record): void {
        $record->author()->associate($data['authorId']);
        $record->save();
    });
```

---

### Deshabilitar todos los campos del formulario
Usa `disabledForm()` para desactivar todos los campos del formulario en el modal:
```php
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

Action::make('approvePost')
    ->schema([
        TextInput::make('title'),
        Textarea::make('content'),
    ])
    ->disabledForm()
    ->action(function (Post $record): void {
        $record->approve();
    });
```

---

### Renderizado de un asistente (Wizard) en el modal
Puedes crear un **asistente de formularios multipaso** dentro de un modal usando `steps()` en lugar de `schema()`:
```php
use Filament\Actions\Action;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Wizard\Step;

Action::make('create')
    ->steps([
        Step::make('Name')
            ->description('Give the category a unique name')
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                TextInput::make('slug')
                    ->disabled()
                    ->required()
                    ->unique(Category::class, 'slug'),
            ])
            ->columns(2),
        Step::make('Description')
            ->description('Add some extra details')
            ->schema([
                MarkdownEditor::make('description'),
            ]),
        Step::make('Visibility')
            ->description('Control who can view it')
            ->schema([
                Toggle::make('is_visible')
                    ->label('Visible to customers.')
                    ->default(true),
            ]),
    ]);
```

---

## Personalización visual del modal

---

### Añadir un icono al modal
Usa `modalIcon()` para añadir un icono:
```php
Action::make('delete')
    ->action(fn (Post $record) => $record->delete())
    ->requiresConfirmation()
    ->modalIcon('heroicon-o-trash');
```

#### Personalizar el color del icono
Usa `modalIconColor()`:
```php
Action::make('delete')
    ->action(fn (Post $record) => $record->delete())
    ->requiresConfirmation()
    ->color('danger')
    ->modalIcon('heroicon-o-trash')
    ->modalIconColor('warning');
```

---

### Personalizar la alineación del contenido
Usa `modalAlignment()` para cambiar la alineación del contenido (`Alignment::Start` o `Alignment::Center`):
```php
use Filament\Support\Enums\Alignment;

Action::make('updateAuthor')
    ->modalAlignment(Alignment::Center);
```

---

### Hacer que el encabezado o pie de página sean pegajosos
- **Encabezado pegajoso**: `stickyModalHeader()`
- **Pie de página pegajoso**: `stickyModalFooter()`

```php
Action::make('updateAuthor')
    ->stickyModalHeader()
    ->stickyModalFooter();
```

---

## Contenido modal personalizado

---

### Definir contenido personalizado
Usa `modalContent()` para renderizar una vista Blade:
```php
Action::make('advance')
    ->action(fn (Post $record) => $record->advance())
    ->modalContent(view('filament.pages.actions.advance'));
```

#### Pasar datos a la vista:
```php
Action::make('advance')
    ->modalContent(fn (Post $record): View => view(
        'filament.pages.actions.advance',
        ['record' => $record],
    ));
```

#### Añadir contenido debajo del formulario:
Usa `modalContentFooter()`:
```php
Action::make('advance')
    ->modalContentFooter(view('filament.pages.actions.advance'));
```

---

### Añadir acciones adicionales al pie del modal
Usa `extraModalFooterActions()` para añadir botones adicionales:
```php
Action::make('create')
    ->extraModalFooterActions(fn (Action $action): array => [
        $action->makeModalSubmitAction('createAnother', arguments: ['another' => true]),
    ])
    ->action(function (array $data, array $arguments): void {
        if ($arguments['another'] ?? false) {
            // Reiniciar el formulario y no cerrar el modal
        }
    });
```

#### Abrir otro modal desde una acción adicional:
```php
Action::make('edit')
    ->extraModalFooterActions([
        Action::make('delete')
            ->requiresConfirmation()
            ->action(function () { /* ... */ })
            ->cancelParentActions(), // Cancela la acción padre si se ejecuta
    ]);
```

---

## Comportamiento del modal

---

### Usar un deslizamiento (`slideOver`) en lugar de un modal
```php
Action::make('updateAuthor')
    ->slideOver();
```
**Nota:** El contenido se desliza desde la derecha y ocupa toda la altura del navegador.

#### Cambiar la posición del deslizamiento:
```php
use Filament\Support\Enums\SlideOverPosition;

Action::make('updateAuthor')
    ->slideOver()
    ->slideOverPosition(SlideOverPosition::Start); // Desde la izquierda
```

---

### Cambiar el ancho del modal
Usa `modalWidth()` con las opciones de `Width`:
```php
use Filament\Support\Enums\Width;

Action::make('updateAuthor')
    ->modalWidth(Width::FiveExtraLarge);
```

---

### Ejecutar código al abrir el modal
Usa `mountUsing()`:
```php
Action::make('create')
    ->mountUsing(function (Schema $form) {
        $form->fill();
    });
```

---

### Personalizar botones del pie del modal
- **Modificar el botón de envío o cancelar**:
  ```php
  Action::make('help')
      ->modalCancelAction(fn (Action $action) => $action->label('Close'));
  ```
- **Eliminar un botón**:
  ```php
  Action::make('help')
      ->modalSubmitAction(false); // Elimina el botón de envío
  ```
- **Añadir botones adicionales**:
  ```php
  Action::make('create')
      ->extraModalFooterActions([ /* ... */ ]);
  ```

---

### Cierre del modal
- **Desactivar cierre al hacer clic fuera**:
  ```php
  Action::make('updateAuthor')
      ->closeModalByClickingAway(false);
  ```
- **Desactivar cierre con la tecla `Escape`**:
  ```php
  Action::make('updateAuthor')
      ->closeModalByEscaping(false);
  ```
- **Ocultar el botón de cierre**:
  ```php
  Action::make('updateAuthor')
      ->modalCloseButton(false);
  ```
- **Hacer el modal "click-through"** (permitir interacción con la página detrás):
  ```php
  Action::make('updateAuthor')
      ->modalClickThrough();
  ```
- **Desactivar enfoque automático**:
  ```php
  Action::make('updateAuthor')
      ->modalAutofocus(false);
  ```

---

### Superponer modales hijos sobre modales padres
Usa `overlayParentActions()` para que el modal hijo aparezca encima del padre:
```php
Action::make('editItems')
    ->slideOver()
    ->schema([ /* ... */ ])
    ->deleteAction(
        fn (Action $action) => $action
            ->requiresConfirmation()
            ->overlayParentActions(),
    );
```

#### Cancelar acciones padres al cerrar el modal hijo:
```php
Action::make('createPost')
    ->extraModalFooterActions([
        Action::make('saveAsDraft')
            ->cancelParentActionsOnClose()
            ->action(function () { /* ... */ }),
    ]);
```

---

## Optimización y configuración avanzada

---

### Forzar la existencia del modal
Usa `modal()` para indicar a Filament que el modal existe sin necesidad de comprobaciones adicionales:
```php
Action::make('updateAuthor')
    ->modal();
```

---

### Ocultar el modal condicionalmente
Usa `modalHidden()`:
```php
Action::make('create')
    ->modalHidden($this->role !== 'admin')
    ->modalContent(view('filament.pages.actions.create'));
```

---

### Añadir atributos HTML adicionales
- **Atributos para la ventana modal**:
  ```php
  Action::make('updateAuthor')
      ->extraModalWindowAttributes(['class' => 'update-author-modal']);
  ```
- **Atributos para la superposición**:
  ```php
  Action::make('updateAuthor')
      ->extraModalOverlayAttributes(['class' => 'update-author-overlay']);
  ```
**Nota:** Usa `merge: true` para fusionar atributos en lugar de sobrescribirlos.

---

## Inyección de dependencias en métodos
Los métodos como `schema()`, `fillForm()`, `modalIcon()`, etc., aceptan **funciones** donde puedes inyectar utilidades como:
- `$action`: Instancia actual de la acción.
- `$arguments`: Argumentos pasados a la acción.
- `$data`: Datos enviados desde el formulario.
- `$livewire`: Instancia del componente Livewire.
- `$record`: Registro Eloquent asociado.
- `$mountedActions`: Array de acciones montadas actualmente.
- `$schema`, `$schemaComponent`, `$schemaState`, etc. (solo para acciones en esquemas).

#### Ejemplo de acceso a datos de acciones padre:
```php
Action::make('second')
    ->action(function (array $mountedActions) {
        dd($mountedActions[0]->getRawData()); // Datos del padre
    });
```

---
```

