[Saltar al contenido principal](https://filamentphp.com/docs/5.x/actions/modals#content-area)## ​Introducción

Las acciones pueden requerir confirmación adicional o entrada por parte del usuario antes de ejecutarse. Puedes abrir un modal antes de que se ejecute una acción para hacer esto.## ​Modales de confirmación

```
use App\Models\Post;
use Filament\Actions\Action;

Action::make('delete')
    ->action(fn (Post $record) => $record->delete())
    ->requiresConfirmation()

```

Vista previa de carga![Confirmation modal](./Modales - Filamento_files/confirmation.jpg)

![Modal de confirmación](./Modales - Filamento_files/confirmation(1).jpg)

## ​Control del contenido modal



### ​Personalización del encabezado, descripción y etiqueta de acción de envío del modal

Puedes personalizar el encabezado, la descripción y la etiqueta del botón de enviar en el modal:```
use App\Models\Post;
use Filament\Actions\Action;

Action::make('delete')
    ->action(fn (Post $record) => $record->delete())
    ->requiresConfirmation()
    ->modalHeading('Delete post')
    ->modalDescription('Are you sure youd like to delete this post? This cannot be undone.')
    ->modalSubmitActionLabel('Yes, delete it')

```

Vista previa de carga![Confirmation modal with custom text](./Modales - Filamento_files/confirmation-custom-text.jpg)

![Modal de confirmación con texto personalizado](./Modales - Filamento_files/confirmation-custom-text(1).jpg)

### ​Renderizado de un esquema en modal

```

use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;

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
    ])

```

Vista previa de carga![Modal with schema layout](./Modales - Filamento_files/schema.jpg)

![Modal con disposición de esquemas](./Modales - Filamento_files/schema(1).jpg)

#### ​Renderización de una forma en modal

```

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
    })

```

Vista previa de carga![Modal with form](./Modales - Filamento_files/form.jpg)

![Modal con forma](./Modales - Filamento_files/form(1).jpg)

##### Relleno del formulario con datos existentes

```

use App\Models\Post;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;

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
    })

```

##### Deshabilitando todos los campos de forma

```

use App\Models\Post;
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
    })

```

Vista previa de carga![Modal with disabled form fields](./Modales - Filamento_files/disabled-form.jpg)

![Modal con campos de forma deshabilitados](./Modales - Filamento_files/disabled-form(1).jpg)

#### ​Renderizado de un asistente en modal

```

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
    ])

```

Vista previa de carga![Modal with wizard](./Modales - Filamento_files/wizard.jpg)

![Modal con asistente](./Modales - Filamento_files/wizard(1).jpg)

### ​Añadir un icono dentro del modal

```

use App\Models\Post;
use Filament\Actions\Action;

Action::make('delete')
    ->action(fn (Post $record) => $record->delete())
    ->requiresConfirmation()
    ->modalIcon('heroicon-o-trash')

```

Vista previa de carga![Confirmation modal with icon](./Modales - Filamento_files/icon.jpg)

![Modal de confirmación con icono](./Modales - Filamento_files/icon(1).jpg)

```

use App\Models\Post;
use Filament\Actions\Action;

Action::make('delete')
    ->action(fn (Post $record) => $record->delete())
    ->requiresConfirmation()
    ->color('danger')
    ->modalIcon('heroicon-o-trash')
    ->modalIconColor('warning')

```

Vista previa de carga![Confirmation modal with custom icon color](./Modales - Filamento_files/icon-color.jpg)

![Modal de confirmación con color de icono personalizado](./Modales - Filamento_files/icon-color(1).jpg)

### ​Personalización de la alineación del contenido modal

```

use Filament\Actions\Action;
use Filament\Support\Enums\Alignment;

Action::make('updateAuthor')
    ->schema([
        // ...
    ])
    ->action(function (array $data): void {
        // ...
    })
    ->modalAlignment(Alignment::Center)

```

Loading preview![Modal with centered content alignment](./Modales - Filamento_files/alignment.jpg)

![Modal with centered content alignment](./Modales - Filamento_files/alignment(1).jpg)

### ​Making the modal header sticky

```

use Filament\Actions\Action;

Action::make('updateAuthor')
    ->schema([
        // ...
    ])
    ->action(function (array $data): void {
        // ...
    })
    ->stickyModalHeader()

```

### ​Making the modal footer sticky

```

use Filament\Actions\Action;

Action::make('updateAuthor')
    ->schema([
        // ...
    ])
    ->action(function (array $data): void {
        // ...
    })
    ->stickyModalFooter()

```

Loading preview![Modal with sticky header and footer](./Modales - Filamento_files/sticky-header.jpg)

![Modal with sticky header and footer](./Modales - Filamento_files/sticky-header(1).jpg)

### ​Custom modal content

```

use App\Models\Post;
use Filament\Actions\Action;

Action::make('advance')
    ->action(fn (Post $record) => $record->advance())
    ->modalContent(view('filament.pages.actions.advance'))

```

#### ​Passing data to the custom modal content

```

use Filament\Actions\Action;
use Illuminate\Contracts\View\View;

Action::make('advance')
    ->action(fn (Contract $record) => $record->advance())
    ->modalContent(fn (Contract $record): View => view(
        'filament.pages.actions.advance',
        ['record' => $record],
    ))

```

#### ​Adding custom modal content below the form

```

use App\Models\Post;
use Filament\Actions\Action;

Action::make('advance')
    ->action(fn (Post $record) => $record->advance())
    ->modalContentFooter(view('filament.pages.actions.advance'))

```

#### ​Adding an action to custom modal content

```

use App\Models\Post;
use Filament\Actions\Action;
use Illuminate\Contracts\View\View;

Action::make('advance')
    ->registerModalActions([
        Action::make('report')
            ->requiresConfirmation()
            ->action(fn (Post $record) => $record->report()),
    ])
    ->action(fn (Post $record) => $record->advance())
    ->modalContent(fn (Action $action): View => view(
        'filament.pages.actions.advance',
        ['action' => $action],
    ))

```
as
```

{{ $action->getModalAction('report') }}

```

## ​Using a slide-over instead of a modal

```

use Filament\Actions\Action;

Action::make('updateAuthor')
    ->schema([
        // ...
    ])
    ->action(function (array $data): void {
        // ...
    })
    ->slideOver()

```

Loading preview![Slide over with form](./Modales - Filamento_files/slide-over.jpg)

![Slide over with form](./Modales - Filamento_files/slide-over(1).jpg)

Instead of opening in the center of the screen, the modal content will now slide in from the right and consume the entire height of the browser.### ​Changing the slide-over position

```

use Filament\Actions\Action;
use Filament\Support\Enums\SlideOverPosition;

Action::make('updateAuthor')
    ->schema([
        // ...
    ])
    ->action(function (array $data): void {
        // ...
    })
    ->slideOver()
    ->slideOverPosition(SlideOverPosition::Start)

```

Loading preview![Slide over from the start of the screen](./Modales - Filamento_files/slide-over-start.jpg)

![Slide over from the start of the screen](./Modales - Filamento_files/slide-over-start(1).jpg)

This is useful when the action trigger sits near the start of the viewport — for example, a row action at the beginning of a table row — so the slide-over opens adjacent to its trigger instead of across the screen.## ​Changing the modal width

```

use Filament\Actions\Action;
use Filament\Support\Enums\Width;

Action::make('updateAuthor')
    ->schema([
        // ...
    ])
    ->action(function (array $data): void {
        // ...
    })
    ->modalWidth(Width::FiveExtraLarge)

```

Loading preview![Modal with custom width](./Modales - Filamento_files/wide.jpg)

![Modal with custom width](./Modales - Filamento_files/wide(1).jpg)

## ​Executing code when the modal opens

```

use Filament\Actions\Action;
use Filament\Schemas\Schema;

Action::make('create')
    ->mountUsing(function (Schema $form) {
        $form->fill();

```
    // ...
})
```

```

