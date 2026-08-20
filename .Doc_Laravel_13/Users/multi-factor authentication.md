
---

# Usuarios
## Autenticación Multifactor (MFA)

---

## Introducción
Por defecto, los usuarios en **Filament** pueden iniciar sesión con su dirección de correo electrónico y contraseña. Sin embargo, puedes habilitar la **autenticación multifactor (MFA)** para agregar una capa adicional de seguridad a las cuentas de tus usuarios.

Cuando **MFA** está habilitado, los usuarios deben realizar un paso adicional antes de ser autenticados y obtener acceso a la aplicación.

![Vista previa de la página de desafío de autenticación multifactor](#)

**Filament** incluye dos métodos de **MFA** que puedes habilitar directamente:
1. **Autenticación mediante aplicación**: Utiliza una aplicación compatible con **Google Authenticator** (como Google Authenticator, Authy o Microsoft Authenticator) para generar una **contraseña de un solo uso basada en tiempo (TOTP)** que se utiliza para verificar al usuario.
2. **Autenticación mediante correo electrónico**: Envía un **código de un solo uso** a la dirección de correo electrónico del usuario, que debe ingresar para verificar su identidad.

En **Filament**, los usuarios configuran la autenticación multifactor desde su página de perfil. Si utilizas la función de página de perfil de **Filament**, la configuración de la autenticación multifactor agregará automáticamente los elementos de interfaz de usuario correctos a la página de perfil:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->profile();
}
```

![Opciones de autenticación multifactor en la página de perfil](#)

---

## Autenticación mediante aplicación

### 1. Agregar una columna a la tabla de usuarios
Para habilitar la autenticación mediante aplicación en un panel, primero debes agregar una nueva columna a tu tabla `users` (o a la tabla que se utilice para tu modelo **Eloquent** "autenticable" en este panel). Esta columna debe almacenar la **clave secreta** utilizada para generar y verificar las contraseñas de un solo uso basadas en tiempo. Puede ser una columna de tipo `text()` en una migración:

```php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

Schema::table('users', function (Blueprint $table) {
    $table->text('app_authentication_secret')->nullable();
});
```

### 2. Implementar la interfaz y el trait en el modelo `User`
En el modelo `User`, debes implementar la interfaz `HasAppAuthentication` y usar el **trait** `InteractsWithAppAuthentication`, que proporciona los métodos necesarios para interactuar con el código secreto y otra información sobre la integración:

```php
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements FilamentUser, HasAppAuthentication, MustVerifyEmail
{
    use InteractsWithAppAuthentication;

    // ...
}
```

**Filament** proporciona una implementación predeterminada para mayor rapidez y simplicidad, pero podrías implementar los métodos requeridos tú mismo y personalizar el nombre de la columna o almacenar el secreto en una tabla completamente separada.

### 3. Activar la autenticación mediante aplicación en el panel
Finalmente, debes activar la función de autenticación mediante aplicación en tu panel. Para hacerlo, usa el método `multiFactorAuthentication()` en la configuración y pasa una instancia de `AppAuthentication`:

```php
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->multiFactorAuthentication([
            AppAuthentication::make(),
        ]);
}
```

---

## Configurar códigos de recuperación para la autenticación mediante aplicación
Si tus usuarios pierden el acceso a su aplicación de autenticación de dos factores, no podrán iniciar sesión en tu aplicación. Para evitar esto, puedes generar un conjunto de **códigos de recuperación** que los usuarios puedan utilizar para iniciar sesión si pierden el acceso a su aplicación de autenticación.

De manera similar a la columna `app_authentication_secret`, debes agregar una nueva columna a tu tabla `users` (o a la tabla que se utilice para tu modelo **Eloquent** "autenticable" en este panel). Esta columna debe almacenar los códigos de recuperación. Puede ser una columna de tipo `text()` en una migración:

```php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

