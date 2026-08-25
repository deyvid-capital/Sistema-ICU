# Zona horaria: Bolivia (La Paz, GMT-4)

El panel y los correos de prueba muestran la hora de **La Paz**. La base de datos sigue
guardando timestamps en **UTC**. Bolivia no tiene horario de verano: **GMT-4 todo el año**.

---

## 1. Resumen

| Pieza | Valor | Rol |
|---|---|---|
| `config('app.timezone')` | `UTC` | Almacenamiento (`created_at`, `updated_at`, `now()` por defecto) |
| `config('app.display_timezone')` | `America/La_Paz` | Visualización en Filament, timeline de actividad y `mail:test` |
| `FilamentTimezone::set(...)` | `America/La_Paz` | Zona por defecto de tablas, infolists y `DateTimePicker` |

Flujo:

```text
Postgres / Eloquent  →  UTC  (20:57:00)
                         │
                         ▼
FilamentTimezone / dateParser  →  America/La_Paz  (16:57:00)
```

Ejemplo real (lunes 24 de agosto de 2026): un evento guardado a las `20:57` UTC se ve
como **`16:57`** (4:57 p.m. en La Paz).

---

## 2. Por qué no se cambia `APP_TIMEZONE` a La Paz

Si Laravel interpretara los timestamps existentes como `America/La_Paz`, las filas ya
guardadas en UTC se mostrarían **4 horas adelantadas** (el síntoma original).

La práctica correcta de Filament 5 es:

1. Dejar Laravel en **UTC**.
2. Fijar la zona de **visualización** con `FilamentTimezone::set()`.

`DatePicker` (solo fecha, sin hora) **no** aplica zona horaria. Así se evita que un
`2026-08-24` se convierta en `2026-08-23` por el desfase GMT-4.

---

## 3. Configuración

### `.env` / `.env.example`

```env
APP_TIMEZONE=UTC
APP_DISPLAY_TIMEZONE=America/La_Paz
```

### `config/app.php`

```php
'timezone' => env('APP_TIMEZONE', 'UTC'),

'display_timezone' => env('APP_DISPLAY_TIMEZONE', 'America/La_Paz'),
```

En código de la app **nunca** uses `env()`: lee `config('app.display_timezone')`.
`env()` solo vive en archivos de `config/`.

Tras cambiar el `.env`:

```bash
vendor/bin/sail artisan config:clear
```

---

## 4. Cómo se aplica en Filament

En `App\Providers\AppServiceProvider::boot()`:

```php
use Filament\Support\Facades\FilamentTimezone;

FilamentTimezone::set(config('app.display_timezone'));
```

Eso cubre, sin tocar cada recurso:

- columnas `->dateTime()` / `->since()` de tablas
- entradas `->dateTime()` de infolists
- `DateTimePicker` (el usuario elige hora de La Paz; Laravel guarda UTC)

Los `DatePicker` globales siguen en formato `d/m/Y` y **sin** zona, a propósito.

---

## 5. Registro de actividad

El plugin `rmsramos/activitylog` parsea fechas con Carbon por su cuenta. `FilamentTimezone`
no alcanza para la línea de tiempo. Por eso el plugin en
`AdminPanelProvider` convierte explícitamente:

```php
ActivitylogPlugin::make()
    ->dateFormat('d/m/Y')
    ->datetimeFormat('d/m/Y H:i:s')
    ->dateParser(fn (mixed $date): Carbon => Carbon::parse($date)->timezone(config('app.display_timezone')))
    ->customizeDatetimeColumn(fn ($column) => $column->timezone(config('app.display_timezone')))
```

Cómo quedó la auditoría en ICU:
[`../Registro-de-Actividad/README.md`](../Registro-de-Actividad/README.md).
La guía genérica del plugin está en
[`../../FilamentPlugins/activity-logs.md`](../../FilamentPlugins/activity-logs.md).

---

## 6. Correos y código propio

Cualquier hora **visible al usuario** (correo, PDF, comando, Blade) debe convertir:

```php
now()->timezone(config('app.display_timezone'))->format('d/m/Y H:i:s');
```

Hoy lo hace `App\Console\Commands\MailTestCommand`.

Para **comparar o guardar**, sigue usando `now()` / UTC. No mezcles las dos zonas en
queries (`where('created_at', now())` está bien porque ambos lados son UTC).

---

## 7. Qué no hacer

- No pongas `APP_TIMEZONE=America/La_Paz` si los timestamps actuales están en UTC.
- No hardcodees `'America/La_Paz'` en recursos: usa `config('app.display_timezone')`.
- No apliques zona a campos que son **solo fecha** (`fecha_ini`, `fecha_fin` sin hora).
- Bolivia no usa DST: no hace falta lógica de horario de verano.

---

## 8. Archivos

```text
config/app.php                                              # timezone + display_timezone
app/Providers/AppServiceProvider.php                        # FilamentTimezone::set()
app/Providers/Filament/AdminPanelProvider.php               # dateParser / customizeDatetimeColumn
app/Console/Commands/MailTestCommand.php                    # hora visible en mail:test
.env / .env.example                                         # APP_TIMEZONE + APP_DISPLAY_TIMEZONE
phpunit.xml                                                 # mismas vars en testing
tests/Feature/ActivityLogTest.php                           # test_las_horas_se_muestran_en_zona_de_la_paz
```

---

## 9. Pruebas

`tests/Feature/ActivityLogTest.php` comprueba que Filament usa `America/La_Paz` y que
`2026-08-24 20:57:00` UTC se ve como `16:57:00`.

```bash
vendor/bin/sail artisan test --compact --filter=test_las_horas_se_muestran_en_zona_de_la_paz
```