## ​Customizing the action buttons in the footer of the modal

### ​Modifying the default modal footer action button

```

use Filament\Actions\Action;

Action::make('help')
    ->modalContent(view('actions.help'))
    ->modalCancelAction(fn (Action $action) => $action->label('Close'))

```

### ​Removing a default modal footer action button

```

use Filament\Actions\Action;

Action::make('help')
    ->modalContent(view('actions.help'))
    ->modalSubmitAction(false)

```

### ​Adding an extra modal action button to the footer

```

use Filament\Actions\Action;

Action::make('create')
    ->schema([
        // ...
    ])
    // ...
    ->extraModalFooterActions(fn (Action $action): array => [
        $action->makeModalSubmitAction('createAnother', arguments: ['another' => true]),
    ])

```

Vista previa de carga![Modal with extra footer action buttons](./Modales - Filamento_files/extra-footer-actions.jpg)

![Modal con botones de acción extra en el pie de página](./Modales - Filamento_files/extra-footer-actions(1).jpg)

```

use Filament\Actions\Action;

Action::make('create')
    ->schema([
        // ...
    ])
    // ...
    ->extraModalFooterActions(fn (Action $action): array => [
        $action->makeModalSubmitAction('createAnother', arguments: ['another' => true]),
    ])
    ->action(function (array $data, array $arguments): void {
        // Create

```
    if ($arguments['another'] ?? false) {
        // Reset the form and don't close the modal
    }
})
```

```