Schema::table('users', function (Blueprint $table) {
    $table->text('app_authentication_recovery_codes')->nullable();
});
```

### Implementar la interfaz y el trait para los códigos de recuperación
A continuación, debes implementar la interfaz `HasAppAuthenticationRecovery` en el modelo `User` y usar el **trait** `InteractsWithAppAuthenticationRecovery`, que proporciona a **Filament** los métodos necesarios para interactuar con los códigos de recuperación:

```php
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery, MustVerifyEmail
{
    use InteractsWithAppAuthentication;
    use InteractsWithAppAuthenticationRecovery;

    // ...
}
```

**Filament** proporciona una implementación predeterminada para mayor rapidez y simplicidad, pero podrías implementar los métodos requeridos tú mismo y personalizar el nombre de la columna o almacenar los códigos de recuperación en una tabla completamente separada.

### Activar los códigos de recuperación en el panel
Finalmente, debes activar la función de códigos de recuperación para la autenticación mediante aplicación en tu panel. Para hacerlo, pasa el método `recoverable()` a la instancia de `AppAuthentication` en el método `multiFactorAuthentication()`:

```php
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->multiFactorAuthentication([
            AppAuthentication::make()
                ->recoverable(),
        ]);
}
```

---

## Cambiar la cantidad de códigos de recuperación generados
Por defecto, **Filament** genera **8 códigos de recuperación** para cada usuario. Si deseas cambiar esto, puedes usar el método `recoveryCodeCount()` en la instancia de `AppAuthentication` en el método `multiFactorAuthentication()`:

```php
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->multiFactorAuthentication([
            AppAuthentication::make()
                ->recoverable()
                ->recoveryCodeCount(10),
        ]);
}
```

---

## Evitar que los usuarios regeneren sus códigos de recuperación
Por defecto, los usuarios pueden visitar su perfil para regenerar sus códigos de recuperación. Si deseas evitar esto, puedes usar el método `regenerableRecoveryCodes(false)` en la instancia de `AppAuthentication` en el método `multiFactorAuthentication()`:

```php
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->multiFactorAuthentication([
            AppAuthentication::make()
                ->recoverable()
                ->regenerableRecoveryCodes(false),
        ]);
}
```

---

## Cambiar el tiempo de expiración del código de la aplicación
Los códigos de la aplicación se emiten utilizando un algoritmo de **contraseña de un solo uso basada en tiempo (TOTP)**, lo que significa que solo son válidos durante un corto período de tiempo antes y después de su generación. Este tiempo se define en una **"ventana"** de tiempo. Por defecto, **Filament** utiliza una ventana de expiración de **8**, lo que crea un período de validez de **4 minutos** a cada lado del tiempo de generación (8 minutos en total).

Para cambiar la ventana, por ejemplo, para que sea válida solo durante **2 minutos** después de su generación, puedes usar el método `codeWindow()` en la instancia de `AppAuthentication`, estableciéndolo en **4**:

```php
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->multiFactorAuthentication([
            AppAuthentication::make()
                ->codeWindow(4),
        ]);
}
```

---

## Personalizar el nombre de la marca de la autenticación mediante aplicación
Cada integración de autenticación mediante aplicación tiene un **"nombre de marca"** que se muestra en la aplicación de autenticación. Por defecto, este es el nombre de tu aplicación. Si deseas cambiarlo, puedes usar el método `brandName()` en la instancia de `AppAuthentication` en el método `multiFactorAuthentication()`:

```php
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->multiFactorAuthentication([
            AppAuthentication::make()
                ->brandName('Demo de Filament'),
        ]);
}
```

---

## Autenticación mediante correo electrónico
La autenticación mediante correo electrónico envía **códigos de un solo uso** a la dirección de correo electrónico del usuario, que deben ingresar para verificar su identidad.

### 1. Agregar una columna a la tabla de usuarios
Para habilitar la autenticación mediante correo electrónico en un panel, primero debes agregar una nueva columna a tu tabla `users` (o a la tabla que se utilice para tu modelo **Eloquent** "autenticable" en este panel). Esta columna debe almacenar un valor booleano que indique si la autenticación mediante correo electrónico está habilitada o no:

```php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

