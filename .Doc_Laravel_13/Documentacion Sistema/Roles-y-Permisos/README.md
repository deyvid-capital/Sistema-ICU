# Roles y permisos (Filament Shield)

El super administrador crea **roles**, les marca **permisos** y se los asigna a los usuarios
desde el panel, **sin tocar código**. Los permisos se aplican a través de las **policies** de
Laravel: si un rol no tiene `ViewAny:Equipo`, el recurso Equipos ni siquiera aparece en la
navegación de ese usuario.

Hoy el sistema ofrece **494 permisos** repartidos en **48 recursos** y **13 páginas**. Esa
escala es la razón de que esta pantalla tenga buscador y filtros propios, y de que el
formulario esté optimizado (§11).

---

## 1. Resumen

| Pieza | Rol |
|---|---|
| `spatie/laravel-permission` | Tablas `roles`, `permissions`, `role_has_permissions`, `model_has_roles` |
| `bezhansalleh/filament-shield` v4 | Genera los permisos y las policies; aporta el recurso de Roles |
| `config/filament-shield.php` | Qué habilidades existen, cómo se nombran, qué se excluye |
| `app/Policies/*.php` (48) | Traducen cada habilidad a un `can('Ability:Modelo')` |
| `App\Filament\Resources\Roles\RoleResource` | Subclase del recurso de Shield: filtros + rendimiento |
| `App\Filament\Resources\Roles\PermissionMatrix` | Catálogo precomputado de permisos y lógica de filtrado |
| `ManageUserSeguridad` (pestaña Roles) | Donde se asigna un rol **a un usuario** |
| `RolesPermissionsSeeder` | Genera permisos y da todos a `super_admin` |

Flujo de una petición cualquiera:

```text
Usuario entra a /admin/equipos
  └─► canAccessPanel()            (acceso habilitado, sin bloqueo, con rol)
        └─► EquipoResource::canViewAny()
              └─► EquipoPolicy::viewAny($user)
                    └─► $user->can('ViewAny:Equipo')
                          └─► roles del usuario → role_has_permissions → permissions
```

Flujo al guardar un rol:

```text
Admin marca checkboxes en Roles y permisos → Guardar
  └─► EditRole::mutateFormDataBeforeSave()
        ├─ lee $this->data (state crudo, incluye secciones ocultas por filtros)
        └─ PermissionMatrix::permissionNamesFromFormState()   ← lista blanca
              └─► afterSave(): resolvePermissions() (1 query) + syncPermissions()
                    └─► PermissionRegistrar::forgetCachedPermissions()
```

---

## 2. Cómo se llega y qué se ve

Acceso: **menú de usuario (arriba a la derecha) → Roles y permisos**. No está en el sidebar
(el plugin va con `registerNavigation(false)`); el enlace solo se muestra si el usuario pasa
`RoleResource::canViewAny()`, es decir si tiene `ViewAny:Role`.

| Ruta | Página |
|---|---|
| `/admin/shield/roles` | Listado de roles (nombre, guard, nº de permisos, última modificación) |
| `/admin/shield/roles/create` | Crear rol |
| `/admin/shield/roles/{id}` | Ver rol (solo lectura) |
| `/admin/shield/roles/{id}/edit` | Editar rol y sus permisos |

El formulario tiene una cabecera (nombre, guard, botones **Marcar todo / Limpiar todo**) y
debajo las pestañas de permisos:

| Pestaña | Contenido | Estado |
|---|---|---|
| **Recursos** | 48 secciones colapsables, una por recurso (badge: 481 permisos) | visible |
| **Páginas** | 13 permisos `View:*` de páginas y clusters | visible |
| Widgets | — | oculta (la app no tiene widgets propios) |
| Personalizados | — | oculta (`custom_permissions` está vacío) |

---

## 3. Guía: asignar permisos a un rol

1. **Menú de usuario → Roles y permisos**.
2. **Nuevo rol** (o **Editar** uno existente).
3. Ponle un nombre en minúsculas y sin espacios: `admin_ti`, `operador`, `auditor`,
   `soporte_mesa`. Deja `guard_name` en **`web`**.
