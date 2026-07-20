# Formularios
## Subida de Archivos (`FileUpload`)

### Introducción

El campo de subida de archivos está basado en [FilePond](https://pqina.nl/filepond/).

```php
use Filament\Forms\Components\FileUpload;

FileUpload::make('attachment')
```

*Vista previa de la carga*

Filament también soporta la biblioteca [spatie/laravel-medialibrary](https://spatie.be/docs/laravel-medialibrary/). Consulta la documentación del plugin para más información.

Por defecto, el componente `FileUpload` acepta cualquier ruta de archivo dentro del disco configurado; este valor puede ser manipulado por el cliente, así que una petición alterada puede enviar la ruta de cualquier otro archivo del mismo disco. Si tu disco almacena archivos de diversos usuarios, inquilinos o registros, debes usar `->preventFilePathTampering()` en el campo (o globalmente con `FileUpload::configureUsing()`) o aislar las subidas por usuario en el directorio para asegurar que sólo accede a sus propios archivos.

---

### Configuración de disco y directorio de almacenamiento

Por defecto, los archivos se subirán al disco de almacenamiento definido en el archivo de configuración. También puedes cambiarlo usando la variable de entorno `FILESYSTEM_DISK`.

Para previsualizar correctamente imágenes y archivos, FilePond requiere que los archivos se sirvan desde el mismo dominio de la aplicación, o bien tener los encabezados CORS adecuados. Asegúrate de que la variable `APP_URL` esté correctamente configurada, o modifica el driver del sistema de archivos para establecer la URL correcta. Si alojas archivos en otro dominio (como S3), asegúrate de configurar los encabezados CORS.

Para cambiar el disco, directorio y visibilidad de un campo específico utiliza los métodos `disk()`, `directory()` y `visibility()`:

```php
use Filament\Forms\Components\FileUpload;

FileUpload::make('attachment')
    ->disk('s3')
    ->directory('form-attachments')
    ->visibility('public')
```

Estos métodos aceptan valores estáticos o funciones para calcular dinámicamente el disco, directorio o visibilidad. Puedes inyectar utilidades a estas funciones.

> La visibilidad por defecto se decide por el nombre literal del disco: uno llamado `public` será tratado como público, otro nombre se considerará privado, sin importar la configuración real. Para evitar confusiones, nombra los discos personalizados de forma inequívoca o define la visibilidad explícitamente.

Es responsabilidad del desarrollador eliminar estos archivos del disco cuando se remuevan del formulario, ya que Filament no sabe si esos archivos se usan en otro lugar. Puedes automatizar esto observando eventos sobre el modelo.

---

### Subida de múltiples archivos

Puedes permitir la subida de varios archivos, los cuales se almacenarán como URLs en JSON:

```php
use Filament\Forms\Components\FileUpload;

FileUpload::make('attachments')
    ->multiple()
```

De manera opcional, puedes controlar esta opción mediante un booleano:

```php
use Filament\Forms\Components\FileUpload;

FileUpload::make('attachments')
    ->multiple(FeatureFlag::active())
```

Este método también admite una función para habilitar/deshabilitar la opción dinámicamente.

Si vas a guardar los archivos en Eloquent, agrega un cast del atributo a array:

```php
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected function casts(): array
    {
        return [
            'attachments' => 'array',
        ];
    }
}
```

---

### Controlando el número máximo de subidas en paralelo

Puedes controlar el número máximo de subidas simultáneas:

```php
FileUpload::make('attachments')
    ->multiple()
    ->maxParallelUploads(1)
```
El valor por defecto de FilePond es 2.

---

### Nombres de archivos

Por defecto, los archivos subidos se guardan con un nombre aleatorio para evitar colisiones.

#### Implicaciones de seguridad

Si usas `preserveFilenames()` o `getUploadedFileNameForStorageUsing()`, ten en cuenta los riesgos: los usuarios podrían subir archivos peligrosos con extensiones ejecutables como `.php`, incluso si has restringido los tipos MIME permitidos, debido a cómo funciona la validación por mimetipos de Laravel.

En discos públicos/locales puede existir riesgo de ejecución de código. S3 es más seguro porque no ejecuta archivos subidos.

La mejor práctica es guardar el nombre original en una columna separada (`storeFileNamesIn()`) y mantener los nombres aleatorios para los archivos almacenados.

#### Preservar nombres originales

Antes de usarlo, revisa las implicaciones de seguridad.

```php
FileUpload::make('attachment')
    ->preserveFilenames()
```

O definido dinámicamente:

```php
FileUpload::make('attachment')
    ->preserveFilenames(FeatureFlag::active())
```

#### Generar nombres personalizados

Personaliza el nombre bajo el que se guarda el archivo:

```php
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

FileUpload::make('attachment')
    ->getUploadedFileNameForStorageUsing(
        fn (TemporaryUploadedFile $file): string => (string) str($file->getClientOriginalName())->prepend('prefijo-'),
    )
```

#### Guardar nombres originales en un campo diferente

```php
FileUpload::make('attachments')
    ->multiple()
    ->storeFileNamesIn('attachment_file_names')
```
Asegúrate de castear este campo como array si subes múltiples archivos.

---

### Validación y seguridad de rutas

El valor del campo FileUpload es una cadena o array con la ruta en el disco. Este valor puede ser manipulado por el cliente. Si necesitas restringirlo llama a `preventFilePathTampering()`:

```php
FileUpload::make('avatar')
    ->preventFilePathTampering()
```

Esto compara contra el valor original del modelo. En formularios de creación sin registro, toda ruta enviada fallará salvo que lo permitas con el callback `allowFilePathUsing`.

Para configurarlo globalmente:

```php
FileUpload::configureUsing(function (FileUpload $component): void {
    $component->preventFilePathTampering();
});
```

Permitir rutas adicionales mediante callback:

```php
FileUpload::make('avatar')
    ->preventFilePathTampering(
        allowFilePathUsing: fn (string $file): bool => str_starts_with($file, 'templates/'),
    )
```

Personaliza el mensaje de error con `validationMessages()`.

---

### Modo Avatar

Convierte el campo en un selector de imagen en círculo para avatares:

```php
FileUpload::make('avatar')
    ->avatar()
```

---

### Editor de Imágenes

Activa el editor de imágenes integrado:

```php
FileUpload::make('image')
    ->image()
    ->imageEditor()
```

Abre el editor pulsando el icono de lápiz en la previsualización. Puedes habilitarlo con un booleano o función.

---

#### Opciones de recorte (crop)

Permite recortar a razones de aspecto específicas:

```php
FileUpload::make('image')
    ->image()
    ->imageEditor()
    ->imageEditorAspectRatioOptions([
        '16:9',
        '4:3',
        '1:1',
    ])
```

Si quieres recorte libre, incluye `null` en el array.

#### Modo, color de fondo y tamaño del área de recorte

```php
FileUpload::make('image')
    ->image()
    ->imageEditor()
    ->imageEditorMode(2)
    ->imageEditorEmptyFillColor('#000000')
    ->imageEditorViewportWidth('1920')
    ->imageEditorViewportHeight('1080')
```

#### Recorte circular

Para avatares, añade recorte circular:

```php
FileUpload::make('image')
    ->image()
    ->avatar()
    ->imageEditor()
    ->circleCropper()
```

---

### Forzar razón de aspecto

Para exigir una razón de aspecto específica, combina la validación y el editor automático:

```php
FileUpload::make('banner')
    ->image()
    ->imageAspectRatio('16:9')
    ->automaticallyOpenImageEditorForAspectRatio()
```

O permite edición completa:

```php
FileUpload::make('banner')
    ->image()
    ->imageEditor()
    ->imageAspectRatio('16:9')
    ->automaticallyOpenImageEditorForAspectRatio()
```

No es compatible con subidas múltiples.

---

### Recorte y redimensionado automático

Permite recortar y redimensionar imágenes automáticamente, sin editor:

```php
FileUpload::make('image')
    ->image()
    ->automaticallyCropImagesToAspectRatio('16:9')
    ->automaticallyResizeImagesMode('cover')
    ->automaticallyResizeImagesToWidth('1920')
    ->automaticallyResizeImagesToHeight('1080')
```

---

### Apariencia del área de subida

Puedes personalizar la interfaz visual:

```php
FileUpload::make('attachment')
    ->imagePreviewHeight('250')
    ->loadingIndicatorPosition('left')
    ->panelAspectRatio('2:1')
    ->panelLayout('integrated')
    ->removeUploadedFileButtonPosition('right')
    ->uploadButtonPosition('left')
    ->uploadProgressIndicatorPosition('left')
```

Para mostrar en cuadrícula:

```php
FileUpload::make('attachments')
    ->multiple()
    ->panelLayout('grid')
```

---

### Reordenar archivos

Permite reordenar archivos subidos:

```php
FileUpload::make('attachments')
    ->multiple()
    ->reorderable()
    ->appendFiles()
```

---

### Abrir archivos en nueva pestaña

Agrega un botón para abrir archivos en otra pestaña:

```php
FileUpload::make('attachments')
    ->multiple()
    ->openable()
```

Puedes personalizar la URL con `getOpenableFileUrlUsing()`:

```php
FileUpload::make('attachments')
    ->multiple()
    ->openable()
    ->getOpenableFileUrlUsing(fn (string $file): string => Storage::disk('s3')->temporaryUrl($file, now()->addMinutes(5)))
```

---

### Descargar archivos

Para agregar un botón de descarga:

```php
FileUpload::make('attachments')
    ->multiple()
    ->downloadable()
```

Personaliza la URL de descarga:

```php
FileUpload::make('attachments')
    ->multiple()
    ->downloadable()
    ->getDownloadableFileUrlUsing(fn (string $file): string => route('attachments.download', ['path' => $file]))
```

---

### Vista previa y otras opciones

Para deshabilitar la vista previa usa `previewable(false)`:

```php
FileUpload::make('attachments')
    ->multiple()
    ->previewable(false)
```

---

### Mover en vez de copiar archivos

Por defecto, los archivos se copian del almacenamiento temporal al guardar el formulario. Puedes moverlos en lugar de copiarlos, siempre que el almacenamiento temporal esté en el mismo disco:

```php
FileUpload::make('attachment')
    ->moveFiles()
```

---

### Evitar guardado permanente

Para que los archivos no se almacenen permanentemente (útil, por ejemplo, para archivos temporales tipo CSV):

```php
FileUpload::make('attachment')
    ->storeFiles(false)
```

---

### Orientar imágenes desde EXIF

Por defecto, FilePond orienta imágenes consultando su EXIF. Para desactivarlo:

```php
FileUpload::make('attachment')
    ->orientImagesFromExif(false)
```

---

### Ocultar botón de eliminación

Puedes ocultar la opción de eliminar archivos:

```php
FileUpload::make('attachment')
    ->deletable(false)
```

---

### Desactivar pegado de archivos

Evita subir archivos desde el portapapeles:

```php
FileUpload::make('attachment')
    ->pasteable(false)
```

---

### Prevenir obtención de información de archivos

Si tienes muchos archivos en almacenamiento remoto y tarda mucho en cargar el formulario, puedes desactivar que Filament revise el tamaño, tipo y existencia de archivos:

```php
FileUpload::make('attachment')
    ->fetchFileInformation(false)
```

---

### Personalizar mensaje de subida

```php
FileUpload::make('attachment')
    ->uploadingMessage('Subiendo archivo...')
```

---

## Validación de subidas de archivos

Además de las reglas generales de la página de validación, hay reglas adicionales para archivos:

- Livewire también valida el máximo tamaño de archivo (p.ej. 12MB). Puedes modificarlo en `config/livewire.php`.

---

### Validación por tipo de archivo

Por defecto, `FileUpload` acepta cualquier tipo de archivo. Es recomendable restringir los tipos permitidos para evitar riesgos:

```php
FileUpload::make('document')
    ->acceptedFileTypes(['application/pdf'])
```

Equivalente para imágenes:

```php
FileUpload::make('image')
    ->image()
```

Para mapas de MIME personalizados:

```php
FileUpload::make('designs')
    ->acceptedFileTypes([
        'x-world/x-3dmf',
        'application/vnd.sketchup.skp',
    ])
    ->mimeTypeMap([
        '3dm' => 'x-world/x-3dmf',
        'skp' => 'application/vnd.sketchup.skp',
    ]);
```

---

### Validación por tamaño de archivo

Restringe el tamaño en KB:

```php
FileUpload::make('attachment')
    ->minSize(512)
    ->maxSize(1024)
```

---

### Archivos grandes

Si tienes problemas subiendo archivos pesados, deberás aumentar valores en tu archivo `php.ini`:

```
post_max_size = 120M
upload_max_filesize = 120M
```

Para cambiar el límite en la configuración de Livewire:

```php
'temporary_file_upload' => [
    'rules' => ['required', 'file', 'max:122880'],
],
```

---

### Validación de dimensiones de imagen

Para limitar dimensiones mínimas o máximas:

```php
use Illuminate\Validation\Rule;

FileUpload::make('photo')
    ->image()
    ->rule(Rule::dimensions()->minWidth(800)->minHeight(600))

FileUpload::make('photo')
    ->image()
    ->rule(Rule::dimensions()->maxWidth(1920)->maxHeight(1080))
```

Combinadas:

```php
FileUpload::make('photo')
    ->image()
    ->rule(
        Rule::dimensions()
            ->minWidth(800)
            ->minHeight(600)
            ->maxWidth(1920)
            ->maxHeight(1080)
    )
```

---

### Validación de aspecto (ratio) de imagen

Restringe por razón de aspecto:

```php
FileUpload::make('banner')
    ->image()
    ->imageAspectRatio('16:9')

FileUpload::make('banner')
    ->image()
    ->imageAspectRatio(['16:9', '4:3', '1:1'])
```

O mediante `Rule::dimensions()`:

```php
FileUpload::make('banner')
    ->image()
    ->rule(Rule::dimensions()->minRatio(4 / 3)->maxRatio(16 / 9))
```

Para ayudar al usuario a cumplir la razón de aspecto, usa `automaticallyOpenImageEditorForAspectRatio()` o `automaticallyCropImagesToAspectRatio()`.

---

### Validación del número de archivos

Usa `minFiles()` y `maxFiles()` para limitar la cantidad de archivos:

```php
FileUpload::make('attachments')
    ->multiple()
    ->minFiles(2)
    ->maxFiles(5)
```

---