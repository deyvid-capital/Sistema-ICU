Aquí tienes el contenido en formato **Markdown** para que puedas copiarlo directamente:

```markdown
# Validación

---

## Introducción
Se pueden añadir reglas de validación a cualquier **Campo**.
En Laravel, las reglas de validación suelen definirse en arrays como `['required', 'max:255']` o en una cadena combinada como `required|max:255`. Esto está bien si trabajas exclusivamente en el backend con solicitudes de formulario sencillas. Pero **Filament** también puede dar validación **frontend** a tus usuarios, para que puedan corregir sus errores antes de que se realicen las solicitudes backend.

El filamento incluye muchos **Métodos dedicados de validación**, pero también puedes usar cualquier otra regla de validación de Laravel, incluyendo **Reglas de validación personalizadas**.

> **Nota:** Algunas reglas de validación por defecto de Laravel dependen de los nombres correctos de atributos y no funcionan cuando se pasan por `rule()` o `rules()`. Utiliza los métodos de validación dedicados siempre que puedas.

---

## Vista previa de carga
Un formulario con errores de validación.

---

## Reglas disponibles

---

### URL activa
El campo debe tener un registro válido de **A** o **AAAA** según la función PHP `dns_get_record()`.
[Consulta la documentación de Laravel](https://laravel.com/docs/validation).

```php
Field::make('name')->activeUrl()
```

---

### Después de (fecha)
El valor del campo debe ser un valor posterior a una fecha determinada.

```php
Field::make('start_date')->after('tomorrow')
```
Alternativamente, puedes pasar el nombre de otro campo para comparar:
```php
Field::make('start_date')
Field::make('end_date')->after('start_date')
```

---

### Después o igual a (fecha)
El valor del campo debe ser una fecha posterior o igual a la fecha dada.

```php
Field::make('start_date')->afterOrEqual('tomorrow')
```
Alternativamente, puedes pasar el nombre de otro campo para comparar:
```php
Field::make('start_date')
Field::make('end_date')->afterOrEqual('start_date')
```

---

### Alfa
El campo debe estar completamente compuesto por caracteres alfabéticos.

```php
Field::make('name')->alpha()
```

---

### Alpha Dash
El campo puede tener caracteres alfanuméricos, así como guiones y guiones bajos.

```php
Field::make('name')->alphaDash()
```

---

### Alfanumérico
El campo debe ser completamente de caracteres alfanuméricos.

```php
Field::make('name')->alphaNum()
```

---

### ASCII
El campo debe estar compuesto completamente por caracteres **ASCII de 7 bits**.

```php
Field::make('name')->ascii()
```

---

### Antes (fecha)
El valor del campo debe ser una fecha anterior a una fecha determinada.

```php
Field::make('start_date')->before('first day of next month')
```
Alternativamente, puedes pasar el nombre de otro campo para comparar:
```php
Field::make('start_date')->before('end_date')
Field::make('end_date')
```

---

### Antes o igual a (fecha)
El valor del campo debe ser una fecha anterior o igual a la fecha dada.

```php
Field::make('start_date')->beforeOrEqual('end of this month')
```
Alternativamente, puedes pasar el nombre de otro campo para comparar:
```php
Field::make('start_date')->beforeOrEqual('end_date')
Field::make('end_date')
```

---

### Confirmado
El campo debe tener un campo coincidente de `{field}_confirmation`.

```php
Field::make('password')->confirmed()
Field::make('password_confirmation')
```

---

### Diferente
El valor del campo debe ser diferente a otro.

```php
Field::make('backup_email')->different('email')
```

---

### No comienza con
El campo no debe comenzar con uno de los valores dados.

```php
Field::make('name')->doesntStartWith(['admin'])
```

---
### No termina con
El campo no debe terminar con uno de los valores dados.

```php
Field::make('name')->doesntEndWith(['admin'])
```

---
### Termina con
El campo debe terminar con uno de los valores dados.

```php
Field::make('name')->endsWith(['bot'])
```

---
### Enum
El campo debe contener un valor enum válido.

```php
Field::make('status')->enum(MyStatus::class)
```

---
### Existe
El valor del campo debe existir en la base de datos.

```php
Field::make('invitation')->exists()
```
Por defecto, se buscará en el modelo del formulario si está registrado. Puedes especificar una tabla o modelo personalizado:
```php
use App\Models\Invitation;

