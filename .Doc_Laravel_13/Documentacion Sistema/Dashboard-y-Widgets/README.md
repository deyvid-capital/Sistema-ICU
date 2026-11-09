# Dashboard y widgets

El panel abre en un **dashboard propio** (`App\Filament\Pages\Dashboard`) con 8 widgets:
un resumen de cifras, 4 gráficos, 2 tablas de avisos y un panel de usuarios con pestañas.
Cada widget se puede dar o quitar por rol desde **Roles y permisos → Widgets**, salvo
*Mis firmas pendientes*, que es personal.

---

## 1. Resumen

| Pieza | Rol |
|---|---|
| `app/Filament/Pages/Dashboard.php` | Reemplaza al dashboard de Filament; rejilla de 1 columna en móvil y 2 desde `lg` |
| `app/Filament/Widgets/*.php` | Los 8 widgets (se descubren solos) |
| `app/Filament/Concerns/ConstruyeSeriesMensuales.php` | Trait de los gráficos por mes: filtro de periodo, conteo y etiquetas |
| `app/Filament/Widgets/UsuariosPanel/` | Pestañas del panel de usuarios y `ColumnaDeBadges` |
| `resources/views/filament/widgets/usuarios-panel-widget.blade.php` | Barra de pestañas + tabla activa |
| `config/filament-shield.php` | Excluye el Dashboard (página) y `MisFirmasPendientesTable` (widget) |
| `tests/Feature/Filament/DashboardWidgetsTest.php` | Tests de stats, gráficos, tablas y permisos |
| `tests/Feature/Filament/UsuariosPanelWidgetTest.php` | Tests del panel de usuarios |

`AdminPanelProvider` ya no registra el `Dashboard` ni el `FilamentInfoWidget` de Filament;
solo queda el `AccountWidget` (saludo y cerrar sesión).

---

## 2. Qué muestra el dashboard (en orden)

| # | Widget | Tipo | Qué muestra |
|---|---|---|---|
| 1 | `ResumenGeneralStats` | Stats | 6 cifras (ver §3) |
| 2 | `EquiposPorEstadoChart` | Dona | Equipos agrupados por estado, un color fijo por estado |
| 3 | `AltasBajasChart` | Barras | Altas y bajas de usuarios por mes |
| 4 | `UsoLicenciasChart` | Barras horizontales apiladas | Por licencia activa: asignadas, libres y, si hay, *sobre el cupo* (rojo) |
| 5 | `EntregasRecepcionesChart` | Líneas | Entregas y recepciones de equipos por mes |
| 6 | `GarantiasPorVencerTable` | Tabla | Equipos con garantía que vence en los próximos 90 días |
| 7 | `MisFirmasPendientesTable` | Tabla | Documentos en curso que esperan la firma del usuario |
| 8 | `UsuariosPanelWidget` | Panel con pestañas | Usuarios, Licencias, Software y Accesos (ver §5) |

Ningún widget hace *polling*: los datos se calculan al cargar la página.

---

## 3. Resumen general (stats)

| Tarjeta | Valor | Descripción |
|---|---|---|
| Equipos asignados | Equipos en estado *Asignado* | "X% de N equipos" |
| Equipos disponibles | Equipos *Sin asignar* | "X% listos para asignar"; amarillo si es 0 |
| Total de licencias | Suma de `total_licencias` de las licencias activas | "X asignadas · Y libres"; rojo si hay más asignadas que cupos |
| Total de software | Todo el software registrado | "N autorizados" (`estado = true`) |
| Usuarios | Todos los usuarios | "X de alta · Y de baja", según su **último movimiento** |
| Firmas digitales activas | Certificados en estado *Activo* | Amarillo si alguno vence en 30 días |

Un usuario sin movimientos cuenta en el total pero no en "de alta" ni en "de baja".

---

## 4. Gráficos y tablas de avisos

### 4.1 Filtro de periodo

*Altas y bajas* y *Entregas y recepciones* tienen un selector arriba a la derecha:

| Opción | Periodo |
|---|---|
| Últimos 6 meses | 6 meses hasta el actual |
| Últimos 12 meses | por defecto |
| Este año | enero hasta el mes actual |

Un valor desconocido en la URL o en el estado vuelve a 12 meses. El conteo se agrupa en
PostgreSQL con `to_char(fecha, 'YYYY-MM')`, sin paquetes extra.