#### ​Abrir otro modal a partir de una acción extra de pie

Puedes anidar acciones entre sí, permitiéndote abrir un nuevo modal a partir de una acción extra en el pie de página:```
use Filament\Actions\Action;

Action::make('edit')
    // ...
    ->extraModalFooterActions([
        Action::make('delete')
            ->requiresConfirmation()
            ->action(function () {
                // ...
            }),
    ])

```

```
use Filament\Actions\Action;

Action::make('delete')
    ->requiresConfirmation()
    ->action(function () {
        // ...
    })
    ->cancelParentActions()

```

```
use Filament\Actions\Action;

Action::make('first')
    ->requiresConfirmation()
    ->action(function () {
        // ...
    })
    ->extraModalFooterActions([
        Action::make('second')
            ->requiresConfirmation()
            ->action(function () {
                // ...
            })
            ->extraModalFooterActions([
                Action::make('third')
                    ->requiresConfirmation()
                    ->action(function () {
                        // ...
                    })
                    ->extraModalFooterActions([
                        Action::make('fourth')
                            ->requiresConfirmation()
                            ->action(function () {
                                // ...
                            })
                            ->cancelParentActions('second'),
                    ]),
            ]),
    ])

```



## ​Acceder a información sobre las acciones de los padres desde un niño

```
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;

Action::make('first')
    ->schema([
        TextInput::make('foo'),
    ])
    ->action(function () {
        // ...
    })
    ->extraModalFooterActions([
        Action::make('second')
            ->requiresConfirmation()
            ->action(function (array $mountedActions) {
                dd($mountedActions[0]->getRawData());
            
                // ...
            }),
    ])

```

```
use Filament\Actions\Action;

Action::make('first')
    ->schema([
        TextInput::make('foo'),
    ])
    ->action(function () {
        // ...
    })
    ->extraModalFooterActions([
        Action::make('second')
            ->schema([
                TextInput::make('bar'),
            ])
            ->arguments(['number' => 2])
            ->action(function () {
                // ...
            })
            ->extraModalFooterActions([
                Action::make('third')
                    ->schema([
                        TextInput::make('baz'),
                    ])
                    ->arguments(['number' => 3])
                    ->action(function () {
                        // ...
                    })
                    ->extraModalFooterActions([
                        Action::make('fourth')
                            ->requiresConfirmation()
                            ->action(function (array $mountedActions) {
                                dd(
                                    $mountedActions[0]->getRawData(),
                                    $mountedActions[0]->getArguments(),
                                    $mountedActions[1]->getRawData(),
                                    $mountedActions[1]->getArguments(),
                                    $mountedActions[2]->getRawData(),
                                    $mountedActions[2]->getArguments(),
                                );
                                // ...
                            }),
                    ]),
            ]),
    ])

```



## ​Cierre del modal



### ​Cerrar el modal haciendo clic para alejar

```
use Filament\Actions\Action;

Action::make('updateAuthor')
    ->schema([
        // ...
    ])
    ->action(function (array $data): void {
        // ...
    })
    ->closeModalByClickingAway(false)

```

```
use Filament\Support\View\Components\ModalComponent;

ModalComponent::closedByClickingAway(false);

```



### ​Cerrar el modal escapando

```
use Filament\Actions\Action;

Action::make('updateAuthor')
    ->schema([
        // ...
    ])
    ->action(function (array $data): void {
        // ...
    })
    ->closeModalByEscaping(false)

```

```
use Filament\Support\View\Components\ModalComponent;

ModalComponent::closedByEscaping(false);

```



### ​Hiding the modal close button

```
use Filament\Actions\Action;

Action::make('updateAuthor')
    ->schema([
        // ...
    ])
    ->action(function (array $data): void {
        // ...
    })
    ->modalCloseButton(false)

```

```
use Filament\Support\View\Components\ModalComponent;

ModalComponent::closeButton(false);

```

Vista previa de carga![Modal without a close button](./Modales - Filamento_files/no-close-button.jpg)

![Modal sin botón de cierre](./Modales - Filamento_files/no-close-button(1).jpg)

## ​Realizando el clic modal