Field::make('invitation')->exists(table: Invitation::class)
```
Por defecto, se usa el nombre del campo como columna para buscar. Puedes especificar una columna personalizada:
```php
Field::make('invitation')->exists(column: 'id')
```
Puedes personalizar aún más la regla pasando un cierre al parámetro `modifyRuleUsing`:
```php
use Illuminate\Validation\Rules\Exists;

Field::make('invitation')
    ->exists(modifyRuleUsing: function (Exists $rule) {
        return $rule->where('is_active', 1);
    })
```
> **Nota:** La regla de validación `exists` de Laravel **no usa el modelo Eloquent** para consultar la base de datos por defecto, por lo que no aplicará ningún alcance global definido en el modelo, incluyendo eliminaciones suaves. Incluso si hay un registro eliminado suavemente con el mismo valor, la validación pasará.

Para aplicar alcances globales (como eliminaciones suaves o multitenancy), usa el método `scopedExists()`:
```php
use Filament\Forms\Components\TextInput;

TextInput::make('email')
    ->scopedExists()
```
Si deseas modificar la consulta Eloquent utilizada para verificar la existencia, puedes pasar una función al parámetro `modifyQueryUsing`:
```php
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

TextInput::make('email')
    ->scopedExists(modifyQueryUsing: function (Builder $query) {
        return $query->withoutGlobalScope(SoftDeletingScope::class);
    })
```

---
### Lleno
El campo no debe estar vacío cuando está presente.

```php
Field::make('name')->filled()
```

---
### Mayor que
El valor del campo debe ser mayor que otro.

```php
Field::make('newNumber')->gt('oldNumber')
```

---
### Mayor o igual que
El valor del campo debe ser mayor o igual que otro.

```php
Field::make('newNumber')->gte('oldNumber')
```

---
### Color hexadecimal
El valor del campo debe ser un color válido en formato hexadecimal.

```php
Field::make('color')->hexColor()
```

---
### En
El campo debe estar incluido en la lista de valores dada.

```php
Field::make('status')->in(['pending', 'completed'])
```
> **Nota:** Los campos de tipo *toggle buttons*, *checkbox list*, *radio* y *select* aplican automáticamente la regla `in()` según sus opciones disponibles, por lo que no es necesario agregarla manualmente.

---
### Dirección IP
El campo debe ser una dirección IP.

```php
Field::make('ip_address')->ip()
Field::make('ip_address')->ipv4()
Field::make('ip_address')->ipv6()
```

---
### JSON
El campo debe ser una cadena JSON válida.

```php
Field::make('ip_address')->json()
```

---
### Menor que
El valor del campo debe ser menor que otro.

```php
Field::make('newNumber')->lt('oldNumber')
```

---
### Menor o igual que
El valor del campo debe ser menor o igual que otro.

```php
Field::make('newNumber')->lte('oldNumber')
```

---
### Dirección MAC
El campo debe ser una dirección MAC.

```php
Field::make('mac_address')->macAddress()
```

---
### Múltiplo de
El campo debe ser un múltiplo de un valor.

```php
Field::make('number')->multipleOf(2)
```

---
### No está en
El campo no debe estar incluido en la lista de valores dada.

```php
Field::make('status')->notIn(['cancelled', 'rejected'])
```

---
### No coincide con Regex
El campo no debe coincidir con la expresión regular dada.

```php
Field::make('email')->notRegex('/^.+$/i')
```

---
### Nulo
El valor del campo puede estar vacío. Esta regla se aplica por defecto si la regla `required` no está presente.

```php
Field::make('name')->nullable()
```

---
### Prohibido
El valor del campo debe estar vacío.

```php
Field::make('name')->prohibited()
```

---
### Prohibido si
El campo debe estar vacío solo si el otro campo especificado tiene alguno de los valores dados.

```php
Field::make('name')->prohibitedIf('field', 'value')
```

---
### Prohibido a menos que
El campo debe estar vacío a menos que el otro campo especificado tenga alguno de los valores dados.

```php
Field::make('name')->prohibitedUnless('field', 'value')
```

---
### Prohíbe
Si el campo no está vacío, todos los otros campos especificados deben estar vacíos.

```php
Field::make('name')->prohibits('field')
Field::make('name')->prohibits(['field', 'another_field'])
```

---
### Requerido
El valor del campo no debe estar vacío.

```php
Field::make('name')->required()
```

---
### Marcar un campo como requerido
Por defecto, los campos requeridos mostrarán un asterisco (*) junto a su etiqueta. Puedes ocultar el asterisco en formularios donde todos los campos son requeridos o donde tenga sentido agregar un *indicador* a los campos opcionales:
```php
use Filament\Forms\Components\TextInput;

