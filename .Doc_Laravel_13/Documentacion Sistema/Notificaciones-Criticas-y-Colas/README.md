# Notificaciones de eventos críticos + Colas (Redis + worker Docker)

Documentación de la funcionalidad que avisa a los **administradores** (`super_admin` y `admin`)
cuando ocurre una operación sensible en el sistema (asignaciones, eliminaciones, cargadores
dañados, excepciones de software, generación de PDF, etc.), con **enlace directo al recurso**,
por **campana del panel + correo**, procesando el trabajo pesado en **segundo plano** con un
contenedor worker de colas.

---

## 1. Resumen

| Pieza | Rol |
|---|---|
| `App\Support\Notifications\AdminRecipients` | Resuelve los destinatarios (usuarios con rol `super_admin` o `admin`) |
| `App\Support\Notifications\NotifyAdmins` | Punto único de despacho de una notificación crítica |
| `App\Notifications\CriticalEventNotification` | Notification en cola (`database` + `mail`) |
| `App\Observers\*Observer` | Disparadores automáticos por evento de modelo |
| `App\Jobs\GenerateEquipoFormularioPdfJob` | Genera el PDF (Browsershot) en segundo plano |
| Servicio Docker `worker` | Procesa la cola Redis (notificaciones, correos y PDFs) |

Flujo general:

```text
Evento (Observer / Job)
   └─► NotifyAdmins::dispatch(...)
          └─► Notification::send($admins, new CriticalEventNotification)   [ShouldQueue → Redis]
                 ├─ toDatabase()  → campana del panel (Filament)
                 └─ toMail()      → correo (solo severidad ALTA)
```

---

## 2. Canales y severidad

`CriticalEventNotification` implementa `ShouldQueue` y decide sus canales según la severidad:

- **`database`** → siempre. Aparece en la campana del panel con icono, color y un botón
  «Ver en el sistema» que enlaza al recurso.
- **`mail`** → **solo** en eventos de severidad **alta**, para no saturar el correo de los
  administradores.

Constantes disponibles:

```php
CriticalEventNotification::SEVERIDAD_ALTA;   // 'alta'  → database + mail
CriticalEventNotification::SEVERIDAD_MEDIA;  // 'media' → solo database
```

El **actor** que originó el evento se **excluye** de los destinatarios (no se autonotifica).

La entrega por `mail` usa el mailer por defecto de Laravel. Si **Configuración → Correo** está
activo, esos envíos salen con la SMTP de la base de datos (contraseña cifrada). Guía:
[`../Correo-SMTP/README.md`](../Correo-SMTP/README.md). Tras cambiar credenciales, reinicia el
worker de colas para que no conserve mailers viejos.

---

## 3. Eventos cubiertos (Observers)

Los observers se registran en `App\Providers\AppServiceProvider::boot()` y disparan sin importar
si el cambio viene de una página Filament, un RelationManager, una clase Workflow o tinker.

| Observer | Modelo | Evento → Notificación | Severidad |
|---|---|---|---|
| `AsignacionEquipoObserver` | `AsignacionEquipo` | creada / eliminada / eliminada permanentemente | alta |
| `AsignacionCargadorObserver` | `AsignacionCargador` | creada / eliminada | media |
| `AsigCelularObserver` | `AsigCelular` | creada / eliminada (sin enlace: los celulares no tienen Resource) | media |
| `CargadorObserver` | `Cargador` | estado → `Dañado` o `Baja` | alta |
| `EquipoObserver` | `Equipo` | estado → `Baja` | alta |
| `SoftwareExcObserver` | `SoftwareExc` | creada / eliminada (admins) | media |
| `NotifyExcepcionSoftware` | `SoftwareExc` | aprobador asignado o reasignado; excepción aprobada o rechazada | **alta** (campana y correo, con enlace a la ficha) |
| `PdfEquipoObserver` | `PdfEquipo` | eliminada | media |
| `GenerateEquipoFormularioPdfJob` | (Job) | «PDF listo» al terminar / «No se pudo generar» al fallar | media |

