# Correo SMTP editable desde el panel

El super administrador (o quien tenga el permiso Shield de la página) configura **host,
puerto, cifrado, usuario, contraseña y remitente** desde **Configuración → Correo**, **sin
editar el `.env` ni tocar código**.

La contraseña se guarda **cifrada** en la base de datos (cast `encrypted` de Laravel, clave
`APP_KEY`). El formulario **nunca** vuelve a mostrar el valor guardado: si el campo se deja
vacío al guardar, se conserva la contraseña anterior.

---

## 1. Resumen

| Pieza | Rol |
|---|---|
| `config/mail.php` + `.env` (`MAIL_*`) | Valores por defecto / respaldo cuando la config de BD está **inactiva** |
| Tabla `mail_settings` | Una sola fila con overrides SMTP y contraseña cifrada |
| `App\Models\MailSetting` | Modelo singleton (`MailSetting::actual()`), password `encrypted` + `$hidden` |
| `App\Services\Configuracion\MailConfigService` | Resuelve BD vs `.env`, cachea, aplica `config()` en runtime |
| `App\Observers\MailSettingObserver` | Invalida caché y reaplica config al guardar/borrar |
| Página `Correo` | UI en cluster Configuración (sort 4), `HasPageShield` |
| `mail:test` | Comando Artisan de prueba; también respeta la config de BD si está activa |

Flujo de una petición (o de un comando Artisan):

```text
AppServiceProvider::boot()
  └─► app->booted()
         └─► MailConfigService::aplicar()
                ├─ activo = false  →  no hace nada (manda .env / config/mail.php)
                └─ activo = true   →  Config::set(mail.*) + purge/forgetMailers
                       └─► Mail::raw / Notification::mail / mail:test
                              usan ya los valores de la BD
```

```text
Admin guarda en Configuración → Correo
  └─► MailSetting::save()
         ├─ password (si viene) → cast encrypted → TEXT en BD
         ├─ MailSettingObserver → olvidar() + aplicar()
         └─ activity('configuracion')  (sin el valor de la contraseña)
```

---

## 2. Qué se ve en el panel

Acceso: **menú de usuario → Configuraciones** (visible si hay acceso a Tema, Marca, Archivos
**o Correo**). El cluster **no aparece en el sidebar**; la sub-navegación izquierda lista:

| Página | Ruta | Contenido |
|---|---|---|
| Tema y colores | `/admin/configuracion/tema-colores` | Temas y colores |
| Marca e identidad | `/admin/configuracion/marca` | Logos y favicon |
| Archivos | `/admin/configuracion/archivos` | Disco, límites, Media Library |
| **Correo** | `/admin/configuracion/correo` | SMTP, credenciales cifradas, prueba de envío |

La página usa `HasPageShield`. Tras desplegar por primera vez:

```bash
vendor/bin/sail artisan migrate
vendor/bin/sail artisan shield:generate --all --panel=admin --no-interaction
```

Asigna el permiso de la página `Correo` al rol que deba administrarla (p. ej. `super_admin`
ya lo obtiene si regeneras con el seeder / `shield:generate`).

### Secciones del formulario

1. **Uso de esta configuración** — toggle *Usar configuración guardada en la base de datos*.
   Si está apagado, el sistema sigue usando el `.env`.
2. **Servidor SMTP** — mailer, host, puerto, scheme, cifrado, exigir TLS.
3. **Credenciales** — usuario y contraseña (campo siempre vacío; helper indica si ya hay una
   guardada).
4. **Remitente** — `from` address/name y dominio EHLO opcional.

Acciones del encabezado:

| Acción | Qué hace |
|---|---|
| **Enviar correo de prueba** | Modal con destinatario; aplica la config y envía un `Mail::raw` |
| **Guardar cambios** | Persiste la fila, reaplica config y registra actividad |

---

## 3. Prioridad de resolución

`MailConfigService` cachea en `mail.resuelto` (forever):

| Situación | Resultado |
|---|---|
| No hay fila / `activo = false` | Manda `config/mail.php` (`.env`) |
| `activo = true` | Manda los campos de `mail_settings` (con fallback a `.env` en campos vacíos) |
| Password en BD | Se usa la de BD (descifrada en memoria) |
| Password vacía en BD | Se usa `MAIL_PASSWORD` del entorno |