4. En la pestaña **Recursos**, usa el buscador y los filtros (§4) para llegar al recurso que
   te interesa en vez de bajar por las 48 secciones.
5. Despliega la sección del recurso y marca las habilidades. Dentro de cada sección tienes
   **Seleccionar todo / Deseleccionar todo** para las 10 de ese recurso.
6. Si el rol necesita ver un módulo entero del sidebar, revisa también la pestaña
   **Páginas** (§8).
7. **Guardar**. La pantalla vuelve al listado con el nuevo número de permisos.

> Marcar y desmarcar es instantáneo y **no** hace peticiones al servidor: nada se guarda
> hasta que pulsas Guardar. Si te equivocas, sal sin guardar y no pasa nada.

### 3.1 El mínimo para que un rol sirva de algo

Un rol vacío **deja entrar al panel** (basta con tener un rol asignado) pero el usuario no
verá casi nada. La receta mínima de solo lectura sobre un recurso es:

| Habilidad | Por qué |
|---|---|
| `ViewAny:X` | Sin esto el recurso **no aparece** en la navegación ni se puede listar |
| `View:X` | Para abrir la ficha de un registro |

Y para que además pueda trabajar: `Create:X`, `Update:X`, y `Delete:X` si debe borrar.

### 3.2 Marcar todo / Limpiar todo

Los dos botones de la cabecera actúan sobre **todas** las pestañas a la vez (recursos y
páginas), ignorando los filtros activos. Son el atajo para clonar un rol casi total y luego
quitar lo que no toca. Igual que los checkboxes, **no guardan**: hay que pulsar Guardar.

---

## 4. Guía: buscar y filtrar entre 48 recursos

Arriba de la pestaña **Recursos** hay tres controles que se combinan entre sí (AND):

| Control | Qué hace |
|---|---|
| **Buscar recurso** | Filtra por nombre. Busca también en las etiquetas de Filament, así que "celulares" encuentra el recurso `Celular` y "unidad compartida" encuentra `AccUdCompartida` |
| **Módulo** | Deja solo los recursos de un cluster del sidebar (ver tabla abajo) |
| **Estado** | *Con permisos asignados* / *Sin permisos*, según lo que el rol **tiene guardado** |

Vaciar un control quita ese filtro; los tres vacíos muestran las 48 secciones.

### 4.1 Módulos disponibles

| Módulo | Recursos |
|---|---|
| Accesos | AccUdCompartida, AccesoDaza, AccesoInternet, AccesoSharepoint, AccesoSistema, AccesoVirtual, AzureAd |
| Asignación | AsignacionCargador, AsignacionEquipo, EntregaEquipo, MantenimientoEquipo, RecepcionEquipo |
| Celulares | AsigCargadorCel, AsigCelular, CargadorCel, Celular, EntregaCelular, Firmante, RecepcionCelular |
| Equipos | Cargador, Equipo, NotaRecepcionEquipo, NotaSalidaEquipo, ReciboEquipo |
| Inventario | MemoriaRam, Microprocesador, ModeloLaptop, TarjetaVideo |
| Recursos compartidos | Fondo, RecursoVpn, Sistema, SitioSharepoint, Virtual |
| Software y licencias | Licencia, LicenciaUsuario, Software, SoftwareArea, SoftwareExc, SoftwareUsuario |
| Usuarios | Area, Cargo, Movimiento, User, UsuarioExterno |
| Sin módulo | Activity, PdfCelular, PdfEquipo, Role |

### 4.2 El filtro Estado mira lo guardado, no lo marcado

Es deliberado: si evaluara los checkboxes en vivo, una sección desaparecería de la pantalla
en cuanto le quitaras el último permiso, en mitad de la edición. Con el criterio actual la
lista se mantiene estable mientras trabajas, y refleja el nuevo estado **después** de
guardar.

Úsalo así:

- **Auditar un rol**: *Con permisos asignados* → ves de un golpe todo lo que ese rol puede
  tocar, sin ruido.
- **Ampliar un rol**: *Sin permisos* → ves solo lo que le falta.

### 4.3 Filtrar y guardar es seguro