> Nota: la notificación de **«PDF listo»** se envía desde el Job (no desde un observer), porque el
> Job corre en el worker sin usuario autenticado y conoce al actor mediante `actorId`.
>
> El flujo de **aprobación de excepciones de software** (quién aprueba, Super Admin, campana +
> correo) está documentado para el usuario en
> [`../Excepciones-Software-Aprobacion/README.md`](../Excepciones-Software-Aprobacion/README.md).

---

## 4. Generación de PDF en segundo plano

Antes, el PDF se generaba **síncrono** dentro de la acción de Filament (Browsershot puede tardar
hasta 120 s, bloqueando la request). Ahora:

1. La acción `generarPdf` de `App\Filament\Resources\Concerns\EquipoFormularioPdfActions`
   despacha `GenerateEquipoFormularioPdfJob` y muestra «Generando PDF…».
2. El worker ejecuta el Job, que reutiliza el servicio existente
   `App\Services\Pdf\GenerateEquipoFormularioPdf` (sin cambios).
3. Al terminar, notifica «PDF listo: …» con enlace al `PdfEquipo` generado.
4. Si falla, `failed()` avisa al solicitante con el mensaje de error.

La **previsualización** (`PdfPreviewController`, rutas `/pdf/preview/*`) **sigue siendo síncrona**,
no se toca.

---

## 5. Infraestructura de colas (Redis + worker)

### `.env`

```env
QUEUE_CONNECTION=redis
REDIS_QUEUE_RETRY_AFTER=210
```

- Se eligió **Redis** (ya corría en `compose.yaml`): 0 RAM extra, descarga a Postgres y latencia
  casi instantánea.
- `REDIS_QUEUE_RETRY_AFTER=210` debe ser **mayor** que el `timeout` del Job de PDF (180 s) para
  evitar que la cola reintente el job mientras aún se está generando el PDF.

### Servicio `worker` en `compose.yaml`

```yaml
worker:
    image: 'sail-8.5/app'
    entrypoint: ["php", "/var/www/html/artisan", "queue:work", "redis", "--sleep=3", "--tries=3", "--backoff=5", "--max-time=3600", "--max-jobs=500"]
    environment:
        WWWUSER: '${WWWUSER}'
        LARAVEL_SAIL: 1
        HOME: /home/sail
    volumes:
        - '.:/var/www/html'
        - 'sail-puppeteer:/home/sail/.cache/puppeteer'
    networks:
        - sail
    depends_on:
        - pgsql
        - redis
    restart: unless-stopped
    mem_limit: 768m
```

Puntos clave:

- **Reutiliza la imagen `sail-8.5/app`** (no se construye una imagen aparte): 0 imagen extra, solo
  el proceso PHP.
- **Corre como root** (igual que `laravel.test`, cuyo `WWWUSER` está vacío). Esto es necesario
  porque Laravel, al compilar un Blade, hace `touch($compilado, $mtime+1)`
  (`Illuminate\View\Compilers\BladeCompiler`), y **fijar el mtime requiere ser dueño del archivo**.
  Con el worker como `sail` (uid 1337) fallaba con `touch(): Utime failed: Operation not permitted`
  al tocar archivos del uid 1000. Root puede tocar cualquier archivo.
- **`HOME=/home/sail`** para que `BrowsershotPdfFactory::resolveChromePath()` encuentre Chrome vía
  `getenv('HOME')` aunque el proceso sea root.
- **Volumen compartido `sail-puppeteer`** (`/home/sail/.cache/puppeteer`) montado también en
  `laravel.test`: Chrome se instala **una sola vez** y ambos contenedores lo usan.
- **`--max-time=3600` / `--max-jobs=500`**: el worker se recicla periódicamente y libera la RAM que
  Chrome (Browsershot) acumula en picos.
- **`mem_limit: 768m`**: holgado para los picos de Chrome.

---

## 6. Puesta en marcha