TextInput::make('name')
    ->required() // Añade validación para asegurar que el campo es requerido
    ->markAsRequired(false) // Elimina el asterisco
```
Si tu campo **no es requerido**, pero deseas mostrar un asterisco, puedes usar:
```php
TextInput::make('name')
    ->markAsRequired()
```

---
### Requerido si
El valor del campo no debe estar vacío solo si el otro campo especificado tiene alguno de los valores dados.

```php
Field::make('name')->requiredIf('field', 'value')
```

---
### Requerido si está aceptado
El valor del campo no debe estar vacío solo si el otro campo especificado es igual a *"yes"*, *"on"*, *1*, *"1"*, *true* o *"true"*.

```php
Field::make('name')->requiredIfAccepted('field')
```

---
### Requerido a menos que
El valor del campo no debe estar vacío a menos que el otro campo especificado tenga alguno de los valores dados.

```php
Field::make('name')->requiredUnless('field', 'value')
```

---
### Requerido con
El valor del campo no debe estar vacío solo si alguno de los otros campos especificados no está vacío.

```php
Field::make('name')->requiredWith('field,another_field')
```

---
### Requerido con todos
El valor del campo no debe estar vacío solo si todos los otros campos especificados no están vacíos.

```php
Field::make('name')->requiredWithAll('field,another_field')
```

---
### Requerido sin
El valor del campo no debe estar vacío solo cuando alguno de los otros campos especificados está vacío.

```php
Field::make('name')->requiredWithout('field,another_field')
```

---
### Requerido sin todos
El valor del campo no debe estar vacío solo cuando todos los otros campos especificados están vacíos.

```php
Field::make('name')->requiredWithoutAll('field,another_field')
```

---
### Regex
El campo debe coincidir con la expresión regular dada.

```php
Field::make('email')->regex('/^.+@.+$/i')
```

---
### Igual
El valor del campo debe ser igual a otro.

```php
Field::make('password')->same('passwordConfirmation')
```

---
### Comienza con
El campo debe comenzar con uno de los valores dados.

```php
Field::make('name')->startsWith(['a'])
```

---
### Cadena
El campo debe ser una cadena.

```php
Field::make('name')->string()
```

---
### Único
El valor del campo no debe existir en la base de datos.

```php
Field::make('email')->unique()
```
Si tu formulario de Filament ya tiene un modelo Eloquent asociado (como en un recurso de panel), Filament lo usará. También puedes especificar una tabla o modelo personalizado:
```php
use App\Models\User;

Field::make('email')->unique(table: User::class)
```
Por defecto, se usa el nombre del campo como columna para buscar. Puedes especificar una columna personalizada:
```php
Field::make('email')->unique(column: 'email_address')
```
Normalmente, deseas ignorar un modelo específico durante la validación de unicidad. Por ejemplo, en un formulario de "actualizar perfil" que incluye el nombre, la dirección de correo electrónico y la ubicación del usuario. Probablemente quieras verificar que la dirección de correo electrónico sea única. Sin embargo, si el usuario solo cambia el campo de nombre y no el de correo electrónico, no deseas que se lance un error de validación porque el usuario ya es el propietario de la dirección de correo electrónico en cuestión.

Si tu formulario de Filament ya tiene un modelo Eloquent asociado, Filament lo ignorará. Para evitar que Filament ignore el registro Eloquent actual, puedes pasar `false` al parámetro `ignoreRecord`:
```php
Field::make('email')->unique(ignoreRecord: false)
```
Alternativamente, para ignorar un registro Eloquent de tu elección, puedes pasarlo al parámetro `ignorable`:
```php
Field::make('email')->unique(ignorable: $ignoredUser)
```
Puedes personalizar aún más la regla pasando un cierre al parámetro `modifyRuleUsing`:
```php
use Illuminate\Validation\Rules\Unique;

Field::make('email')
    ->unique(modifyRuleUsing: function (Unique $rule) {
        return $rule->where('is_active', 1);
    })
```
> **Nota:** La regla de validación `unique` de Laravel **no usa el modelo Eloquent** para consultar la base de datos por defecto, por lo que no aplicará ningún alcance global definido en el modelo, incluyendo eliminaciones suaves. Incluso si hay un registro eliminado suavemente con el mismo valor, la validación fallará.

Para aplicar alcances globales (como eliminaciones suaves o multitenancy), usa el método `scopedUnique()`:
```php
use Filament\Forms\Components\TextInput;

TextInput::make('email')
    ->scopedUnique()