Puedes filtrar, cambiar cosas y guardar: **los permisos de las secciones ocultas se
conservan**. Está cubierto por un test específico
(`test_guardar_con_un_filtro_activo_conserva_los_permisos_ocultos`), porque el
comportamiento por defecto de Filament sería el contrario: un componente oculto se excluye
del guardado. Ver §11.2 si vas a tocar ese código.

---

## 5. Guía: asignar un rol a un usuario

Los roles se asignan **desde el usuario**, no desde el rol:

1. **Usuarios → Usuarios** → abre el usuario.
2. Pestaña **Seguridad** → sub-pestaña **Roles**.
3. Selecciona uno o varios roles (el select es múltiple y buscable) y guarda.

Detalles importantes:

- La pestaña **Roles solo la ve un `super_admin`**.
- **Sin al menos un rol, el usuario no puede entrar al panel**
  (`User::motivoAccesoDenegado()` lo bloquea con un mensaje claro en el login).
- No puedes quitarte a ti mismo `super_admin` si no hay **otro** super administrador con
  acceso vigente (lo impide `ProteccionSuperAdmin`).
- Un usuario con varios roles **acumula** los permisos de todos ellos.

Ver [`../Registro-de-Actividad/README.md`](../Registro-de-Actividad/README.md): los cambios
de roles y permisos quedan registrados.

---

## 6. Cómo se llaman los permisos

Formato: **`Habilidad:Modelo`** en PascalCase, con `:` de separador
(`config/filament-shield.php` → `permissions.separator` y `case`).

```text
ViewAny:Equipo        ver el listado de equipos
Update:Celular        editar celulares
ForceDeleteAny:User   vaciar la papelera de usuarios
View:EquiposCluster   permiso de página del cluster Equipos
```

Cambiar `separator` o `case` **renombra todos los permisos** y hay que regenerar y
reasignar todo. No se toca.

---

## 7. Qué controla cada habilidad

Cada recurso ofrece **10** habilidades (el recurso `User` ofrece 11, ver §7.2):

| Habilidad | Método de policy | Qué habilita en la interfaz |
|---|---|---|
| `ViewAny` | `viewAny` | Que el recurso **aparezca en la navegación** y se pueda abrir el listado |
| `View` | `view` | Abrir la ficha de un registro (página Ver) |
| `Create` | `create` | Botón *Nuevo* y página de creación |
| `Update` | `update` | Botón *Editar* y página de edición |
| `Delete` | `delete` | Eliminar un registro (va a la papelera si el modelo usa soft deletes) |
| `DeleteAny` | `deleteAny` | Eliminar **en lote** desde la selección múltiple de la tabla |
| `Restore` | `restore` | Restaurar un registro de la papelera |
| `RestoreAny` | `restoreAny` | Restaurar en lote |
| `ForceDelete` | `forceDelete` | Borrado **definitivo** de un registro |
| `ForceDeleteAny` | `forceDeleteAny` | Borrado definitivo en lote |

### 7.1 Papelera: solo aplica a 31 de los 48 recursos

`Restore`, `RestoreAny`, `ForceDelete` y `ForceDeleteAny` solo tienen efecto real en los
modelos con `SoftDeletes`. En los **17** que no la usan esos 4 checkboxes se pueden marcar
pero **nunca se evalúan**:

> Activity, Area, Cargo, Fondo, Licencia, MemoriaRam, Microprocesador, ModeloLaptop,
> PdfCelular, RecursoVpn, Role, Sistema, SitioSharepoint, Software, SoftwareArea,
> TarjetaVideo, Virtual

Marcarlos no hace daño; no marcarlos tampoco quita nada.

### 7.2 Habilidades extra: el caso `ManageSecurity:User`

`ManageSecurity:User` es la 11ª habilidad del recurso `User` y es la que abre la pantalla
**Seguridad** de un usuario (2FA, bloqueos, preferencias de notificación). No es un permiso
"suelto": se declara en la configuración y se fusiona con las 10 estándar.

```php
// config/filament-shield.php
'resources' => [
    'manage' => [
        UserResource::class => ['manageSecurity'],   // + las 10 de policies.methods
    ],
],
```

Ese es el patrón para añadir una habilidad propia a un recurso: declararla ahí, añadir el
método a la policy (`UserPolicy::manageSecurity()`) y usarla en la UI
(`->visible(fn () => $user->can('ManageSecurity:User'))`).