Al guardar la página, el observer llama a `olvidar()` + `aplicar()`.

> **Importante:** `aplicar()` solo afecta al proceso PHP actual (web o `artisan`). Si hay un
> **worker de colas** (`queue:work` / contenedor `worker`), reinícialo tras cambiar la
> contraseña para que no siga con mailers resueltos con la config vieja.

---

## 4. Seguridad

| Medida | Detalle |
|---|---|
| Cifrado en reposo | Cast `encrypted` (AES-256-CBC + MAC con `APP_KEY`) |
| Columna `TEXT` | El blob cifrado es más largo que el texto plano |
| `$hidden` | `password` no sale en `toArray()` / JSON del modelo |
| Formulario | El campo password **nunca** se hidrata con el valor guardado |
| Guardar vacío | No sobrescribe la contraseña existente (`dehydrated` solo si `filled`) |
| Activity log | Guarda host/usuario/from y un booleano `password_actualizada`; **nunca** el secreto |
| Shield | Sin permiso de página no se entra (URL directa también denegada) |
| `APP_KEY` | Sigue solo en el entorno. Si se rota, usar `APP_PREVIOUS_KEYS` para no perder el descifrado |

### Qué no hacer

- No escribir el `.env` desde la UI.
- No hashear la contraseña SMTP (`Hash::make` es irreversible; SMTP necesita el valor real).
- No mostrar la clave guardada en Livewire / HTML.
- No registrar la clave en logs, notificaciones ni `mail:test` (solo longitud / metadatos).

---

## 5. Tabla `mail_settings`

Migración: `database/migrations/2026_09_02_154000_create_mail_settings_table.php`.

| Columna | Tipo | Notas |
|---|---|---|
| `activo` | boolean, default `false` | Si `true`, overlay sobre `config/mail.php` |
| `mailer` | string(50), nullable | `smtp`, `log`, `array` (UI) |
| `host` | string(255) | p. ej. `smtp.office365.com`, `sandbox.smtp.mailtrap.io` |
| `port` | unsignedInteger | 587 (STARTTLS), 465 (SMTPS), 2525 (Mailtrap), etc. |
| `scheme` | string(20) | `smtp` o `smtps` |
| `encryption` | string(20), nullable | `tls`, `ssl` o `null` (ninguno) |
| `require_tls` | boolean, nullable | |
| `username` | string(255) | |
| `password` | **text**, nullable | Valor cifrado por Eloquent |
| `from_address` / `from_name` | string(255) | Remitente global |
| `ehlo_domain` | string(255), nullable | `local_domain` del mailer SMTP |

Modelo: `App\Models\MailSetting`.

---

## 6. Archivos clave

| Ruta | Responsabilidad |
|---|---|
| `app/Models/MailSetting.php` | Singleton + casts |
| `app/Services/Configuracion/MailConfigService.php` | Resolver, cache, `aplicar()` |
| `app/Observers/MailSettingObserver.php` | Invalidación al `saved` / `deleted` |
| `app/Filament/Clusters/Configuracion/Pages/Correo.php` | Página Filament |
| `resources/views/filament/clusters/configuracion/pages/correo.blade.php` | Vista mínima (`{{ $this->form }}`) |
| `app/Providers/AppServiceProvider.php` | Singleton, observer, `booted → aplicar()` |
| `app/Providers/Filament/AdminPanelProvider.php` | Enlace del menú Configuraciones incluye Correo |
| `app/Console/Commands/MailTestCommand.php` | `mail:test` (muestra fuente BD o `.env`) |
| `config/mail.php` | Defaults Laravel / `.env` |
| `tests/Feature/Filament/CorreoPageTest.php` | UI, cifrado, conservar password, toggle inactivo |
| `tests/Unit/Services/MailConfigServiceTest.php` | Formulario sin password; `$hidden` |

---

## 7. Cómo usarlo (operación)

### 7.1 Primera vez