### 4.2 Garantías por vencer

Equipos con `venc_garantia` entre hoy y hoy + 90 días (`GarantiasPorVencerTable::DIAS_DE_AVISO`).
El badge *Días restantes* es rojo hasta 30 días, amarillo hasta 60 y gris después. El botón
**Ver** abre la ficha del equipo solo si el usuario puede verlo (`EquipoResource::canView`).

### 4.3 Mis firmas pendientes

Solicitudes de firma *En curso* donde el usuario es firmante, su firma está *Pendiente* y el
documento sigue en curso. **Firmar** lleva a la ficha de la solicitud.

Se muestra solo a quien es firmante de algún envío o tiene una firma vigente. No depende de
un rol, por eso está excluido de Shield.

---

## 5. Panel de usuarios (pestañas)

Una barra de pestañas encima de la tabla permite cambiar entre cuatro vistas. Todas
muestran **a todos los usuarios**, estén de alta, de baja o sin movimientos.

| Pestaña | Columnas |
|---|---|
| **Usuarios** | Usuario (correo debajo) · Área y cargo · Movimiento (Alta/Baja) · Rol · 2FA |
| **Licencias** | Usuario · Área y cargo · Licencias activas (badges) |
| **Software** | Usuario · Área y cargo · Software activo (badges) |
| **Accesos** | Usuario · Área y cargo · un badge por tipo: Sistemas, SharePoint, Máquinas virtuales, Unidades compartidas, Internet/Wi-Fi, DAZA, Azure AD, FortiToken |

- **2FA** es el doble factor del propio sistema (el que el usuario activa en *Mi perfil*,
  Breezy). Cuenta solo si está **confirmado** (`two_factor_secret` y
  `two_factor_confirmed_at` no nulos).
- Los badges listan solo asignaciones **activas** (`estado = true`).

### 5.1 Búsqueda y filtros

| Dónde | Busca / filtra por |
|---|---|
| Todas las pestañas | Búsqueda por nombre, nombres, apellidos, correo, carnet y área. Filtros: Área, Cargo, Movimiento (*De alta*, *De baja*, *Sin movimiento*) |
| Usuarios | Filtros Rol y 2FA |
| Licencias | Búsqueda por nombre de licencia; filtro Licencia |
| Software | Búsqueda por nombre de software; filtro Software |
| Accesos | Búsqueda por el nombre de cualquier acceso; filtro *Tiene acceso a* (varios tipos a la vez, con O) |

### 5.2 Columnas responsivas y "Mostrar más"

- *Área y cargo* se oculta en pantallas pequeñas (`visibleFrom('md')`) y *Rol* también en las
  medianas (`visibleFrom('lg')`).
- En Accesos, los tipos menos usados (Unidades, Internet/Wi-Fi, DAZA, Azure AD, FortiToken)
  arrancan ocultos.
- Cualquier columna se muestra u oculta con el selector de columnas de la tabla.
- Cada celda de badges muestra 3 y un enlace **Mostrar N más / Mostrar N menos**. Los badges
  van uno debajo de otro (`listWithLineBreaks()`); sin eso Filament recorta la lista en el
  servidor y "Mostrar más" no tiene nada que desplegar.

### 5.3 Cómo está armado

```text
UsuariosPanelWidget  (permiso View:UsuariosPanelWidget, pestaña en $pestana)
  └─ blade: <x-filament::tabs> + @livewire(pestaña activa)
        └─ PestanaUsuarios (abstracta, TableWidget, no descubierta)
              ├─ consulta de usuarios + Usuario + Área y cargo
              ├─ filtros Área, Cargo, Movimiento, búsqueda, paginación 10/25/50
              └─ UsuariosTab · LicenciasTab · SoftwareTab · AccesosTab
                    └─ ColumnaDeBadges::make(...)  → carga, columna, búsqueda y filtro
```

- Solo se monta la pestaña activa. Cambiar de pestaña reemplaza el componente.
- Una pestaña desconocida (por ejemplo, manipulando el estado de Livewire) vuelve a
  *Usuarios*: solo se aceptan las claves de `UsuariosPanelWidget::PESTANAS`.
- Las asignaciones se cargan por adelantado por página, filtradas por `estado = true`, sin
  consultas N+1.

### 5.4 Agregar una pestaña o un tipo de badge