### 7.3 `Replicate` y `Reorder` no existen a propósito

Se eliminaron del sistema (config, 48 policies y base de datos) porque la aplicación no usa
ni `ReplicateAction` ni tablas `reorderable()`: eran 96 permisos y 96 métodos que nunca se
evaluaban y solo ensuciaban esta pantalla.

Si algún día se añade un botón de duplicar o una tabla reordenable:

```php
// config/filament-shield.php → policies.methods
'methods' => [..., 'replicate'],           // y 'reorder' en single_parameter_methods
```

Después hay que **añadir a mano** el método a las policies que lo necesiten y marcar el
permiso nuevo en los roles. Ojo con regenerar en bloque: ver §10.1.

---

## 8. Páginas, clusters y una advertencia

La pestaña **Páginas** tiene 13 permisos:

| Permiso | Se aplica de verdad |
|---|---|
| `View:TemaColores`, `View:Marca`, `View:Archivos`, `View:Correo` | **Sí** — esas páginas usan el trait `HasPageShield` |
| `View:AccesosCluster`, `View:AsignacionCluster`, `View:CelularesCluster`, `View:EquiposCluster`, `View:InventarioCluster`, `View:RecursosCluster`, `View:SoftwareCluster`, `View:UsuariosCluster` | **No** — hoy son decorativos |
| `View:MyProfilePage` | **No** — página de perfil de Breezy |

> **Advertencia comprobada:** los 8 permisos `View:*Cluster` **no gatean nada** todavía. Un
> rol sin `View:EquiposCluster` sigue teniendo `EquiposCluster::canAccess() === true`,
> porque las clases de cluster en `app/Filament/Clusters/` no usan `HasPageShield`. En la
> práctica el cluster se ve pero está vacío: lo que oculta el contenido son los `ViewAny:*`
> de los recursos de dentro.
>
> Si se quiere que el permiso del cluster mande de verdad, hay que añadir a cada clase de
> cluster:
>
> ```php
> use BezhanSalleh\FilamentShield\Traits\HasPageShield;
>
> class EquiposCluster extends Cluster
> {
>     use HasPageShield;
> ```
>
> Es un cambio de una línea por cluster, pero **cambia el comportamiento del sidebar** para
> todos los roles que hoy no tienen esos permisos: hay que asignarlos antes de activarlo.

El cluster **Configuración** no tiene permiso propio (Shield descarta los clusters que son
padres de páginas descubiertas). Su visibilidad depende de las 4 páginas de dentro; ver
[`../Configuracion-Tema-y-Marca/README.md`](../Configuracion-Tema-y-Marca/README.md) y
[`../Correo-SMTP/README.md`](../Correo-SMTP/README.md).

---

## 9. Los roles del sistema

| Rol | Para qué |
|---|---|
| `super_admin` | Todos los permisos. Es el rol que administra roles, usuarios y configuración |
| `admin_ti` | Rol de negocio, se le asignan permisos a mano |
| `operador` | Rol de negocio, se le asignan permisos a mano |
| `auditor` | Rol de negocio, se le asignan permisos a mano |

Los tres últimos los crea **vacíos** `RolesPermissionsSeeder`; sus permisos se asignan por
la pantalla de Roles y **no se reproducen con `db:seed`**. Si quieres que una plantilla de
rol sea reproducible en otro entorno, añádele los permisos al seeder.

### 9.1 `super_admin` NO es un atajo mágico

En este proyecto `super_admin.define_via_gate` está en **`false`**
(`config/filament-shield.php`). Eso significa que **no** hay un `Gate::before` que le
conceda todo: `super_admin` puede porque tiene las 494 filas en
`role_has_permissions`, una por una.

Consecuencia práctica y fácil de olvidar:

> **Cuando se añade un recurso nuevo al sistema, `super_admin` no lo ve hasta que se
> generan los permisos y se le vuelven a sincronizar.** Ver §10.

### 9.2 `panel_user`

`config/filament-shield.php` trae `panel_user.enabled = true`, pero el rol no existe en la
base de datos y ninguna clase usa `HasPanelShield`: hoy es configuración inerte. El acceso
al panel lo decide `User::canAccessPanel()`, que solo exige **tener algún rol**.