```
Si deseas modificar la consulta Eloquent utilizada para verificar la unicidad, puedes pasar una función al parámetro `modifyQueryUsing`:
```php
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

TextInput::make('email')
    ->scopedUnique(modifyQueryUsing: function (Builder $query) {
        return $query->withoutGlobalScope(SoftDeletingScope::class);
    })
```

---
### ULID
El campo debe ser un **Identificador Único Lexicográficamente Ordenable (ULID)** válido.

```php
Field::make('identifier')->ulid()
```

---
### UUID
El campo debe ser un **Identificador Único Universal (UUID)** válido según el estándar **RFC 4122** (versiones 1, 3, 4 o 5).

```php
Field::make('identifier')->uuid()
```

---
## Otras reglas
Puedes añadir otras reglas de validación a cualquier campo usando el método `rules()`:
```php
TextInput::make('slug')->rules(['alpha_dash'])
```
Una lista completa de reglas de validación se encuentra en la [documentación de Laravel](https://laravel.com/docs/validation).

---
## Reglas personalizadas
Puedes usar cualquier regla de validación personalizada como lo harías en Laravel:
```php
TextInput::make('slug')->rules([new Uppercase()])
```
También puedes usar reglas de cierre:
```php
use Closure;

TextInput::make('slug')->rules([
    fn (): Closure => function (string $attribute, $value, Closure $fail) {
        if ($value === 'foo') {
            $fail('El :attribute no es válido.');
        }
    },
])
```
Puedes inyectar utilidades como `$get` en tus reglas personalizadas, por ejemplo, si necesitas referenciar otros valores de campos en tu formulario. Para hacerlo, envuelve la regla de cierre en otra función que la retorne:
```php
use Filament\Schemas\Components\Utilities\Get;

TextInput::make('slug')->rules([
    fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
        if ($get('other_field') === 'foo' && $value !== 'bar') {
            $fail("El {$attribute} no es válido.");
        }
    },
])
```

---
## Personalizar atributos de validación
Cuando los campos fallan en la validación, su etiqueta se usa en el mensaje de error. Para personalizar la etiqueta utilizada en los mensajes de error de los campos, usa el método `validationAttribute()`:
```php
use Filament\Forms\Components\TextInput;

TextInput::make('name')
    ->validationAttribute('nombre completo')
```
Además de permitir un valor estático, el método `validationAttribute()` también acepta una función para calcularlo dinámicamente. Puedes inyectar varias utilidades en la función como parámetros.

---
## Mensajes de validación
Por defecto, se usa el mensaje de error de validación de Laravel. Para personalizar los mensajes de error, usa el método `validationMessages()`:
```php
use Filament\Forms\Components\TextInput;

TextInput::make('email')
    ->unique(// ...)
    ->validationMessages([
        'unique' => 'El :attribute ya ha sido registrado.',
    ])
```
Además de permitir un array de valores estáticos, el método `validationMessages()` también acepta una función para cada mensaje. Puedes inyectar varias utilidades en las funciones como parámetros.

---
## Permitir HTML en mensajes de validación
Por defecto, los mensajes de validación se renderizan como texto plano para prevenir ataques **XSS**. Sin embargo, puedes necesitar renderizar **HTML** en tus mensajes de validación, como al mostrar listas o enlaces. Para habilitar el renderizado de HTML en los mensajes de validación, usa el método `allowHtmlValidationMessages()`:
```php
use Filament\Forms\Components\TextInput;

TextInput::make('password')
    ->required()
    ->rules([
        new CustomRule(), // Regla personalizada que devuelve un mensaje de validación que contiene HTML
    ])
    ->allowHtmlValidationMessages()
```
> **Advertencia:** Habilitar esto evita escapar el contenido para renderizar mensajes de validación en este campo. Asegúrate de que cada mensaje (incluyendo los de reglas personalizadas o traducciones) sea seguro para renderizar. **Contenido no confiable puede llevar a XSS.**

---
## Desactivar la validación cuando no se guardan campos
Cuando un campo **no se guarda**, sigue siendo validado. Para desactivar la validación de campos que no se guardan, utiliza el método `validatedWhenNotDehydrated()`:
```php
use Filament\Forms\Components\TextInput;

TextInput::make('name')
    ->required()
    ->saved(false)
    ->validatedWhenNotDehydrated(false)
```
Además de permitir un valor estático, el método `validatedWhenNotDehydrated()` también acepta una función para calcularlo dinámicamente. Puedes inyectar varias utilidades en la función como parámetros.
```

---
