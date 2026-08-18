
---


# Documentación
**Versión:**
[Seleccionar versión de la documentación](#)
- 3.x

---

## Características y Capturas de Pantalla
- **Mi Perfil**: Información personal con soporte para avatares.
  ![Captura de pantalla del perfil con soporte para avatares](#)
- **Actualizar contraseña**: Con reglas de validación personalizables.
  ![Captura de pantalla de la actualización de contraseña](#)
- **Autenticación de Dos Factores (2FA)**: Con códigos de recuperación.
  ![Captura de pantalla de los códigos 2FA](#)
- **Acción de confirmación de contraseña**: Protege acciones sensibles con un modal de confirmación.
  ![Captura de pantalla de la acción de confirmación de contraseña](#)
- **Autenticación de Dos Factores (2FA)**: Obliga al usuario a habilitar 2FA antes de usar la aplicación.
  ![Captura de pantalla de la obligación de 2FA](#)
- **Gestión de tokens de acceso personal de Sanctum**: Crear y gestionar tokens.
  ![Captura de pantalla de la gestión de tokens de Sanctum](#)
- **Sesiones del navegador**: Gestionar sesiones activas y cerrar sesión en otros dispositivos.
  ![Captura de pantalla de las sesiones del navegador](#)
  ![Captura de pantalla de la confirmación de cierre de sesión](#)
- **Claves de acceso (Passkeys)**: Autenticación sin contraseña.
  ![Captura de pantalla de las claves de acceso](#)

---

## Descripción
**Características mejoradas de seguridad para paneles de Filament (v4).**
Incluye una página "Mi Perfil" personalizable con:
- Información personal y soporte para avatares.
- Actualización de contraseña.
- Autenticación de dos factores (2FA).
- Autenticación con claves de acceso (Passkeys).
- Gestión de tokens de Sanctum.
¡Instalación en minutos!

---

## 📌 Instalación

### 1. Instalar el paquete vía Composer
```bash
composer require jeffgreco13/filament-breezy
php artisan breezy:install
```

### 2. Agregar Breezy a tu Panel de Filament
Agrega `BreezyCore` al método `plugin()` o `plugins()` de tu panel:

```php
use Jeffgreco13\FilamentBreezy\BreezyCore;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            BreezyCore::make()
        ]);
}
```

### 3. Integrar clases de Tailwind
Filament recomienda crear un tema personalizado para soportar mejor las clases adicionales de Tailwind.
Después de crear tu tema, agrega las vistas de Breezy a tu archivo `theme.css` (generalmente en `resources/css/filament/admin/theme.css`):

```css
@source '../../../../vendor/jeffgreco13/filament-breezy/resources/**/*';
```

### 4. (Opcional) Publicar las vistas
```bash
php artisan vendor:publish --tag="filament-breezy-views"
```

---

## 🔧 Uso y Configuración

---

### Actualizar el guard de autenticación
Breezy usará el `authGuard` configurado en el Panel de Filament.
Puedes actualizarlo según tus necesidades:

```php
use Jeffgreco13\FilamentBreezy\BreezyCore;

class CustomersPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->authGuard('customers')
            ->plugin(
                BreezyCore::make()
            );
    }
}
```
**Nota:** Asegúrate de que el modelo utilizado en tu `Guard` extienda la clase `Authenticatable`.

---

### Configurar la página "Mi Perfil"
Habilita la página "Mi Perfil" con opciones de configuración:

```php
BreezyCore::make()
    ->myProfile(
        shouldRegisterUserMenu: true,       // Establece el enlace "cuenta" en el menú de usuario (por defecto: true)
        userMenuLabel: 'Mi Perfil',         // Personaliza la etiqueta del enlace (por defecto: null)
        shouldRegisterNavigation: false,     // Agrega un elemento de navegación principal (por defecto: false)
        navigationGroup: 'Configuración',   // Grupo de navegación (por defecto: null)
        hasAvatars: false,                   // Habilita el componente de subida de avatares (por defecto: false)
        slug: 'mi-perfil'                    // Establece el slug para la página (por defecto: 'my-profile')
    )
```

---

### Clase personalizada para "Mi Perfil"
Puedes usar una clase personalizada extendiendo la predeterminada:

```php
BreezyCore::make()
    ->myProfile()
    ->customMyProfilePage(AccountSettingsPage::class);
```

---

### Usar avatares en tu Panel
Las instrucciones para usar avatares personalizados se encuentran en la documentación de **Filament v4** bajo *"Configuración de avatares de usuario"*.

Ejemplo de implementación:

```php
use Illuminate\Support\Facades\Storage;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url ? Storage::url($this->avatar_url) : null;
    }
}
```

---

### Personalizar el componente de subida de avatares
```php
use Filament\Forms\Components\FileUpload;

BreezyCore::make()
    ->avatarUploadComponent(fn($fileUpload) => $fileUpload->disableLabel())
    // O reemplazar con tu propio componente:
    ->avatarUploadComponent(fn() => FileUpload::make('avatar_url')->disk('profile-photos'));
```

---

### Agregar columna a la tabla de usuarios
Si deseas usar avatares, crea una columna `avatar_url` en la tabla `users`:

1. Crea una migración:
   ```bash
   php artisan make:migration add_avatar_url_column_to_users_table
   ```

2. Define la migración:
   ```php
   use Illuminate\Database\Migrations\Migration;
   use Illuminate\Database\Schema\Blueprint;
   use Illuminate\Support\Facades\Schema;

   return new class extends Migration
   {
       public function up(): void
       {
           Schema::table('users', function (Blueprint $table) {
               $table->string('avatar_url')->nullable();
           });
       }

       public function down(): void
       {
           Schema::table('users', function (Blueprint $table) {
               $table->dropColumn('avatar_url');
           });
       }
   };
   ```

3. Agrega `avatar_url` al modelo `User`:
   ```php
   protected $fillable = [
       'avatar_url',
       // ... otros campos
   ];
   ```

---

### Personalizar la actualización de contraseña
Por defecto, Breezy usa las reglas de contraseña de Laravel.
Puedes personalizar las reglas de validación:

```php
use Illuminate\Validation\Rules\Password;

BreezyCore::make()
    ->passwordUpdateRules(
        rules: [Password::default()->mixedCase()->uncompromised(3)], // Array de reglas
        requiresCurrentPassword: true, // Si es false, el usuario puede actualizar sin ingresar su contraseña actual (por defecto: true)
    );
```

---

### Excluir componentes predeterminados de "Mi Perfil"
Si no deseas usar un componente predeterminado:

```php
BreezyCore::make()
    ->withoutMyProfileComponents([
        'update_password'
    ]);
```

---

### Crear componentes personalizados para "Mi Perfil"
1. Crea un nuevo componente Livewire:
   ```bash
   php artisan make:livewire MyCustomComponent
   ```

2. Extiende la clase `MyProfileComponent`:
   ```php
   use Jeffgreco13\FilamentBreezy\Livewire\MyProfileComponent;
   use Filament\Forms\Components\TextInput;
   use Filament\Schemas\Schema;

   class MyCustomComponent extends MyProfileComponent
   {
       protected string $view = "livewire.my-custom-component";
       public array $only = ['my_custom_field'];
       public array $data;
       public $user;
       public $userClass;

       public function mount()
       {
           $this->user = Filament::getCurrentPanel()->auth()->user();
           $this->userClass = get_class($this->user);
           $this->form->fill($this->user->only($this->only));
       }

       public function form(Schema $schema): Schema
       {
           return $schema
               ->components([
                   TextInput::make('my_custom_field')->required()
               ])
               ->statePath('data');
       }

       public function submit(): void
       {
           $data = collect($this->form->getState())->only($this->only)->all();
           $this->user->update($data);
           Notification::make()
               ->success()
               ->title(__('¡Componente personalizado actualizado con éxito!'))
               ->send();
       }
   }
   ```

3. En la vista de tu componente, usa el componente Blade de Filament:
   ```blade
   <x-filament::section :aside="true" heading="Tu título" description="Descripción">
       <form wire:submit.prevent="submit" class="space-y-6">
           {{ $this->form }}
           <div class="text-right">
               <x-filament::button type="submit" form="submit" class="align-right">
                   ¡Enviar!
               </x-filament::button>
           </div>
       </form>
   </x-filament::section>
   ```

4. Registra tu componente con Breezy:
   ```php
   use App\Livewire\MyCustomComponent;

   BreezyCore::make()
       ->myProfileComponents([MyCustomComponent::class]);
   ```

---

### Sobrescribir componentes de "Mi Perfil"
Puedes reemplazar componentes existentes:

```php
use App\Livewire\MyCustomComponent;

BreezyCore::make()
    ->myProfileComponents([
        'update_password' => MyCustomComponent::class, // Reemplaza el componente de actualización de contraseña
        // 'two_factor_authentication' => ,
        // 'sanctum_tokens' => ,
        // 'browser_sessions' => ,
    ]);
```

**⚠️ Importante:**
- Cambiar el correo electrónico en el formulario de información personal requiere que el usuario vuelva a ingresar su contraseña actual.
- Si sobrescribes `getProfileFormComponents()`, asegúrate de incluir `$this->getCurrentPasswordComponent()` en el array devuelto.

---

### Personalizar solo campos y notificaciones
Extiende el componente original de Breezy:

```php
namespace App\Livewire;

use Filament\Forms;
use Filament\Notifications\Notification;
use Jeffgreco13\FilamentBreezy\Livewire\PersonalInfo;

class CustomPersonalInfo extends PersonalInfo
{
    public ?array $only = ['custom_name_field', 'custom_email_field'];

    protected function getProfileFormComponents(): array
    {
        return [
            $this->getNameComponent(),
            $this->getEmailComponent(),
            $this->getCurrentPasswordComponent(),
            $this->getCustomComponent(),
        ];
    }

    protected function getNameComponent(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('custom_name_field')->required();
    }

    protected function getEmailComponent(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('custom_email_field')->required();
    }

    protected function sendNotification(): void
    {
        Notification::make()
            ->success()
            ->title('¡Datos guardados!')
            ->send();
    }
}
```

Luego, regístralo en `BreezyCore::make()->myProfileComponents`.

---

### Ordenar componentes de "Mi Perfil"
Puedes ordenar componentes personalizados estableciendo la propiedad estática `$sort`:

```php
TwoFactorAuthentication::setSort(4);
```

---

## 🔐 Autenticación de Dos Factores (2FA)

### 1. Agregar el trait a tu modelo
```php
use Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, TwoFactorAuthenticatable;
    // ...
}
```

### 2. Habilitar 2FA
```php
BreezyCore::make()
    ->enableTwoFactorAuthentication(
        force: false,               // Obliga al usuario a habilitar 2FA (por defecto: false)
        action: CustomTwoFactorPage::class, // Usar una página 2FA personalizada
        authMiddleware: MustTwoFactor::class, // Personalizar middleware o deshabilitarlo (false)
        scopeToPanel: true,         // Limitar 2FA solo al panel actual (por defecto: true)
    );
```

### 3. Personalizar la página de 2FA
```php
use Jeffgreco13\FilamentBreezy\Pages\TwoFactorPage;

class CustomTwoFactorPage extends TwoFactorPage
{
    protected static string $layout = 'custom.auth.layout.view';
}
```

---

## 🔑 Tokens de Acceso Personal de Sanctum
Desde **Laravel 11.x**, Sanctum ya no está incluido por defecto.
Si no lo tienes instalado, sigue las instrucciones de instalación [aquí](#).

### Habilitar la gestión de tokens:
```php
BreezyCore::make()
    ->enableSanctumTokens(
        permissions: ['mis', 'permisos', 'personalizados'] // Personalizar permisos (por defecto: ["create", "view", "update", "delete"])
    );
```

---

## 🔒 Acción de Confirmación de Contraseña
Este botón solicita al usuario que ingrese su contraseña para acciones sensibles (ej. eliminar).
Usa el mismo tiempo de espera (`password_timeout`) configurado en `config/auth.php`.

```php
use Jeffgreco13\FilamentBreezy\Actions\PasswordButtonAction;
use Filament\Support\Icons\Heroicon;

PasswordButtonAction::make('accion_segura')->action('doSecureAction');

// Personalizar icono, acción, título del modal, etc.
PasswordButtonAction::make('accion_segura')
    ->label('Eliminar')
    ->icon(Heroicon::ShieldCheck)
    ->modalHeading('Confirmación')
    ->action(fn() => $this->doAction());
```

---

## 🌐 Sesiones del Navegador
La función **Sesiones del Navegador** de Sanctum (deshabilitada por defecto) permite a los usuarios:
- Ver sus sesiones activas en diferentes dispositivos.
- Cerrar sesión remota en otros dispositivos.

**Requisito:** El controlador de sesión debe ser `database`.

### Habilitar Sesiones del Navegador
```php
BreezyCore::make()
    ->enableBrowserSessions(condition: true); // Habilitar (por defecto: true)
```

### Ver sesiones activas
En la página de perfil del usuario, se muestran las sesiones activas con:
- Información del dispositivo (navegador y plataforma).
- Dirección IP.
- Última actividad.

### Cerrar sesión en otros navegadores
Los usuarios pueden cerrar sesión en otros dispositivos ingresando su contraseña para confirmar.

### Configuración adicional
Puedes personalizar el componente sobrescribiendo `browser_sessions` en `myProfileComponents`:

```php
BreezyCore::make()
    ->myProfileComponents([
        'browser_sessions' => \App\Livewire\CustomBrowserSessions::class,
    ]);
```

---

## 🔑 Claves de Acceso (Passkeys)
Permite a los usuarios gestionar sus claves de acceso y autenticarse sin contraseña.

### Habilitar Passkeys
```php
BreezyCore::make()
    ->enablePasskeys(
        relyingPartyName: 'Mi Aplicación',    // Nombre de la parte confiable (por defecto: APP_NAME)
        relyingPartyId: 'https://mi-aplicacion.com', // ID de la parte confiable (por defecto: APP_URL)
        relyingPartyIcon: '/logo.png',        // Icono de la parte confiable
        scopeToPanel: true,                   // Limitar a este panel (por defecto: true)
    );
```

### Configuración adicional
Sobrescribe el componente `passkeys` en `myProfileComponents`:

```php
BreezyCore::make()
    ->myProfileComponents([
        'passkeys' => \App\Livewire\CustomPasskeys::class,
    ]);
```

---

## ❓ Preguntas Frecuentes (FAQ)

### ¿Cómo funcionan las sesiones de 2FA en múltiples paneles?
Por defecto, Breezy usa el mismo guard definido en tu Panel (por defecto: `'web'`).
Solo los paneles que hayan registrado el plugin `BreezyCore` tendrán acceso a 2FA.
Si varios paneles usan 2FA y comparten el mismo guard, el usuario solo debe ingresar el código OTP **una vez por sesión**.

### ¿Cómo interactúa 2FA con `MustVerifyEmail`?
Cuando 2FA está configurado correctamente, el usuario será solicitado para el código OTP **antes** de la verificación de correo.

### ¿Cuánto dura la sesión de 2FA?
La sesión de 2FA tiene la misma duración que la sesión de Laravel.
Cuando el usuario cierra sesión o esta expira, deberá ingresar el código OTP nuevamente.

---

## 📈 Actualización
Consulta la guía **[UPGRADING](#)** para instrucciones detalladas de actualización entre versiones principales.

---

## 🧪 Pruebas
```bash
composer test
```

---

## 📜 Registro de Cambios
Consulta **[CHANGELOG](#)** para más información sobre los cambios recientes.

---

## 🤝 Contribuciones
Consulta **[CONTRIBUTING](#)** para detalles sobre cómo contribuir.

---

## 🛡️ Vulnerabilidades de Seguridad
Revisa nuestra **[política de seguridad](#)** para reportar vulnerabilidades.

---

## 🙏 Créditos
- **Jeff Greco**
- **Jacobtims**
- **Todos los colaboradores**

---
## 📄 Licencia
**Licencia MIT (MIT)**. Consulta el archivo **[LICENSE](#)** para más información.
```

---
¿Necesitas que realice alguna modificación o ajuste en este documento? 😊