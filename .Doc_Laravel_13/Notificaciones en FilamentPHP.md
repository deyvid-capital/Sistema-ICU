# Notificaciones en FilamentPHP

---

## 📌 Resumen

Las notificaciones en FilamentPHP se envían utilizando un objeto construido a través de una **API fluida**. Al llamar al método `send()` en el objeto `Notification`, se despachará y mostrará la notificación en tu aplicación. Dado que las notificaciones utilizan la sesión para enviarse, pueden enviarse desde cualquier parte de tu código, **incluyendo JavaScript**, no solo desde componentes de Livewire.

---

## 📖 Introducción

Las notificaciones son una forma efectiva de comunicar información al usuario. Filament proporciona una API intuitiva para crear notificaciones personalizadas con títulos, iconos, colores, acciones y más.

---

## 🚀 Uso Básico

### Ejemplo en PHP

```php
<?php

namespace App\Livewire;

use Filament\Notifications\Notification;
use Livewire\Component;

class EditPost extends Component
{
    public function save(): void
    {
        // ...
        
        Notification::make()
            ->title('Saved successfully')
            ->success()
            ->send();
    }
}
```

### Ejemplo en JavaScript

```javascript
new FilamentNotification()
    .title('Saved successfully')
    .send()
```

---

## 📝 Establecer un Título

El mensaje principal de la notificación aparece en el título. Puedes establecerlo de la siguiente manera:

### En PHP

```php
use Filament\Notifications\Notification;

Notification::make()
    ->title('Saved successfully')
    ->send();
```

### Con Markdown

El título puede contener **HTML seguro** y **Markdown**. Usa el helper `Str::markdown()` para convertir Markdown a HTML seguro:

```php
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

Notification::make()
    ->title(Str::markdown('Saved **successfully**'))
    ->send();
```