Schema::table('users', function (Blueprint $table) {
    $table->boolean('has_email_authentication')->default(false);
});
```

### 2. Implementar la interfaz y el trait en el modelo `User`
A continuación, debes implementar la interfaz `HasEmailAuthentication` en el modelo `User` y usar el **trait** `InteractsWithEmailAuthentication`, que proporciona a **Filament** los métodos necesarios para interactuar con la columna que indica si la autenticación mediante correo electrónico está habilitada:

```php
use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Filament\Auth\MultiFactor\Email\Concerns\InteractsWithEmailAuthentication;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements FilamentUser, HasEmailAuthentication, MustVerifyEmail
{
    use InteractsWithEmailAuthentication;

    // ...
}
```

**Filament** proporciona una implementación predeterminada para mayor rapidez y simplicidad, pero podrías implementar los métodos requeridos tú mismo y personalizar el nombre de la columna o almacenar el valor en una tabla completamente separada.

### 3. Activar la autenticación mediante correo electrónico en el panel
Finalmente, debes activar la función de autenticación mediante correo electrónico en tu panel. Para hacerlo, usa el método `multiFactorAuthentication()` en la configuración y pasa una instancia de `EmailAuthentication`:

```php
use Filament\Auth\MultiFactor\Email\EmailAuthentication;
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->multiFactorAuthentication([
            EmailAuthentication::make(),
        ]);
}
```

---

## Cambiar el tiempo de expiración del código de correo electrónico
Los códigos de correo electrónico se emiten con una **validez de 4 minutos**, después de los cuales expiran.

Para cambiar el período de expiración, por ejemplo, para que sea válido solo durante **2 minutos** después de que se generen los códigos, puedes usar el método `codeExpiryMinutes()` en la instancia de `EmailAuthentication`, estableciéndolo en **2**:

```php
use Filament\Auth\MultiFactor\Email\EmailAuthentication;
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->multiFactorAuthentication([
            EmailAuthentication::make()
                ->codeExpiryMinutes(2),
        ]);
}
```

---

## Requerir autenticación multifactor
Por defecto, los usuarios **no están obligados** a configurar la autenticación multifactor. Puedes obligar a los usuarios a configurarla pasando el parámetro `isRequired: true` al método `multiFactorAuthentication()` en la configuración:

```php
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->multiFactorAuthentication([
            AppAuthentication::make(),
        ], isRequired: true);
}
```

Cuando esta opción está habilitada, los usuarios serán solicitados a configurar la autenticación multifactor después de iniciar sesión, si aún no lo han hecho.

---

## Notas de seguridad sobre la autenticación multifactor
En **Filament**, el proceso de autenticación multifactor ocurre **antes** de que el usuario sea autenticado realmente en la aplicación. Esto te permite estar seguro de que ningún usuario puede autenticarse y acceder a la aplicación sin pasar por el paso de autenticación multifactor. **No necesitas recordar agregar middleware a ninguna de tus rutas autenticadas** para asegurarte de que los usuarios hayan completado el paso de autenticación multifactor.

Sin embargo, si tienes otras partes de tu aplicación **Laravel** que autentican usuarios, ten en cuenta que **no se les solicitará la autenticación multifactor** si ya están autenticados en otro lugar y luego visitan el panel, a menos que la autenticación multifactor sea obligatoria y no la hayan configurado aún.

---

## Envíos concurrentes de códigos de recuperación
Cuando un usuario inicia sesión con un código de recuperación, el método `verifyRecoveryCode()` de **Filament** envuelve la secuencia **lectura-validación-escritura** en:
- Un **bloqueo de caché por usuario** (`Cache::lock`).
- Una **transacción de base de datos** con un bloqueo de fila `lockForUpdate()` en la fila del usuario.

El **bloqueo de caché** serializa los envíos concurrentes en todos los trabajadores de PHP, independientemente del controlador de base de datos subyacente. Por lo tanto, **dos solicitudes de inicio de sesión paralelas no pueden consumir el mismo código ni resucitar un código recién consumido desde una instantánea obsoleta**, incluso cuando el almacenamiento es:
- Un almacenamiento no SQL.
- Una conexión de base de datos diferente.
- Un controlador sin soporte para `SELECT ... FOR UPDATE` (como SQLite).

El **bloqueo de caché** depende de un almacenamiento de bloqueo compartido. El almacenamiento de caché de archivos predeterminado de **Filament**, así como **Redis**, **Memcached**, **base de datos** y **DynamoDB**, proporcionan un bloqueo compartido en los trabajadores de **PHP-FPM** en la misma máquina (o en varias máquinas, para los almacenamientos basados en red). El almacenamiento en **array** es por proceso y no se serializa entre trabajadores: está diseñado solo para pruebas.

Si sobrescribes `getAppAuthenticationRecoveryCodes()` o `saveAppAuthenticationRecoveryCodes()`, el **bloqueo de caché** sigue envolviendo toda la secuencia **lectura-validación-escritura**, por lo que tu sobrescritura está protegida. Tu sobrescritura solo es responsable de hacer que la escritura en el almacenamiento sea atómica. Por ejemplo, una única actualización de **Eloquent** o un primitivo atómico equivalente en tu almacenamiento elegido.
```

---