```
use Filament\Actions\Action;

Action::make('updateAuthor')
    ->schema([
        // ...
    ])
    ->action(function (array $data): void {
        // ...
    })
    ->modalClickThrough()

```

Cuando un modal es click-through, se elimina su fondo, los clics fuera de la ventana modal pasan a la página inferior y la página permanece desplazable. El modal aún puede cerrarse usando su botón de cierre o pulsando la tecla escape.## ​Evitar que el modal se enfoque automáticamente

```
use Filament\Actions\Action;

Action::make('updateAuthor')
    ->schema([
        // ...
    ])
    ->action(function (array $data): void {
        // ...
    })
    ->modalAutofocus(false)

```

```
use Filament\Support\View\Components\ModalComponent;

ModalComponent::autofocus(false);

```



## ​Superponer modales de acción hijo sobre modales de acción padre

```
use Filament\Actions\Action;
use Filament\Schemas\Components\Repeater;

Action::make('editItems')
    ->slideOver()
    ->schema([
        Repeater::make('items')
            ->schema([
                // ...
            ])
            ->deleteAction(
                fn (Action $action) => $action
                    ->requiresConfirmation()
                    ->overlayParentActions(),
            ),
    ])
    ->action(function () {
        // ...
    })

```

En este ejemplo, cuando el usuario pulsa el botón de eliminar en un elemento repetidor, el cuadro de confirmación aparece encima del deslizamiento en lugar de que el deslizamiento se cierre primero. Esto crea una experiencia más fluida, especialmente para acciones dentro de deslizamientos o formas complejas donde cerrar y volver a abrir el padre sería desorientador.Vista previa de carga![Child confirmation modal overlaying a parent slide-over](./Modales - Filamento_files/overlaying-child.jpg)

![Modal de confirmación hijo superpuesto a un deslizamiento parental](./Modales - Filamento_files/overlaying-child(1).jpg)

## ​Cancelación de acciones parentales cuando un modal está cerrado

```
use Filament\Actions\Action;

Action::make('createPost')
    ->schema([
        // ...
    ])
    ->extraModalFooterActions([
        Action::make('saveAsDraft')
            ->schema([
                // ...
            ])
            ->cancelParentActionsOnClose()
            ->action(function (): void {
                // ...
            }),
    ])
    ->action(function (array $data): void {
        // ...
    })

```

```
use Filament\Actions\Action;

Action::make('editPostMetadata')
    ->schema([
        // ...
    ])
    ->cancelParentActionsOnClose('createPost')
    ->action(function (): void {
        // ...
    })

```



## ​Optimizing modal configuration methods

```
use Filament\Actions\Action;

Action::make('updateAuthor')
    ->modal()

```



## ​Ocultar condicionalmente el modal

```
use Filament\Actions\Action;

Action::make('create')
    ->action(function (array $data): void {
        // ...
    })
    ->modalHidden($this->role !== 'admin')
    ->modalContent(view('filament.pages.actions.create'))

```



## ​Añadir atributos adicionales a la ventana modal

```
use Filament\Actions\Action;

Action::make('updateAuthor')
    ->extraModalWindowAttributes(['class' => 'update-author-modal'])

```



## ​Añadir atributos extra a la superposición modal

```
use Filament\Actions\Action;

Action::make('updateAuthor')
    ->extraModalOverlayAttributes(['class' => 'update-author-overlay'])

```

[Edita esta página en GitHub](https://github.com/filamentphp/filament/edit/5.x/packages/actions/docs/02-modals.md)## Patrocinado por

[Tu logo aquí](https://github.com/sponsors/danharrin)Ctrl+IModales - Filamento[Patrones CSS y JavaScript listos para copiar y pegar con accesibilidad integrada.anuncios vía Carbon](https://srv.carbonads.net/ads/click/x/GTND427YCAYDVKQYCE7LYKQUFTSIK53WF6AICZ3JCAAD553JF6SIP27KCVYI6KQWC6AIE2QLCA7D45QWCVBIVZ3JCAAD5K3NC6SDTK3K2JWNABY)![ads via Carbon](./Modales - Filamento_files/cnv)

![ads via Carbon](./Modales - Filamento_files/associate-segment)

![ads via Carbon](./Modales - Filamento_files/px)

![ads via Carbon](./Modales - Filamento_files/seg)

![ads via Carbon](./Modales - Filamento_files/spp.pl.download)

![ads via Carbon](./Modales - Filamento_files/src=15643656)

![ads via Carbon](./Modales - Filamento_files/saved_resource)