> **⚠️ Nota de Seguridad**:  
> El sanitizador HTML integrado de Filament permite atributos en línea para soportar funciones de formato de texto enriquecido como colores de fuente, resaltado de texto y tamaño de imágenes.  
> **No uses contenido de usuarios no confiables** sin sanitizarlo adecuadamente.  
> Consulta la [Documentación de Seguridad](https://filamentphp.com/docs) para más detalles sobre cómo personalizar el sanitizador.

### En JavaScript

```javascript
new FilamentNotification()
    .title('Saved successfully')
    .send()
```

---

## 🎨 Establecer un Icono

Puedes añadir un icono a la notificación y personalizar su color (por defecto es gris):

### En PHP

```php
use Filament\Notifications\Notification;

Notification::make()
    ->title('Saved successfully')
    ->icon('heroicon-o-document-text')
    ->iconColor('success')
    ->send();
```

### En JavaScript

```javascript
new FilamentNotification()
    .title('Saved successfully')
    .icon('heroicon-o-document-text')
    .iconColor('success')
    .send()
```

---

## 🌈 Estados de Notificación

Las notificaciones pueden tener diferentes estados: `success`, `warning`, `danger` o `info`. En lugar de configurar manualmente el icono y el color, puedes usar el método `status()` o los métodos dedicados:

### En PHP

```php
use Filament\Notifications\Notification;

Notification::make()
    ->title('Saved successfully')
    ->success() // Método dedicado para estado "success"
    ->send();
```

### Métodos de Estado


| Estado        | Método en PHP        | Método en JavaScript |
| ------------- | -------------------- | -------------------- |
| Éxito         | `->success()`        | `.success()`         |
| Advertencia   | `->warning()`        | `.warning()`         |
| Peligro       | `->danger()`         | `.danger()`          |
| Información   | `->info()`           | `.info()`            |
| Personalizado | `->status('custom')` | `.status('custom')`  |


### En JavaScript

```javascript
new FilamentNotification()
    .title('Saved successfully')
    .success()
    .send()
```

---

## 🎨 Establecer un Color de Fondo

Puedes personalizar el color de fondo de la notificación:

### En PHP

```php
use Filament\Notifications\Notification;

Notification::make()
    ->title('Saved successfully')
    ->color('success')
    ->send();
```

### En JavaScript

```javascript
new FilamentNotification()
    .title('Saved successfully')
    .color('success')
    .send()
```

---

## ⏳ Establecer una Duración

Por defecto, las notificaciones se muestran durante **6 segundos** antes de cerrarse automáticamente. Puedes personalizar esta duración:

### En Milisegundos

```php
use Filament\Notifications\Notification;

Notification::make()
    ->title('Saved successfully')
    ->success()
    ->duration(5000) // 5 segundos
    ->send();
```

### En Segundos

```php
use Filament\Notifications\Notification;

Notification::make()
    ->title('Saved successfully')
    ->success()
    ->seconds(5) // 5 segundos
    ->send();
```

### En JavaScript

```javascript
new FilamentNotification()
    .title('Saved successfully')
    .success()
    .duration(5000) // 5 segundos en milisegundos
    .send();

// O en segundos
new FilamentNotification()
    .title('Saved successfully')
    .success()
    .seconds(5)
    .send();
```

### Notificaciones Persistentes

Si prefieres que la notificación **no se cierre automáticamente** y el usuario deba cerrarla manualmente:

```php
use Filament\Notifications\Notification;

Notification::make()
    ->title('Saved successfully')
    ->success()
    ->persistent()
    ->send();
```

```javascript
new FilamentNotification()
    .title('Saved successfully')
    .success()
    .persistent()
    .send()
```

---

## 📄 Texto del Cuerpo

Puedes añadir un texto adicional en el cuerpo de la notificación con el método `body()`:

### En PHP

```php
use Filament\Notifications\Notification;

Notification::make()
    ->title('Saved successfully')
    ->success()
    ->body('Changes to the post have been saved.')
    ->send();
```

### Con Markdown

```php
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

Notification::make()
    ->title('Saved successfully')
    ->success()
    ->body(Str::markdown('Changes to the **post** have been saved.'))
    ->send();
```

### En JavaScript

```javascript
new FilamentNotification()
    .title('Saved successfully')
    .success()
    .body('Changes to the post have been saved.')
    .send()
```

---

## 🔘 Añadir Acciones a las Notificaciones

Las notificaciones soportan **acciones**, que son botones que se muestran debajo del contenido. Estas acciones pueden abrir una URL o enviar un evento de Livewire.

### En PHP

```php
use Filament\Actions\Action;
use Filament\Notifications\Notification;

Notification::make()
    ->title('Saved successfully')
    ->success()
    ->body('Changes to the post have been saved.')
    ->actions([
        Action::make('view')
            ->button(),
        Action::make('undo')
            ->color('gray'),
    ])
    ->send();
```

### En JavaScript

```javascript
new FilamentNotification()
    .title('Saved successfully')
    .success()
    .body('Changes to the post have been saved.')
    .actions([
        new FilamentNotificationAction('view')
            .button(),
        new FilamentNotificationAction('undo')
            .color('gray'),
    ])
    .send()
```

> **📌 Nota**: Puedes aprender más sobre cómo estilizar los botones de acción en la [documentación de Acciones de Filament](https://filamentphp.com/docs).

---

## 🌐 Abrir URLs desde Acciones de Notificación

Puedes configurar una acción para abrir una URL, opcionalmente en una nueva pestaña:

### En PHP

```php
use Filament\Actions\Action;
use Filament\Notifications\Notification;

Notification::make()
    ->title('Saved successfully')
    ->success()
    ->body('Changes to the post have been saved.')
    ->actions([
        Action::make('view')
            ->button()
            ->url(route('posts.show', $post), shouldOpenInNewTab: true),
        Action::make('undo')
            ->color('gray'),
    ])
    ->send();
```

### En JavaScript

```javascript
new FilamentNotification()
    .title('Saved successfully')
    .success()
    .body('Changes to the post have been saved.')
    .actions([
        new FilamentNotificationAction('view')
            .button()
            .url('/view')
            .openUrlInNewTab(),
        new FilamentNotificationAction('undo')
            .color('gray'),
    ])
    .send()
```

> **⚠️ Advertencia de Seguridad**:  
> Si pasas datos controlados por el usuario al método `url()`, **valida que la URL no use esquemas peligrosos** como `javascript:` o `data:`.  
> Usa el helper `Str::sanitizeUrl()` para sanitizar URLs:
>
> ```php
> use Illuminate\Support\Str;
>
> Action::make('view')
>     ->url(Str::sanitizeUrl($userProvidedUrl))
> ```

---

## 📡 Despachar Eventos Livewire desde Acciones

Puedes ejecutar código adicional cuando se hace clic en una acción de notificación, despachando un evento Livewire:

### En PHP

```php
use Filament\Actions\Action;
use Filament\Notifications\Notification;

Notification::make()
    ->title('Saved successfully')
    ->success()
    ->body('Changes to the post have been saved.')
    ->actions([
        Action::make('view')
            ->button()
            ->url(route('posts.show', $post), shouldOpenInNewTab: true),
        Action::make('undo')
            ->color('gray')
            ->dispatch('undoEditingPost', [$post->id]),
    ])
    ->send();
```

### Métodos de Despacho


| Método           | Descripción                                                    | Ejemplo en PHP                           | Ejemplo en JavaScript                   |
| ---------------- | -------------------------------------------------------------- | ---------------------------------------- | --------------------------------------- |
| `dispatch()`     | Despacha un evento al componente actual.                       | `->dispatch('eventName', [$data])`       | `.dispatch('eventName')`                |
| `dispatchSelf()` | Despacha un evento al componente actual (alias de `dispatch`). | `->dispatchSelf('eventName', [$data])`   | `.dispatchSelf('eventName')`            |
| `dispatchTo()`   | Despacha un evento a otro componente.                          | `->dispatchTo('component', 'eventName')` | `.dispatchTo('component', 'eventName')` |


### Ejemplos en JavaScript

```javascript
new FilamentNotification()
    .title('Saved successfully')
    .success()
    .body('Changes to the post have been saved.')
    .actions([
        new FilamentNotificationAction('view')
            .button()
            .url('/view')
            .openUrlInNewTab(),
        new FilamentNotificationAction('undo')
            .color('gray')
            .dispatch('undoEditingPost'),
    ])
    .send()

// Usando dispatchSelf
new FilamentNotificationAction('undo')
    .color('gray')
    .dispatchSelf('undoEditingPost')

// Usando dispatchTo
new FilamentNotificationAction('undo')
    .color('gray')
    .dispatchTo('another_component', 'undoEditingPost')
```

---

## 🚪 Cerrar Notificaciones desde Acciones

Puedes cerrar la notificación automáticamente después de ejecutar una acción:

### En PHP

```php
use Filament\Actions\Action;
use Filament\Notifications\Notification;

Notification::make()
    ->title('Saved successfully')
    ->success()
    ->body('Changes to the post have been saved.')
    ->actions([
        Action::make('view')
            ->button()
            ->url(route('posts.show', $post), shouldOpenInNewTab: true),
        Action::make('undo')
            ->color('gray')
            ->dispatch('undoEditingPost', [$post->id])
            ->close(), // Cierra la notificación al hacer clic
    ])
    ->send();
```

### En JavaScript

```javascript
new FilamentNotification()
    .title('Saved successfully')
    .success()
    .body('Changes to the post have been saved.')
    .actions([
        new FilamentNotificationAction('view')
            .button()
            .url('/view')
            .openUrlInNewTab(),
        new FilamentNotificationAction('undo')
            .color('gray')
            .dispatch('undoEditingPost')
            .close(), // Cierra la notificación al hacer clic
    ])
    .send()
```

---

## 🌐 Usar Objetos JavaScript

Los objetos JavaScript `FilamentNotification` y `FilamentNotificationAction` están asignados a `window.FilamentNotification` y `window.FilamentNotificationAction`, por lo que están disponibles en scripts en la página.

### Importar en un Archivo JavaScript

Si estás usando un bundler como Vite o Webpack, puedes importarlos directamente:

```javascript
import { Notification, NotificationAction } from '../../vendor/filament/notifications/dist/index.js';

// Uso
new Notification()
    .title('Hello')
    .send();
```

---

## 🚫 Cerrar una Notificación con JavaScript

Puedes cerrar una notificación manualmente despachando un evento en el navegador llamado `close-notification`. Este evento debe contener el **ID de la notificación**.

### Obtener el ID de la Notificación

```php
use Filament\Notifications\Notification;

$notification = Notification::make()
    ->title('Hello')
    ->persistent()
    ->send();

$notificationId = $notification->getId();
```

### Cerrar la Notificación desde Livewire

```php
$this->dispatch('close-notification', id: $notificationId);
```

### Cerrar la Notificación desde JavaScript (Alpine.js)

```html
<button x-on:click="$dispatch('close-notification', { id: notificationId })" type="button">
    Close Notification
</button>
```

### Usar un ID Personalizado

Si no puedes persistir el ID aleatorio, puedes pasar un **ID personalizado** al crear la notificación:

```php
use Filament\Notifications\Notification;

Notification::make('greeting') // ID personalizado
    ->title('Hello')
    ->persistent()
    ->send();
```

Luego, cierra la notificación usando el ID personalizado:

```html
<button x-on:click="$dispatch('close-notification', { id: 'greeting' })" type="button">
    Close Notification
</button>
```

> **⚠️ Advertencia**:  
> Si envías múltiples notificaciones con el **mismo ID**, podrías experimentar efectos secundarios inesperados. Se recomienda usar **IDs aleatorios** siempre que sea posible.

---

## 📍 Posicionamiento de Notificaciones

Puedes configurar la alineación de las notificaciones en un **proveedor de servicios** o **middleware** usando los métodos `alignment()` y `verticalAlignment()`. Las opciones disponibles son:


| Alineación Horizontal | Método en PHP       | Valor    |
| --------------------- | ------------------- | -------- |
| Inicio                | `Alignment::Start`  | `start`  |
| Centro                | `Alignment::Center` | `center` |
| Fin                   | `Alignment::End`    | `end`    |



| Alineación Vertical | Método en PHP               | Valor    |
| ------------------- | --------------------------- | -------- |
| Inicio              | `VerticalAlignment::Start`  | `start`  |
| Centro              | `VerticalAlignment::Center` | `center` |
| Fin                 | `VerticalAlignment::End`    | `end`    |


### Ejemplo en PHP

```php
use Filament\Notifications\Livewire\Notifications;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\VerticalAlignment;

// Configurar alineación horizontal y vertical
Notifications::alignment(Alignment::Start);
Notifications::verticalAlignment(VerticalAlignment::End);
```

---

## 📌 Resumen de Métodos Disponibles

### Métodos de `Notification` (PHP)


| Método         | Descripción                                                              | Ejemplo                             |
| -------------- | ------------------------------------------------------------------------ | ----------------------------------- |
| `title()`      | Establece el título de la notificación.                                  | `->title('Saved successfully')`     |
| `body()`       | Establece el cuerpo de la notificación.                                  | `->body('Changes saved.')`          |
| `icon()`       | Establece el icono de la notificación.                                   | `->icon('heroicon-o-check')`        |
| `iconColor()`  | Establece el color del icono.                                            | `->iconColor('success')`            |
| `color()`      | Establece el color de fondo de la notificación.                          | `->color('success')`                |
| `success()`    | Establece el estado a "éxito".                                           | `->success()`                       |
| `warning()`    | Establece el estado a "advertencia".                                     | `->warning()`                       |
| `danger()`     | Establece el estado a "peligro".                                         | `->danger()`                        |
| `info()`       | Establece el estado a "información".                                     | `->info()`                          |
| `duration()`   | Establece la duración en milisegundos.                                   | `->duration(5000)`                  |
| `seconds()`    | Establece la duración en segundos.                                       | `->seconds(5)`                      |
| `persistent()` | Hace que la notificación sea persistente (no se cierre automáticamente). | `->persistent()`                    |
| `actions()`    | Añade acciones a la notificación.                                        | `->actions([Action::make('undo')])` |
| `send()`       | Envía la notificación.                                                   | `->send()`                          |
| `getId()`      | Obtiene el ID de la notificación.                                        | `$notification->getId()`            |


### Métodos de `FilamentNotification` (JavaScript)


| Método          | Descripción                                     | Ejemplo                                              |
| --------------- | ----------------------------------------------- | ---------------------------------------------------- |
| `.title()`      | Establece el título de la notificación.         | `.title('Saved successfully')`                       |
| `.body()`       | Establece el cuerpo de la notificación.         | `.body('Changes saved.')`                            |
| `.icon()`       | Establece el icono de la notificación.          | `.icon('heroicon-o-check')`                          |
| `.iconColor()`  | Establece el color del icono.                   | `.iconColor('success')`                              |
| `.color()`      | Establece el color de fondo de la notificación. | `.color('success')`                                  |
| `.success()`    | Establece el estado a "éxito".                  | `.success()`                                         |
| `.warning()`    | Establece el estado a "advertencia".            | `.warning()`                                         |
| `.danger()`     | Establece el estado a "peligro".                | `.danger()`                                          |
| `.info()`       | Establece el estado a "información".            | `.info()`                                            |
| `.duration()`   | Establece la duración en milisegundos.          | `.duration(5000)`                                    |
| `.seconds()`    | Establece la duración en segundos.              | `.seconds(5)`                                        |
| `.persistent()` | Hace que la notificación sea persistente.       | `.persistent()`                                      |
| `.actions()`    | Añade acciones a la notificación.               | `.actions([new FilamentNotificationAction('undo')])` |
| `.send()`       | Envía la notificación.                          | `.send()`                                            |


---

## 📚 Recursos Adicionales

- [Documentación Oficial de Filament](https://filamentphp.com/docs)
- [Documentación de Acciones en Filament](https://filamentphp.com/docs/actions)
- [Documentación de Seguridad en Filament](https://filamentphp.com/docs/security)