```bash
# 1. Levantar servicios (crea el worker y el volumen compartido)
vendor/bin/sail up -d

# 2. Instalar Chrome UNA vez en el volumen compartido (lo verán ambos contenedores)
#    Si da EACCES, corrige el dueño del punto de montaje:
docker compose exec -u root laravel.test chown -R sail:sail /home/sail/.cache
vendor/bin/sail bash -c 'npx puppeteer browsers install chrome-headless-shell'

# 3. Si cambiaste .env
vendor/bin/sail artisan config:clear

# 4. Ver el worker en acción
docker compose logs -f worker
```

Tras cambiar código de jobs/notificaciones/observers, reinicia el worker:

```bash
vendor/bin/sail restart worker    # o: docker compose restart worker
```

### Comandos útiles de cola

```bash
vendor/bin/sail artisan queue:failed     # listar jobs fallidos
vendor/bin/sail artisan queue:retry all  # reintentar todos
vendor/bin/sail artisan queue:flush      # borrar los fallidos
```

---

## 7. Cómo añadir un evento crítico nuevo

Gracias al punto único `NotifyAdmins`, agregar un aviso es una sola llamada. Ejemplo dentro de un
observer o servicio:

```php
use App\Notifications\CriticalEventNotification;
use App\Support\Notifications\NotifyAdmins;

NotifyAdmins::dispatch(
    title: 'Título corto del evento',
    body: "Detalle del evento — por {$this->actor()}.",
    url: MiRecursoResource::getUrl('view', ['record' => $registro]), // o null si no aplica
    icon: 'heroicon-o-bell',
    color: 'warning',
    severity: CriticalEventNotification::SEVERIDAD_ALTA, // ALTA = también correo
);
```

Para un modelo nuevo, crea el observer en `app/Observers/` y regístralo en
`AppServiceProvider::boot()` con `MiModelo::observe(MiModeloObserver::class);`.

---

## 8. Producción

En un servidor (Forge / VPS) hay que replicar el worker con **Supervisor** en lugar del contenedor:

```ini
[program:icu-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/forge/tu-sitio/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=forge
numprocs=1
redirect_stderr=true
stdout_logfile=/home/forge/tu-sitio/storage/logs/worker.log
stopwaitsecs=3600
```

- Instala Chrome para el usuario que ejecuta el worker (ver
  [`../Browsershot-PDF-Sail-y-Produccion.md`](../Browsershot-PDF-Sail-y-Produccion.md) §4) y fija
  `BROWSERSHOT_CHROME_PATH` en el `.env` de producción.
- Recuerda `php artisan config:cache` y reiniciar Supervisor tras cada deploy.

---

## 9. Archivos

**Nuevos**

```text
app/Support/Notifications/AdminRecipients.php
app/Support/Notifications/NotifyAdmins.php
app/Notifications/CriticalEventNotification.php
app/Jobs/GenerateEquipoFormularioPdfJob.php
app/Observers/AsignacionEquipoObserver.php
app/Observers/AsignacionCargadorObserver.php
app/Observers/AsigCelularObserver.php
app/Observers/CargadorObserver.php
app/Observers/EquipoObserver.php
app/Observers/SoftwareExcObserver.php
app/Observers/PdfEquipoObserver.php
tests/Feature/CriticalEventNotificationsTest.php
```

**Modificados**

```text
app/Providers/AppServiceProvider.php                          # registro de observers
app/Filament/Resources/Concerns/EquipoFormularioPdfActions.php # despacha el Job
.env / .env.example                                           # QUEUE_CONNECTION=redis + REDIS_QUEUE_RETRY_AFTER
compose.yaml                                                  # servicio worker + volumen sail-puppeteer
```

**Sin tocar:** `PdfPreviewController`, `GenerateEquipoFormularioPdf`, las páginas Create/Edit
(la lógica de aviso vive en los observers).

---

## 10. Pruebas

`tests/Feature/CriticalEventNotificationsTest.php` cubre:

- Que se notifica a los administradores al crear una asignación de equipo.
- Que **no** se notifica al actor que originó el evento.
- Que el correo solo se envía en severidad **alta** (`via()` devuelve `['database','mail']` vs `['database']`).

```bash
vendor/bin/sail artisan test --compact tests/Feature/CriticalEventNotificationsTest.php
```
