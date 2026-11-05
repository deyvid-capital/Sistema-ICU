# 6. Notificaciones

Se reutiliza lo que ya existe:

- La campana de Filament (`->databaseNotifications()`).
- El correo configurado en **Configuración → Correo** (`MailSetting`, que admite Gmail SMTP con contraseña de aplicación).
- Las preferencias por usuario (`NotificationPreference` + `User::debeRecibir()`).
- `AdminRecipients::all()`: roles `super_admin` y `admin`.

## Tipos de evento nuevos

Todos se agrupan en un solo tipo, `TipoEventoNotificacion::FirmaDigital`, con un interruptor por canal en *Usuarios → Seguridad → Notificaciones*.

> **Cambio (fase 6):** al principio los eventos marcados con 🔒 se enviaban siempre. Se decidió que **todos** respeten el interruptor, para poder desactivar los correos en pruebas. Al apagar un canal, la pantalla muestra un aviso: no se bloquea ninguna firma, las pendientes siguen en *Pendientes de mi firma* y los avisos omitidos no se reenvían al volver a encenderlo.

| Evento | Destinatarios | Campana | Correo |
|---|---|---|---|
| 🔒 `firma_asignada` | Titular al que el admin activó o renovó un `.p12`: *"Tu firma digital está lista / fue renovada"*. No tiene que activar nada; se vincula en su próximo inicio de sesión | ✅ | ✅ |
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

> **Asunto:** [Sistema ICU] Tu firma digital quedó en pausa
>
> Tu contraseña del sistema fue restablecida el 26/10/2026 a las 10:32 (recuperación por correo).
> Por seguridad, tu firma digital quedó en pausa.
>
> **Para reactivarla** solo inicia sesión con tu **nueva contraseña**: la firma se vuelve a vincular sola y desde ese momento firmas con ella.
>
> Si ya tienes la sesión abierta, ve a **Menú de usuario → Mi firma digital → Reactivar mi firma** y escribe tu nueva contraseña del sistema.
>
> [ Ver mi firma digital ]

**A admins y super admins**

> **Asunto:** [Sistema ICU] Firma digital de Juan Pérez requiere reactivación
>
> - **Usuario:** Juan Pérez (CI 1234567).
> - **Motivo:** contraseña restablecida por *administrador María Gómez* / *recuperación por correo* / *importación*.
> - **Fecha:** 26/10/2026 10:32.
>
> **Qué hacer:** nada. La firma se reactiva sola cuando el usuario inicie sesión con su nueva contraseña.
>
> [ Ver firmas digitales ]

**Firma heredada sin custodia** (activada con el flujo anterior): al usuario se le dice que un administrador debe reactivarla y que no tiene que hacer nada más; a los admins, que entren a *Configuración → Firmas digitales → Reactivar firma* y escriban la contraseña del `.p12`.

## Clases

- `App\Notifications\Firma\SolicitudFirmaNotification`: una sola clase parametrizada por evento, al estilo de `CriticalEventNotification`. Implementa `ShouldQueue` y `afterCommit()`.
- `App\Notifications\Firma\FirmaAsignadaNotification` (antes `FirmaActivacionPendienteNotification`): aviso al titular de firma lista o renovada.
- `App\Notifications\Firma\FirmaRequiereReactivacionNotification`: dos plantillas (usuario y admin) por las diferencias de contenido, cada una con su variante para firmas sin custodia.
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

**Reglas de** `firmas:enviar-recordatorios`**:**

- Solo envíos con `recordatorio_cada_horas` definido.
- Solo firmantes con firmas pendientes que **pueden firmar ahora**: en orden libre, todos los pendientes; con orden, solo al que le toca.
- Se envía si `ultimo_recordatorio_at` (o la fecha de envío) tiene más de `recordatorio_cada_horas`.
- Solo entre 08:00 y 19:00, hora de Bolivia, de lunes a viernes.
- Si se pasó la `fecha_limite`, el aviso se marca como **vencido** y se copia al creador.

`firmas:avisar-vencimientos`**:** certificados activos que vencen en 30, 15 o 7 días.