---

## 10. Cuando agregas un recurso nuevo

Después de crear un `XResource` y su modelo:

```bash
# 1. Genera los permisos y la policy del recurso nuevo,
#    sin tocar las 48 policies que ya existen
vendor/bin/sail artisan shield:generate --all --panel=admin \
  --ignore-existing-policies --no-interaction

# 2. Limpia la caché de permisos
vendor/bin/sail artisan permission:cache-reset

# 3. Formatea la policy recién creada
vendor/bin/sail bin pint --dirty
```

3. Entra a **Roles y permisos → super_admin**, busca el recurso nuevo (aparecerá con
   *Estado: sin permisos*), márcale todo y guarda. O, más rápido, **Marcar todo → Guardar**.
4. Repite para los roles de negocio que deban verlo.

### 10.1 Nunca regeneres policies sin `--ignore-existing-policies`

`shield:generate` **sobrescribe** el archivo de cada policy que genera
(`copyStubToApp`), y `--ignore-existing-policies` es lo único que hace que salte las que ya
existen. Como solo salta las **existentes**, un recurso nuevo sí obtiene su policy: es el
flag correcto siempre.

Qué se rompe si lo omites: Shield regenera `UserPolicy::manageSecurity()` con **dos**
parámetros (`manageSecurity(AuthUser $authUser, User $user)`), porque `manageSecurity` no
está en `policies.single_parameter_methods`. Pero la app lo comprueba contra la **clase**,
no contra un registro:

```php
// app/Filament/Resources/Users/Tables/UsersTable.php y ManageUserSeguridad.php
auth()->user()?->can('manageSecurity', User::class)
```

Con la firma de dos parámetros esa comprobación falla, y con ella la pantalla **Seguridad**
del usuario y las acciones de la tabla de usuarios. La firma correcta es la actual, de un
solo parámetro.

> Si quieres blindarlo, añade `'manageSecurity'` a `policies.single_parameter_methods` en
> `config/filament-shield.php`: no cambia nada en ejecución (esa lista solo afecta a la
> generación) y hace que cualquier regeneración futura produzca la firma correcta.

Alternativa a los pasos 3-4 en entornos limpios:

```bash
vendor/bin/sail artisan db:seed --class=RolesPermissionsSeeder
```

Ese seeder regenera permisos y le vuelve a dar **todos** a `super_admin` (los roles de
negocio los deja como estén, pero si la tabla `permissions` se regenera de cero, las
asignaciones de todos los roles se pierden por la FK en cascada; ver §16).

---

## 11. Rendimiento: por qué el recurso está sobrescrito

Con 494 permisos, el formulario del paquete tardaba de más. Las causas eran algorítmicas, y
están corregidas en `RoleResource` y `PermissionMatrix`:

| | Paquete | Ahora |
|---|---|---|
| Hidratar el formulario | **1752 ms** (un `checkPermissionTo()` por permiso, O(n²)) | **2,7 ms** (1 query + `isset`) |
| Guardar un rol | **494 queries** (`firstOrCreate` por permiso) | **1 query** |
| Marcar un checkbox | petición a Livewire + re-render de ~500 checkboxes | **0 peticiones** |

Medido sobre el rol `super_admin` en este proyecto.

### 11.1 Herencia, no `shield:publish`

`app/Filament/Resources/Roles/` contiene **subclases** de las clases de Shield, no copias.
`form()`, `table()`, `getSlug()` y las páginas siguen viniendo del paquete y se actualizan
con `composer update`. El panel descubre nuestra clase y Shield deja de registrar la suya
sola, porque `Utils::isResourcePublished()` busca la subcadena `\RoleResource` entre los
recursos del panel.

> **Orden crítico en `AdminPanelProvider`:** `->discoverResources()` debe ir **antes** de
> `->plugins()`. Si se invierte, se registrarían los dos recursos y chocarían en el slug
> `shield/roles`. Lo detecta `route:list --path=shield/roles` (§14) y el test
> `test_el_recurso_publicado_reemplaza_al_del_paquete`.

### 11.2 Qué NO romper si tocas este código

