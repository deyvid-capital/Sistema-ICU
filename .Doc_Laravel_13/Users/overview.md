

---

# Usuarios
## Vista general

---

## Introducción
Por defecto, todos los usuarios del modelo `App\Models\User` pueden acceder a **Filament** en entornos locales. Para permitirles el acceso en producción, debes seguir algunos pasos adicionales para garantizar que solo los usuarios correctos tengan acceso a la aplicación.

![Vista previa de la página de inicio de sesión predeterminada](#)

---

## Autorizar el acceso al panel
Para configurar tu modelo `App\Models\User` y permitirle el acceso a **Filament** en entornos no locales, debes implementar el contrato `FilamentUser`:

```php
namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements FilamentUser
{
    // ...

    public function canAccessPanel(Panel $panel): bool
    {
        return str_ends_with($this->email, '@tudominio.com') && $this->hasVerifiedEmail();
    }
}
```

El método `canAccessPanel()` devuelve `true` o `false` según si el usuario tiene permiso para acceder al panel `$panel`. En este ejemplo, verificamos si el correo electrónico del usuario termina con `@tudominio.com` y si ha verificado su dirección de correo.

### Acceso condicional a paneles específicos
Como tienes acceso al panel actual (`$panel`), puedes escribir verificaciones condicionales para diferentes paneles. Por ejemplo, restringir el acceso solo al panel de administración mientras permites que todos los usuarios accedan a otros paneles:

```php
namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements FilamentUser
{
    // ...

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return str_ends_with($this->email, '@tudominio.com') && $this->hasVerifiedEmail();
        }

        return true;
    }
}
```

---

## Autorizar el acceso a los Recursos
Consulta la sección **Autorización** en la documentación de **Recursos** para controlar el acceso a las páginas de Recursos y sus registros de datos.

---

## Configurar avatares de usuario
Por defecto, **Filament** utiliza [ui-avatars.com](https://ui-avatars.com) para generar avatares basados en el nombre del usuario. Sin embargo, si tu modelo de usuario tiene un atributo `avatar_url`, este se utilizará en su lugar.

Para personalizar cómo **Filament** obtiene la URL del avatar de un usuario, puedes implementar el contrato `HasAvatar`:

```php
namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    // ...

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url;
    }
}
```

El método `getFilamentAvatarUrl()` se utiliza para obtener el avatar del usuario actual. Si este método devuelve `null`, **Filament** recurrirá a [ui-avatars.com](https://ui-avatars.com).

---

## Usar un proveedor de avatares diferente
Puedes reemplazar fácilmente [ui-avatars.com](https://ui-avatars.com) por otro servicio creando un nuevo proveedor de avatares.

En este ejemplo, creamos un nuevo archivo en `app/Filament/AvatarProviders/BoringAvatarsProvider.php` para [boringavatars.com](https://boringavatars.com). El método `get()` acepta una instancia del modelo de usuario y devuelve una URL de avatar para ese usuario:

```php
namespace App\Filament\AvatarProviders;

use Filament\AvatarProviders\Contracts;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class BoringAvatarsProvider implements Contracts\AvatarProvider
{
    public function get(Model | Authenticatable $record): string
    {
        $name = str(Filament::getNameForDefaultAvatar($record))
            ->trim()
            ->explode(' ')
            ->map(fn (string $segment): string => filled($segment) ? mb_substr($segment, 0, 1) : '')
            ->join(' ');

        return 'https://source.boringavatars.com/beam/120/' . urlencode($name);
    }
}
```

Ahora, registra este nuevo proveedor de avatares en la configuración:

```php
use App\Filament\AvatarProviders\BoringAvatarsProvider;
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->defaultAvatarProvider(BoringAvatarsProvider::class);
}
```

---

## Configurar el atributo de nombre del usuario
Por defecto, **Filament** utilizará el atributo `name` del usuario para mostrar su nombre en la aplicación. Para cambiar esto, puedes implementar el contrato `HasName`:

```php
namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements FilamentUser, HasName
{
    // ...

    public function getFilamentName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
```

El método `getFilamentName()` se utiliza para obtener el nombre del usuario actual.

---

## Características de autenticación
Puedes habilitar fácilmente características de autenticación para un panel en el archivo de configuración:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->login()               // Habilita la página de inicio de sesión
        ->registration()        // Habilita la página de registro
        ->passwordReset()       // Habilita la página de restablecimiento de contraseña
        ->emailVerification()   // Habilita la verificación de correo electrónico
        ->emailChangeVerification() // Habilita la verificación de cambio de correo
        ->profile();            // Habilita la página de perfil
}
```

![Vista previa de la página de registro predeterminada](#)
![Vista previa de la página de restablecimiento de contraseña predeterminada](#)
![Vista previa de la página de perfil predeterminada](#)

**Filament** también soporta **autenticación multifactor**, sobre la cual puedes aprender más en la sección de [Autenticación Multifactor](#).

---

## Personalizar las características de autenticación
Si deseas reemplazar estas páginas con las tuyas propias, puedes pasar cualquier clase de página de **Filament** a estos métodos.

La mayoría de las personas podrán realizar sus personalizaciones extendiendo la clase base de la página del código de **Filament**, sobrescribiendo métodos como `form()`, y luego pasando la nueva clase de página a la configuración:

```php
use App\Filament\Pages\Auth\EditProfile;
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->profile(EditProfile::class);
}
```

En este ejemplo, personalizaremos la página de perfil. Debemos crear una nueva clase PHP en `app/Filament/Pages/Auth/EditProfile.php`:

```php
namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EditProfile extends BaseEditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('username')
                    ->required()
                    ->maxLength(255),
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }
}
```

Esta clase extiende la clase base de la página de perfil del código de **Filament**. Otras clases de página que podrías extender incluyen:
- `Filament\Auth\Pages\Login`
- `Filament\Auth\Pages\Register`
- `Filament\Auth\Pages\EmailVerification\EmailVerificationPrompt`
- `Filament\Auth\Pages\PasswordReset\RequestPasswordReset`
- `Filament\Auth\Pages\PasswordReset\ResetPassword`

En el método `form()` del ejemplo, llamamos a métodos como `getNameFormComponent()` para obtener los componentes de formulario predeterminados de la página. Puedes personalizar estos componentes según sea necesario. Para todas las opciones de personalización disponibles, consulta la clase base `EditProfile` en el código de **Filament**: contiene todos los métodos que puedes sobrescribir para realizar cambios.

---

## Personalizar un campo de autenticación sin redefinir el formulario
Si deseas personalizar un campo en un formulario de autenticación sin necesidad de definir un nuevo método `form()`, puedes extender el método específico del campo y encadenar tus personalizaciones:

```php
use Filament\Schemas\Components\Component;

protected function getPasswordFormComponent(): Component
{
    return parent::getPasswordFormComponent()
        ->revealable(false);
}
```

---

## Verificación de cambio de correo electrónico
Si estás utilizando la función `profile()` junto con `emailChangeVerification()`, los usuarios que cambien su dirección de correo electrónico desde el formulario de perfil deberán verificar su nuevo correo electrónico antes de poder iniciar sesión con él. Esto se hace enviando un correo de verificación a la nueva dirección, que contiene un enlace que el usuario debe hacer clic para verificar su nuevo correo electrónico. **La dirección de correo en la base de datos no se actualiza hasta que el usuario haga clic en el enlace del correo.**

El enlace que se envía al usuario es válido por **60 minutos**. Al mismo tiempo que se envía el correo a la nueva dirección, también se envía un correo a la dirección antigua con un enlace para **bloquear el cambio**. Esto es una medida de seguridad para evitar que un usuario sea afectado por un actor malintencionado.

---

## Usar una barra lateral en la página de perfil
Por defecto, la página de perfil **no utiliza** el diseño estándar de página con barra lateral. Esto es para que funcione con la función de **tenancy** (multitenencia), de lo contrario no sería accesible si el usuario no tiene inquilinos, ya que los enlaces de la barra lateral se enrutan al inquilino actual.

Si **no estás usando tenancy** en tu panel y deseas que la página de perfil utilice el diseño estándar con barra lateral, puedes pasar el parámetro `isSimple: false` al método `$panel->profile()` al registrar la página:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->profile(isSimple: false);
}
```

---

## Personalizar los slugs de las rutas de autenticación
Puedes personalizar los **slugs de URL** utilizados para las rutas de autenticación en la configuración:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->loginRouteSlug('iniciar-sesion')
        ->registrationRouteSlug('registrarse')
        ->passwordResetRoutePrefix('restablecer-contrasena')
        ->passwordResetRequestRouteSlug('solicitar')
        ->passwordResetRouteSlug('restablecer')
        ->emailVerificationRoutePrefix('verificar-correo')
        ->emailVerificationPromptRouteSlug('solicitar-verificacion')
        ->emailVerificationRouteSlug('verificar')
        ->emailChangeVerificationRoutePrefix('verificar-cambio-correo')
        ->emailChangeVerificationRouteSlug('verificar');
}
```

---

## Configurar el guard de autenticación
Para establecer el **guard de autenticación** que utiliza **Filament**, puedes pasar el nombre del guard al método de configuración `authGuard()`:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->authGuard('web');
}
```

