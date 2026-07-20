
---

```markdown
# Forms: Markdown Editor

---

## Introducción
El editor de **Markdown** en Filament permite:
- Editar y previsualizar contenido en formato Markdown.
- Subir imágenes mediante **drag and drop**.

### Ejemplo básico:
```php
use Filament\Forms\Components\MarkdownEditor;

MarkdownEditor::make('content')
```

---


---
## Seguridad

### ⚠️ Advertencia sobre XSS
Por defecto, el editor envía **Markdown y HTML en crudo** al backend. Un atacante podría interceptar el valor del componente y enviar un **HTML malicioso**, exponiendo tu sitio a vulnerabilidades de **Cross-Site Scripting (XSS)**.

#### Solución:
Cuando muestres el contenido del editor en una vista Blade, **siempre sanitiza el HTML**. Filament ya sanitiza automáticamente el HTML en componentes como `TextColumn` y `TextEntry`, pero si lo haces manualmente, usa el helper `sanitizeHtml()` de Filament:

```php
{!! str($record->content)->markdown()->sanitizeHtml() !!}
```

#### Detalles del sanitizador de Filament:
- El sanitizador integrado **permite atributos de estilo en línea** (ej: `style="color: red;"`) para soportar formato de texto rico (colores, resaltado, tamaño de imágenes).
- **No elimina** propiedades CSS como `background: url(...)` o `position: fixed`.
- **Recomendación**: Si el contenido proviene de usuarios no confiables, **restringe la configuración por defecto** del sanitizador. Consulta la [documentación de seguridad](https://filamentphp.com/docs) para personalizarlo.

---

## Personalización de los botones de la barra de herramientas

Puedes configurar los botones de la barra de herramientas del editor usando el método `toolbarButtons()`. Los botones predeterminados son:

```php
use Filament\Forms\Components\MarkdownEditor;

MarkdownEditor::make('content')
    ->toolbarButtons([
        ['bold', 'italic', 'strike', 'link'],
        ['heading'],
        ['blockquote', 'codeBlock', 'bulletList', 'orderedList'],
        ['table', 'attachFiles'],
        ['undo', 'redo'],
    ]);
```
- Cada **array anidado** representa un **grupo de botones** en la barra de herramientas.
- El método `toolbarButtons()` también acepta una **función** para calcular dinámicamente los botones.

---


---
## Subida de imágenes al editor

El editor permite subir imágenes, que se almacenarán en una **URL públicamente accesible** (con permisos de almacenamiento público). No se soportan URLs temporales para subidas de archivos en contenido estático.

### Configuración de la ubicación de subida:
```php
use Filament\Forms\Components\MarkdownEditor;

MarkdownEditor::make('content')
    ->fileAttachmentsDisk('s3')          // Disco de almacenamiento (ej: 's3', 'public')
    ->fileAttachmentsDirectory('attachments'); // Directorio dentro del disco
```
- Los métodos `fileAttachmentsDisk()` y `fileAttachmentsDirectory()` también aceptan **funciones** para calcular los valores dinámicamente.

---

## Validación de imágenes subidas

### Restringir tipos de archivo aceptados
Usa `fileAttachmentsAcceptedFileTypes()` para controlar los **tipos MIME** permitidos. Por defecto, se aceptan:
- `image/png`
- `image/jpeg`
- `image/gif`
- `image/webp`

#### Ejemplo:
```php
MarkdownEditor::make('content')
    ->fileAttachmentsAcceptedFileTypes(['image/png', 'image/jpeg']);
```

### Limitar el tamaño máximo de los archivos
Usa `fileAttachmentsMaxSize()` para definir el **tamaño máximo en kilobytes**. Por defecto, el límite es **12288 KB (12 MB)**.

#### Ejemplo (5 MB):
```php
MarkdownEditor::make('content')
    ->fileAttachmentsMaxSize(5120); // 5 MB (5120 KB)
```

---
```

---
### Notas adicionales:
1. **Sanitización**: Siempre sanitiza el HTML al mostrar contenido de usuarios no confiables.
2. **Almacenamiento**: Asegúrate de que el disco y directorio configurados para las subidas existan y tengan los permisos correctos.
3. **Pruebas**: Verifica que las imágenes subidas se guarden correctamente y que los botones de la barra de herramientas funcionen como esperas.