| No hagas | Por qué |
|---|---|
| Volver a poner `->live()` en los CheckboxList | Devuelve el roundtrip y el re-render en cada clic |
| Quitar `->dehydratedWhenHidden()` de la Section o del CheckboxList | Guardar con un filtro activo **borraría** los permisos ocultos |
| Leer el `$data` dehidratado en vez de `$this->data` al guardar | Mismo problema: las secciones ocultas no llegan |
| Filtrar el state por nombre de clave (lista negra) | `$this->data` arrastra claves que no son campos (`updated_at`…) y se guardarían como permisos. Va por lista blanca: `PermissionMatrix::fieldOptions()` |
| Reintroducir el toggle `select_all` del paquete | Es lo que forzaba `->live()` en los ~50 checkbox lists |

El layout (2 columnas, ancho de secciones) se configura con la **API pública del plugin** en
`AdminPanelProvider`, sin código propio:

```php
FilamentShieldPlugin::make()
    ->registerNavigation(false)
    ->gridColumns(['default' => 1, 'lg' => 2])
    ->sectionColumnSpan(1)
    ->resourceCheckboxListColumns(['default' => 1, 'sm' => 2]),
```

---

## 12. Actualizar Filament Shield a futuro

`composer.json` fija `^4.2`, así que un `composer update` normal solo trae 4.x y no afecta a
nada de esto.

En un salto de **major** (v5, v6…):

| Sin riesgo | Riesgo acotado |
|---|---|
| `policies.methods` de la config, las 48 policies, la migración de limpieza, los filtros de UI | Los 4 métodos sobrescritos en `RoleResource` y la forma del array de `FilamentShield::getResources()` que consume `PermissionMatrix` |

Procedimiento recomendado:

1. Leer el `UPGRADE`/`CHANGELOG` del paquete.
2. Comparar los 4 métodos sobrescritos con
   `vendor/bezhansalleh/filament-shield/src/Traits/HasShieldFormComponents.php`.
3. Correr `tests/Feature/Filament/RolePermissionsFormTest.php`: si el contrato cambió, falla
   ahí y no en producción.

Peor caso: reescribir esas dos clases. Y si se quiere volver a Shield puro, se borra
`app/Filament/Resources/Roles/` y se revierten dos imports (`config/filament-shield.php` y
`AdminPanelProvider`); el recurso del paquete se vuelve a registrar solo.

---

## 13. Archivos clave

| Ruta | Responsabilidad |
|---|---|
| `app/Filament/Resources/Roles/RoleResource.php` | Subclase: filtros, hidratación rápida, Marcar/Limpiar todo |
| `app/Filament/Resources/Roles/PermissionMatrix.php` | Catálogo precomputado, filtrado, extracción y resolución de permisos |
| `app/Filament/Resources/Roles/Pages/EditRole.php` | Guardado en bloque leyendo el state crudo |
| `app/Filament/Resources/Roles/Pages/CreateRole.php` | Ídem al crear |
| `app/Filament/Resources/Roles/Pages/ListRoles.php` | Solo apunta al recurso |
| `app/Filament/Resources/Roles/Pages/ViewRole.php` | Solo apunta al recurso |
| `config/filament-shield.php` | Habilidades, nomenclatura, exclusiones, super admin |
| `app/Policies/*.php` | 48 policies, 10 métodos cada una (11 en `UserPolicy`) |
| `app/Providers/Filament/AdminPanelProvider.php` | Plugin, layout, enlace del menú de usuario |
| `app/Filament/Resources/Users/Pages/ManageUserSeguridad.php` | Pestaña Roles (asignar rol a usuario) |
| `app/Support/Seguridad/ProteccionSuperAdmin.php` | Protecciones del último super admin |
| `database/seeders/RolesPermissionsSeeder.php` | Genera permisos y da todos a `super_admin` |
| `database/migrations/*_create_permission_tables.php` | Tablas de Spatie (FK en cascada) |
| `database/migrations/*_remove_replicate_reorder_permissions.php` | Limpieza de los 96 permisos muertos |
| `tests/Feature/Filament/RolePermissionsFormTest.php` | 11 tests de la pantalla |

---

## 14. Comandos de referencia