---

## Configurar el broker de contraseñas
Para establecer el **broker de contraseñas** que utiliza **Filament**, puedes pasar el nombre del broker al método de configuración `authPasswordBroker()`:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->authPasswordBroker('users');
}
```

---

## Deshabilitar la visualización de contraseñas
Por defecto, todos los campos de contraseña en los formularios de autenticación son `revealable()`. Esto permite al usuario ver una versión de texto plano de la contraseña que está escribiendo al hacer clic en un botón.

Para deshabilitar esta función, puedes pasar `false` al método de configuración `revealablePasswords()`:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->revealablePasswords(false);
}
```

También puedes deshabilitar esta función por campo llamando a `->revealable(false)` en el objeto del campo al extender la clase base de la página.

---

## Configurar el acceso de invitados a un panel
Por defecto, **Filament** espera trabajar solo con usuarios autenticados. Para permitir que los invitados accedan a un panel, debes:
1. Evitar usar componentes que esperen un usuario autenticado (como perfiles, avatares).
2. Eliminar el **middleware de Autenticación** predeterminado:
   - Elimina `Authenticate::class` del array `authMiddleware()` en la configuración del panel.
   - Elimina `->login()` y cualquier otra característica de autenticación del panel.
   - Elimina el widget `AccountWidget` del array `widgets()`, ya que lee los datos del usuario actual.

---

## Autorizar invitados en políticas
Cuando está presente, **Filament** se basa en las **Políticas de Modelo de Laravel** para el control de acceso. Para otorgar acceso de lectura a usuarios invitados en una política de modelo, crea la **Política** y actualiza los métodos `viewAny()` y `view()`, cambiando el parámetro `User $user` a `?User $user` para que sea opcional y devuelva `true`. Alternativamente, puedes eliminar esos métodos de la política por completo.
```
