# Auditoría y registro de actividad

El sistema guarda **quién cambió qué**, en **qué recurso**, **cuándo**, y el **antes / después**
de cada creación, edición, eliminación o restauración de modelos Eloquent.

No registra visitas a páginas ni clics. Solo cambios persistidos por Eloquent (panel Filament,
Relation Managers, tinker, seeders, jobs, etc.).

Paquetes: `rmsramos/activitylog` **v4.0.4** (UI Filament 5) sobre `spatie/laravel-activitylog` **4.12.3**.

La guía genérica del plugin está en
[`../../FilamentPlugins/activity-logs.md`](../../FilamentPlugins/activity-logs.md).
Este documento describe **cómo quedó implementado en ICU**.

---

## 1. Resumen

| Pieza | Rol |
|---|---|
| `App\Models\Concerns\LogsIcuActivity` | Trait único: qué campos se loguean y cuáles se ocultan |
| `Spatie\Activitylog\Traits\CausesActivity` en `User` | Marca al usuario autenticado como *causer* |
| Tabla `activity_log` | Persistencia (morphs **string**, no bigint) |
| `ActivitylogPlugin` | Menú **Auditoría → Registros de actividad** |
| `ActivitylogRelationManager` | Historial en la ficha del registro |
| `App\Filament\Actions\ActivityTimelineAction` | Modal de línea de tiempo en la tabla |
| `App\Policies\ActivityPolicy` | Permisos Shield (`ViewAny:Activity`, etc.) |
| `lang/es/activity.php` | Nombres de modelos y campos en español |

Flujo:

```text
Eloquent create / update / delete / restore
   └─► LogsIcuActivity (solo fillable sucios, sin password)
          └─► activity_log
                 ├─ causer  → User (UUID)
                 ├─ subject → modelo afectado (id o serial)
                 ├─ event   → created | updated | deleted | restored
                 └─ properties JSON → old + attributes (antes / después)
```

---

## 2. Qué se ve en el panel

Con permiso `ViewAny:Activity`:

1. **Navegación** grupo **Auditoría**, icono lista, badge con el conteo.
2. **Listado global**: actor, recurso, evento, fecha/hora (La Paz), valores cambiados.
3. **Ver recurso**: abre el registro Filament del sujeto, si existe.
4. **Restaurar**: visible cuando el sujeto tiene soft delete (`isRestoreModelActionHidden(false)`).
5. En recursos clave: pestaña de historial y botón **Actividades** (timeline).

Recursos con Relation Manager + timeline:

| Recurso | Relation Manager | Timeline en tabla |
|---|---|---|
| Usuarios | sí | sí |
| Equipos | sí | sí |
| Celulares | sí | sí |
| Cargadores de celular | sí | sí |
| Asignaciones de celular | sí | sí |
| Asignaciones de equipo | sí | sí |

El resto de modelos **sí se auditan** (trait en el modelo); solo no tienen la UI extra en su ficha.
Siguen apareciendo en el listado global de Auditoría.

---

## 3. Trait `LogsIcuActivity`

Todos los modelos de dominio lo usan **excepto** `NotificationPreference` (preferencias de
campana/correo: ruido, no auditoría de inventario).

Reglas:

- Si el modelo tiene `$fillable`, loguea esos campos (`logFillable()`).
- Si no, loguea todo (`logAll()`).
- Solo atributos **sucios** (`logOnlyDirty()`).
- No escribe filas vacías (`dontSubmitEmptyLogs()`).
- Un save que solo toca `updated_at` no genera log.
- Nunca guarda `password` ni `remember_token`.

Para excluir más campos en un modelo concreto, overridea el método:

```php
protected function activityLogExcept(): array
{
    return ['password', 'remember_token', 'token_interno'];
}
```

`User` también usa `CausesActivity`: Spatie rellena `causer_type` / `causer_id` con el usuario
autenticado. Sin sesión (tinker, job, seeder) el causer queda `null`.

---

## 4. Base de datos: morphs string

Spatie publica morphs `bigint` por defecto. ICU no puede usarlos:

- `users.id` es **UUID**
- `equipos` usa `service_tag` (**string**) como PK
- otros catálogos mezclan enteros y strings

Las migraciones locales definen `subject_id` y `causer_id` como `string`:

```text
database/migrations/2026_08_24_203613_create_activity_log_table.php
database/migrations/2026_08_24_203614_add_event_column_to_activity_log_table.php
database/migrations/2026_08_24_203615_add_batch_uuid_column_to_activity_log_table.php
```

No regeneres esas migraciones con el stub de Spatie: volverían a `unsignedBigInteger` y
romperían usuarios y equipos.

Otras opciones en `config/activitylog.php`:

- `subject_returns_soft_deleted_models` → `true` (poder restaurar desde el log)
- `delete_records_older_than_days` → `365` (`php artisan activitylog:clean`)

---

## 5. Plugin, traducciones y zona horaria

En `AdminPanelProvider`:

```php
ActivitylogPlugin::make()
    ->label('Registro de actividad')
    ->pluralLabel('Registros de actividad')
    ->navigationGroup('Auditoría')
    ->navigationIcon('heroicon-o-clipboard-document-list')
    ->navigationSort(99)
    ->navigationCountBadge(true)
    ->resourceActionLabel('Ver recurso')
    ->isRestoreModelActionHidden(false)
    ->dateFormat('d/m/Y')
    ->datetimeFormat('d/m/Y H:i:s')
    ->dateParser(fn (mixed $date): Carbon => Carbon::parse($date)->timezone(config('app.display_timezone')))
    ->customizeDatetimeColumn(fn ($column) => $column->timezone(config('app.display_timezone')))
    ->translateSubject(...)
    ->translateLogKey(...)
    ->authorize(fn (): bool => (bool) auth()->user()?->can('ViewAny:Activity'));
```

Las horas se muestran en **La Paz (GMT-4)**. El detalle está en
[`../Zona-Horaria-Bolivia/README.md`](../Zona-Horaria-Bolivia/README.md).

Textos en `lang/es/activity.php`:

- `activity.subjects.*` → nombre del modelo (`user` → usuario, `equipo` → equipo)
- `activity.keys.*` → nombre del campo (`email` → correo, `id_area` → área)

Si falta una clave, Filament usa `Str::headline()` del nombre técnico.

---

## 6. Permisos (Shield)

`Activity` no es un Resource de la app, así que la policy se registra a mano en
`AppServiceProvider`:

```php
Gate::policy(Activity::class, ActivityPolicy::class);
```

`ActivityPolicy` delega en abilities Shield: `ViewAny:Activity`, `View:Activity`,
`Delete:Activity`, `Restore:Activity`, etc.

El menú de Auditoría solo aparece si el usuario tiene `ViewAny:Activity`.
`RolesPermissionsSeeder` corre `shield:generate` y da **todos** los permisos a `super_admin`.

Si la base ya tenía roles sembrados **antes** de instalar el log, regenera permisos:

```bash
vendor/bin/sail artisan shield:generate --all --panel=admin --option=permissions --ignore-existing-policies --no-interaction
```

Luego asigna `ViewAny:Activity` (y los que correspondan) al rol `auditor` u otros, desde
Roles y permisos. El rol `auditor` nace vacío a propósito.

---

## 7. Estilos del timeline

Tailwind v4 no escanea `vendor/` salvo que se lo indiques. Sin esto, el modal de
Actividades sale sin iconos ni línea de tiempo.

En `resources/css/filament/admin/theme.css`:

```css
@source '../../../../vendor/rmsramos/activitylog/resources/**/*';
```

Tras tocarlo:

```bash
vendor/bin/sail pnpm run build
```

(Este proyecto usa **pnpm**, no npm.)

---

## 8. Cómo ampliarlo

### Auditar un modelo nuevo

```php
use App\Models\Concerns\LogsIcuActivity;

class MiModelo extends Model
{
    use LogsIcuActivity;
}
```

Añade la etiqueta en `lang/es/activity.php` → `subjects` (y `keys` si el campo no está).

### Timeline + historial en un Resource

En el Resource:

```php
use Rmsramos\Activitylog\RelationManagers\ActivitylogRelationManager;

public static function getRelations(): array
{
    return [
        ActivitylogRelationManager::class,
    ];
}
```

En la tabla:

```php
use App\Filament\Actions\ActivityTimelineAction;

ActivityTimelineAction::make(),
```

No copies el action del vendor: reutiliza `ActivityTimelineAction` (iconos, colores, límite 20).

---

## 9. Qué no hace

- No loguea lecturas, búsquedas ni exportaciones.
- No loguea `NotificationPreference`.
- No guarda contraseñas ni `remember_token`.
- No sustituye las **notificaciones críticas** (campana / correo a admins). Eso es otro flujo:
  [`../Notificaciones-Criticas-y-Colas/README.md`](../Notificaciones-Criticas-y-Colas/README.md).

---

## 10. Archivos

**Nuevos**

```text
app/Models/Concerns/LogsIcuActivity.php
app/Policies/ActivityPolicy.php
app/Filament/Actions/ActivityTimelineAction.php
lang/es/activity.php
config/activitylog.php
config/filament-activitylog.php
database/migrations/2026_08_24_203613_create_activity_log_table.php
database/migrations/2026_08_24_203614_add_event_column_to_activity_log_table.php
database/migrations/2026_08_24_203615_add_batch_uuid_column_to_activity_log_table.php
tests/Feature/ActivityLogTest.php
```

**Modificados (puntos de enganche)**

```text
app/Providers/AppServiceProvider.php                 # Gate::policy(Activity)
app/Providers/Filament/AdminPanelProvider.php        # ActivitylogPlugin
app/Models/User.php                                  # LogsIcuActivity + CausesActivity
app/Models/*                                         # LogsIcuActivity (salvo NotificationPreference)
resources/css/filament/admin/theme.css               # @source del plugin
User / Equipo / Celular / CargadorCel /
AsigCelular / AsignacionEquipo Resources + Tables    # relation manager + timeline
```

---

## 11. Pruebas

`tests/Feature/ActivityLogTest.php` cubre:

- Crear y actualizar un cargador registra causer, sujeto y antes/después.
- El `password` del usuario **no** entra en `properties`.
- `super_admin` entra al listado de actividad.
- Un usuario sin permiso recibe **403**.
- Las horas se muestran en `America/La_Paz` (`20:57` UTC → `16:57`).

```bash
vendor/bin/sail artisan test --compact tests/Feature/ActivityLogTest.php
```