```bash
# Generar permisos y las policies que falten (sin tocar las existentes)
vendor/bin/sail artisan shield:generate --all --panel=admin \
  --ignore-existing-policies --no-interaction

# Solo permisos, sin tocar policies existentes
vendor/bin/sail artisan shield:generate --all --panel=admin \
  --option=permissions --ignore-existing-policies --no-interaction

# Dar el rol super_admin a un usuario
vendor/bin/sail artisan shield:super-admin --user=1 --panel=admin

# Limpiar la caché de permisos de Spatie (tras cambios por consola o SQL)
vendor/bin/sail artisan permission:cache-reset

# Comprobar que solo está registrado nuestro RoleResource
vendor/bin/sail artisan route:list --path=shield/roles

# Recuento rápido
vendor/bin/sail artisan tinker --execute 'foreach (Spatie\Permission\Models\Role::withCount("permissions")->get() as $r) { printf("%-14s %d\n", $r->name, $r->permissions_count); }'
```

---

## 15. Tests

```bash
vendor/bin/sail artisan test --compact tests/Feature/Filament/RolePermissionsFormTest.php
```

Cubren, entre otros:

- Las tres páginas (listado, editar, ver) **renderizan** con los 494 permisos.
- Hidratar el formulario **no** hace una query por permiso.
- Guardar **no** escala las queries con el número de permisos.
- **Guardar con un filtro activo conserva los permisos ocultos** (la regresión peligrosa).
- El buscador y el filtro de estado ocultan y muestran las secciones correctas.
- *Marcar todo* / *Limpiar todo* actualizan el formulario y persisten.
- Crear un rol guarda **solo** los permisos marcados (y ningún valor de los filtros).
- No existen permisos ni métodos de policy `Replicate`/`Reorder`.
- Nuestro `RoleResource` reemplaza al del paquete y conserva el slug.

---

## 16. Problemas comunes

| Síntoma | Causa probable | Solución |
|---|---|---|
| Añadí un recurso y no aparece en el sidebar, ni siendo super_admin | Los permisos nuevos no existen o no están asignados (§9.1) | `shield:generate` + marcarlos en el rol |
| Cambié permisos por consola/SQL y la UI sigue igual | Caché de Spatie | `permission:cache-reset` |
| Un rol perdió todos sus permisos de golpe | Se regeneró la tabla `permissions` desde cero (p. ej. `migrate:fresh --seed`); las filas de `role_has_permissions` caen por FK en cascada | Reasignar por la UI; para que sea reproducible, dejar los permisos del rol en el seeder |
| El usuario no puede entrar: "no tiene un rol asignado" | Ningún rol asignado | Usuarios → Seguridad → Roles (§5) |
| No veo la pestaña Roles en un usuario | Solo la ve `super_admin` | Entrar con un super administrador |
| No puedo quitarme `super_admin` | Es el único super admin con acceso vigente | Dar `super_admin` a otro usuario primero |
| Guardé y no cambió nada | El formulario no guarda al marcar: hay que pulsar **Guardar** | Volver a marcar y guardar |
| Un rol tiene el permiso del cluster pero no ve nada dentro | Lo que oculta el contenido son los `ViewAny:*` de los recursos, no el permiso del cluster (§8) | Asignar los `ViewAny:` de los recursos |
| La pestaña Widgets o Personalizados no aparece | No hay widgets propios ni `custom_permissions` definidos; Shield oculta las pestañas vacías | Es lo esperado |

---

## 17. Checklist de despliegue

1. `vendor/bin/sail artisan migrate`
2. `vendor/bin/sail artisan shield:generate --all --panel=admin --ignore-existing-policies --no-interaction`
3. `vendor/bin/sail artisan permission:cache-reset`
4. Verificar que `super_admin` tiene **todos** los permisos (§14) y, si no, **Marcar todo →
   Guardar** desde la UI
5. `vendor/bin/sail artisan route:list --path=shield/roles` → las 4 rutas deben apuntar a
   `App\Filament\Resources\Roles\Pages\*`
6. Comprobar que cada usuario activo tiene al menos un rol
7. `vendor/bin/sail artisan optimize:clear` si se cachearon rutas o config