1. Migrar y regenerar permisos Shield (ver §2).
2. Entrar a **Configuraciones → Correo**.
3. Completar host, puerto, scheme, cifrado, usuario y contraseña.
4. Activar *Usar configuración guardada en la base de datos*.
5. Guardar.
6. **Enviar correo de prueba** a tu bandeja.
7. Si hay worker de colas, reiniciarlo.

### 7.2 Cambiar solo la contraseña

1. Abrir **Correo**.
2. Escribir la **nueva** contraseña (el resto puede quedarse igual).
3. Guardar.
4. Probar envío / reiniciar worker si aplica.

Si dejas el campo vacío, **no** se borra ni cambia la clave anterior.

### 7.3 Volver al `.env`

Desactiva el toggle *Usar configuración guardada…* y guarda. Los valores en BD se conservan
pero dejan de aplicarse hasta que reactives el toggle.

### 7.4 Probar desde consola

```bash
vendor/bin/sail artisan mail:test alguien@ejemplo.com
```

La tabla del comando incluye la columna **Fuente** (`base de datos` o `.env`).

---

## 8. Valores típicos por proveedor

La UI es **SMTP genérico**: sirve para Microsoft 365, Mailtrap, Gmail (SMTP), SES SMTP, etc.
Solo cambian host/puerto/credenciales.

| Proveedor | Host (ej.) | Puerto | Scheme | Cifrado |
|---|---|---|---|---|
| Microsoft 365 / Outlook | `smtp.office365.com` | `587` | `smtp` | `tls` |
| Mailtrap (sandbox) | `sandbox.smtp.mailtrap.io` | `2525` o `587` | `smtp` | `tls` |
| Gmail (SMTP) | `smtp.gmail.com` | `587` | `smtp` | `tls` |

Notas:

- Con **MFA** en Microsoft, usa una **contraseña de aplicación**, no la del usuario.
- Con cuentas que exigen SMTP AUTH, habilítalo en el tenant / buzón.
- Mailers por **API** (Postmark HTTP, SES API, Resend, etc.) **no** se configuran en esta
  página; seguirían yendo por `.env` / `config/services.php` si se adoptan más adelante.

---

## 9. Relación con el resto del sistema

- Las **notificaciones críticas por correo** (`CriticalEventNotification`, severidad alta)
  usan el mailer por defecto de Laravel. Si Correo está activo, esos mails salen con la
  config de BD. Ver
  [`../Notificaciones-Criticas-y-Colas/README.md`](../Notificaciones-Criticas-y-Colas/README.md).
- Reset de contraseña / correos de Filament Breezy también pasan por el mismo mailer.
- Tema, marca y archivos siguen el mismo patrón de **fila única + servicio + página en el
  cluster**; Correo añade cifrado porque guarda un secreto. Ver
  [`../Configuracion-Tema-y-Marca/README.md`](../Configuracion-Tema-y-Marca/README.md) y
  [`../Archivos-y-Media-Library/README.md`](../Archivos-y-Media-Library/README.md).

---

## 10. Tests

```bash
vendor/bin/sail artisan test --compact \
  tests/Feature/Filament/CorreoPageTest.php \
  tests/Unit/Services/MailConfigServiceTest.php \
  tests/Feature/MailTestCommandTest.php
```

Cubren, entre otros:

- La página carga **sin** filtrar la contraseña en el HTML.
- Al guardar, el valor en BD **no** es texto plano y Eloquent lo descifra bien.
- Guardar con password vacío **conserva** la anterior.
- Con `activo = false`, `aplicar()` **no** sobrescribe el `.env`.
- Acción de prueba notifica éxito con `Mail::fake()`.

---

## 11. Checklist de despliegue

1. `vendor/bin/sail artisan migrate`
2. `vendor/bin/sail artisan shield:generate --all --panel=admin --no-interaction` (o asignar
   permiso de página manualmente)
3. Configurar SMTP en la UI y activar el toggle **o** dejar `activo = false` y seguir con
   `MAIL_*` en el entorno
4. Probar con el botón de la página o `mail:test`
5. Reiniciar workers de cola si están en marcha
6. Verificar que `APP_KEY` de producción está respaldada (sin ella no se descifra la password)