**Nueva pestaña:**

1. Crear una clase en `app/Filament/Widgets/UsuariosPanel/` que extienda `PestanaUsuarios`.
2. Implementar `getEtiqueta()` y `getIcono()`, y sobrescribir los ganchos que necesite:
   `columnasDeBadges()`, `relaciones()`, `columnasPropias()`, `filtrosPropios()`.
3. Agregarla a `UsuariosPanelWidget::PESTANAS`.

No hace falta tocar permisos: hereda `View:UsuariosPanelWidget`.

**Nuevo tipo de badge** (por ejemplo, un acceso nuevo): una línea en `columnasDeBadges()`.

```php
ColumnaDeBadges::make(
    nombre: 'vpn',
    etiqueta: 'VPN',
    relacion: 'accesosVpn',          // HasMany del usuario con columna `estado`
    atributo: 'recurso.nombre_vpn',  // admite notación de punto
    color: 'info',
    ocultaPorDefecto: true,
),
```

De esa línea salen la carga anticipada, la columna, su búsqueda y su opción en el filtro
*Tiene acceso a*.

---

## 6. Permisos

| Widget | Permiso |
|---|---|
| Resumen general | `View:ResumenGeneralStats` |
| Equipos por estado | `View:EquiposPorEstadoChart` |
| Altas y bajas | `View:AltasBajasChart` |
| Uso de licencias | `View:UsoLicenciasChart` |
| Entregas y recepciones | `View:EntregasRecepcionesChart` |
| Garantías por vencer | `View:GarantiasPorVencerTable` |
| Panel de usuarios (las 4 pestañas) | `View:UsuariosPanelWidget` |
| Mis firmas pendientes | Ninguno: se basa en ser firmante (§4.3) |

Se asignan en **Roles y permisos → pestaña Widgets**. Sin el permiso, el widget no aparece.
Las pestañas del panel no tienen permiso propio (`$isDiscovered = false`): un solo permiso
cubre todo el panel.

> **Importante:** `define_via_gate` está en `false`, así que `super_admin` **no** se salta
> Shield. Tras crear un widget nuevo hay que generar el permiso y asignarlo:
>
> ```bash
> vendor/bin/sail artisan db:seed --class=RolesPermissionsSeeder
> ```

---

## 7. Tests

```bash
vendor/bin/sail artisan test --compact tests/Feature/Filament/DashboardWidgetsTest.php tests/Feature/Filament/UsuariosPanelWidgetTest.php
```

Cubren, entre otros:

- Cifras del resumen (equipos, licencias, usuarios de alta/baja, firmas por vencer).
- Datos de cada gráfico y el regreso a 12 meses con un filtro inválido.
- Garantías dentro y fuera de los 90 días; firmas pendientes solo del usuario.
- Que cada widget exige su permiso y *Mis firmas pendientes* solo se ve siendo firmante.
- Cambio de pestaña, pestaña inválida y permiso único del panel.
- Todos los usuarios visibles, filtros, búsqueda, 2FA confirmado vs. sin confirmar.
- Badges solo de asignaciones activas, "Mostrar N más / menos" con más de 3 badges y
  columnas ocultas que se pueden activar.

En los tests hay que crear y asignar los permisos `View:<Widget>` a mano (no se corre el
seeder).

---

## 8. Problemas comunes

| Síntoma | Causa probable | Solución |
|---|---|---|
| Un widget nuevo no aparece, ni siendo super_admin | Falta su permiso `View:*` | Correr `RolesPermissionsSeeder` (§6) |
| Un widget da 403 en tests | El test no asignó el permiso | Crear y dar `View:<Widget>` en `setUp` |
| "Mostrar más" no despliega nada | La columna no usa `listWithLineBreaks()` | Usar `ColumnaDeBadges`, que ya lo incluye |
| Un usuario no cuenta como de alta ni de baja | No tiene movimientos | Es lo esperado; aparece con el filtro *Sin movimiento* |
| El 2FA sale apagado aunque el usuario lo inició | No confirmó el código en *Mi perfil* | Confirmar el 2FA |
| Queda un permiso `View:ActividadRecienteTable` en Roles | Ese widget se quitó; `shield:generate` no borra permisos | Borrarlo desde la base o ignorarlo |
| No se ven los estilos de las pestañas | Assets sin compilar | `vendor/bin/sail pnpm run build` |
