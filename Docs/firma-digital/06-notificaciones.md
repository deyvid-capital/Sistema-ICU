# 6. Notificaciones

Se reutiliza lo que ya existe:

- La campana de Filament (`->databaseNotifications()`).
- El correo configurado en **Configuración → Correo** (`MailSetting`, que admite Gmail SMTP con contraseña de aplicación).
- Las preferencias por usuario (`NotificationPreference` + `User::debeRecibir()`).
- `AdminRecipients::all()`: roles `super_admin` y `admin`.

## Tipos de evento nuevos

Se agregan a `TipoEventoNotificacion`. Los marcados con 🔒 **siempre** se envían por los dos canales y no se pueden apagar en las preferencias.

| Evento | Destinatarios | Campana | Correo |
|---|---|---|---|
| 🔒 `firma_activacion_pendiente` | Titular al que se le cargó o renovó un `.p12` | ✅ | ✅ |
| 🔒 `firma_requiere_reactivacion` | **Usuario + admins + super admins** (ver abajo) | ✅ | ✅ |
| 🔒 `firma_solicitada` | Orden libre: **todos** los firmantes al enviar. Con orden: el siguiente cuando le toca | ✅ | ✅ |
| `firma_recordatorio` | Firmantes con firmas pendientes | ✅ | ✅ |
| `firma_realizada` | Creador del envío | ✅ | — |
| `firma_completada` | Creador y todos los firmantes, cuando el envío finaliza | ✅ | ✅ (enlace de descarga) |
| `firma_rechazada` | Creador y firmantes del documento | ✅ | ✅ |
| `firma_cancelada` | Firmantes pendientes | ✅ | — |
| `certificado_por_vencer` | Titular y admins (a 30, 15 y 7 días) | ✅ | ✅ |

## Correo de reactivación (contraseña restablecida)

Se dispara cuando la contraseña del sistema cambia **por fuera** de *Mi perfil* ([ver seguridad](03-seguridad.md#cambios-de-contraseña-del-sistema)).

**Al usuario**

> **Asunto:** [ICU] Debes reactivar tu firma digital
>
> Tu contraseña del sistema fue restablecida el 26/10/2026 a las 10:32 (por: recuperación por correo).
> Por seguridad, tu firma digital quedó en pausa hasta que la reactives.
>
> **Cómo reactivarla (2 minutos):**
> 1. Entra al sistema con tu nueva contraseña.
> 2. Ve a **Menú de usuario → Mi firma digital → Reactivar**.
> 3. Paso 1: escribe la contraseña de tu archivo **.p12** (la de Digicert/Viafirma, **no** la del sistema).
> 4. Paso 2: confirma tu **nueva contraseña del sistema**.
>
> Si no recuerdas la contraseña del .p12, comunícate con el administrador.
>
> [ Reactivar mi firma ]

**A admins y super admins**

> **Asunto:** [ICU] Firma digital de Juan Pérez requiere reactivación
>
> - **Usuario:** Juan Pérez (CI 1234567), área Tecnología.
> - **Motivo:** contraseña restablecida por *administrador María Gómez* / *recuperación por correo* / *importación*.
> - **Fecha:** 26/10/2026 10:32.
> - **Firmas pendientes afectadas:** 2 documentos (SF-2026-00042, SF-2026-00047).
>
> **Qué hacer:** el usuario ya recibió las instrucciones. Si necesita ayuda, debe entrar a *Mi firma digital → Reactivar* con la contraseña de su archivo .p12 y su nueva contraseña del sistema. Si perdió la contraseña del .p12, hay que solicitar reposición a Digicert y subir el nuevo archivo en *Configuración → Firmas digitales → Renovar*.
>
> [ Ver firma en el sistema ]

## Clases

- `App\Notifications\Firma\SolicitudFirmaNotification`: una sola clase parametrizada por evento, al estilo de `CriticalEventNotification`. Implementa `ShouldQueue` y `afterCommit()`.
- `App\Notifications\Firma\FirmaRequiereReactivacionNotification`: dos plantillas (usuario y admin) por las diferencias de contenido.
- `toDatabase()` usa `FilamentNotification::make()->...->actions([Action::make('ver')->url(...)])`.
- `toMail()` usa Markdown mail.
  - Asunto del envío a firmar: `"[ICU] Tienes {n} documento(s) por firmar: {asunto}"`.
  - Contenido: mensaje del creador, quién lo envió, fecha límite y el botón **"Revisar y firmar"**.
- El correo **no adjunta los PDFs**, solo lleva el enlace. Así los documentos no salen del sistema.

## Recordatorios (requiere agregar el scheduler)

Hoy el proyecto **no tiene tareas programadas**. Hay que agregar:

1. `routes/console.php`:

```php
Schedule::command('firmas:enviar-recordatorios')->hourly()->withoutOverlapping();
Schedule::command('firmas:avisar-vencimientos')->dailyAt('08:00')->timezone('America/La_Paz');
```

2. `compose.yaml`: un servicio `scheduler` con la misma imagen que `worker` y el comando `php artisan schedule:work`.

**Reglas de `firmas:enviar-recordatorios`:**

- Solo envíos con `recordatorio_cada_horas` definido.
- Solo firmantes con firmas pendientes que **pueden firmar ahora**: en orden libre, todos los pendientes; con orden, solo al que le toca.
- Se envía si `ultimo_recordatorio_at` (o la fecha de envío) tiene más de `recordatorio_cada_horas`.
- Solo entre 08:00 y 19:00, hora de Bolivia, de lunes a viernes.
- Si se pasó la `fecha_limite`, el aviso se marca como **vencido** y se copia al creador.

**`firmas:avisar-vencimientos`:** certificados activos que vencen en 30, 15 o 7 días.
