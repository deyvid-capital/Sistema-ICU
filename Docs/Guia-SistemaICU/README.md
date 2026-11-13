# Guía de usuario del Sistema ICU — plan de implementación

> **Plan reemplazado (2026-11-13).** La guía pasa a ser un plugin aparte: ver
> `Docs/Plugin-Documentacion-SistemaICU/README.md`. Este documento queda como registro de
> lo ya construido.

Plan corregido (2026-11-10, actualizado 2026-11-11). Reemplaza al plan generado por Cursor
(`guía_usuario_in-app`). Estado: **aprobado; fases 1 a 3 implementadas** (ver §10).

---

## 1. Qué se va a construir

Un módulo **Guía de usuario** dentro del sistema, con estas cuatro reglas:

1. **Cada persona ve solo lo que su rol permite.** Si un rol no puede ver Equipos, el
   capítulo de Equipos no aparece en su guía; si puede verlos pero no crearlos, no ve la
   sección "Registrar un equipo".
2. **El PDF trae solo lo permitido.** Lo que una persona descarga contiene exactamente los
   capítulos y secciones que ve en pantalla, nada más.
3. **Pantalla aparte del panel.** La guía tiene su propio menú lateral y sus propias
   pestañas, con los mismos estilos, colores, logo y componentes de Filament.
4. **Capturas automáticas.** Nadie saca capturas a mano: un comando recorre el sistema,
   toma las pantallas, resalta dónde hacer clic y las deja listas.
5. **El super administrador edita desde el navegador.** Puede cambiar el texto de una
   sección o reemplazar una captura en producción, sin desplegar. Nadie más edita.
6. **Todos descargan su PDF.** Quien ve la guía puede descargarla; el PDF trae solo lo que
   esa persona puede ver.

---

## 2. Decisiones

| Tema | Decisión | Por qué |
|---|---|---|
| Pantalla | **Segundo panel de Filament** en `/guia` | Da menú lateral, pestañas y tema propios sin inventar un layout. Misma sesión: no hay segundo login |
| Contenido | **Archivos en el repositorio** (`resources/guia/`), no base de datos | Llega a producción con el despliegue normal, queda versionado en git y las capturas automáticas se guardan al lado del texto |
| Permisos | Cada capítulo y sección declara un **permiso que ya existe** en Shield (`ViewAny:Equipo`, `Create:Equipo`…) | La guía sigue sola a los roles: no hay una segunda configuración de "quién ve qué" que mantener |
| Capturas | **Puppeteer**, que ya está instalado (lo usa Browsershot) | Sin dependencias nuevas. Playwright habría sido una más |
| Datos de las capturas | Base de datos **de demostración** con datos ficticios | Las capturas nunca muestran personas ni equipos reales, y salen iguales cada vez |
| PDF | Browsershot en cola, **un PDF por conjunto de permisos** | Dos personas que ven lo mismo comparten el archivo; nadie recibe contenido que no puede ver |
| Edición | El super admin edita texto y capturas en pantalla; se guarda como **edición** que tiene prioridad sobre el archivo | Permite corregir en producción sin desplegar, y se puede volver al original |
| Tablas nuevas | **Una** (`guia_ediciones`, en la fase 3) | Sin Media Library ni historial de exportaciones |

### Qué cambia respecto al plan de Cursor

| Plan de Cursor | Este plan | Motivo |
|---|---|---|
| Todos los usuarios ven toda la guía | Visibilidad por permisos, por capítulo y por sección | Es el requisito principal |
| Un solo PDF para todos | PDF según lo que cada quien puede ver | Ídem |
| Página dentro del panel admin | Panel propio `/guia` con su menú y pestañas | Requisito de pantalla aparte |
| Contenido en base de datos, editable en Filament | Archivos Markdown en el repo | El plan no decía cómo llegaban a producción el contenido y las capturas hechas en dev |
| 3 tablas + Media Library + disco confidencial | Una tabla, solo para las ediciones del super admin | El contenido base ya no vive en base de datos |
| Playwright + escenarios en YAML propio | Puppeteer (ya instalado) + una receta corta por capítulo | Sin dependencia nueva ni mini-lenguaje que mantener |
| Job con timeout de 300 s | Timeout de 180 s | La cola reintenta a los 210 s (`REDIS_QUEUE_RETRY_AFTER`); con 300 s el PDF se generaría dos veces |
| Campo `audiencia` sin uso | Eliminado | Lo reemplaza el permiso |

**Qué se edita dónde:** el texto y las capturas se pueden cambiar desde el navegador (§7).
Crear un capítulo, agregar o quitar una pestaña y cambiar el permiso que exige cada una se
hace en los archivos y llega con un despliegue.

---

## 3. Cómo se ve

```text
/guia
┌──────────────────┬──────────────────────────────────────────────────────┐
│  LOGO            │  Equipos                           [Descargar PDF ▾] │
│                  │ ┌────────────┬──────────────────────┬──────────────┐ │
│  Inicio          │ │ Ver y buscar│ Registrar un equipo │ Dar de baja  │ │
│                  │ └────────────┴──────────────────────┴──────────────┘ │
│  EQUIPOS         │                                                      │
│   Equipos      ◄─│  1. Abre Equipos → Equipos                           │
│   Cargadores     │  ┌────────────────────────────────────────────┐      │
│                  │  │  [captura con el botón resaltado ①]        │      │
│  USUARIOS        │  └────────────────────────────────────────────┘      │
│   Usuarios       │  2. Pulsa "Nuevo equipo" …                           │
│   Movimientos    │                                                      │
│                  │  ┌ Reglas ──────────────────────────────────┐        │
│  ← Volver al     │  │ • El service tag no se puede repetir     │        │
│    sistema       │  └──────────────────────────────────────────┘        │
└──────────────────┴──────────────────────────────────────────────────────┘
```

- **Menú lateral:** los capítulos visibles para el usuario, agrupados por módulo (los mismos
  grupos del sistema: Equipos, Usuarios, Accesos…).
- **Pestañas:** las secciones del capítulo que el usuario puede ver.
- **Inicio:** tarjetas con los capítulos disponibles para ese usuario.
- **Entrada:** enlace "Guía de usuario" en el menú de usuario del panel admin. Desde la guía,
  "Volver al sistema".
- Clic en una captura la amplía (modal de Filament).

---

## 4. Contenido: archivos en `resources/guia/`

```text
resources/guia/
  equipos/
    capitulo.php        ← título, grupo, orden, permiso y lista de secciones
    ver-y-buscar.md     ← una sección = una pestaña
    registrar.md
    dar-de-baja.md
    capturas.mjs        ← receta de capturas del capítulo (§6)
    img/                ← capturas generadas (.webp), se commitean
  inicio/
    …
```

`capitulo.php` (un arreglo PHP: no hace falta ningún parser):

```php
return [
    'titulo' => 'Equipos',
    'grupo' => 'Equipos',
    'icono' => Heroicon::OutlinedComputerDesktop,
    'orden' => 10,
    'permiso' => 'ViewAny:Equipo',          // null = lo ve cualquier usuario del panel
    'secciones' => [
        ['slug' => 'ver-y-buscar', 'titulo' => 'Ver y buscar'],
        ['slug' => 'registrar', 'titulo' => 'Registrar un equipo', 'permiso' => 'Create:Equipo'],
        ['slug' => 'dar-de-baja', 'titulo' => 'Dar de baja', 'permiso' => 'Update:Equipo'],
    ],
];
```

Cada sección es Markdown normal con una plantilla fija:

```markdown
Qué se hace en esta sección, en una o dos líneas.

1. Abre **Equipos → Equipos**.
   ![Listado de equipos](img/ver-y-buscar-01.webp)
2. Pulsa **Nuevo equipo**.

### Reglas
- El service tag no se puede repetir.
```

El Markdown se renderiza con HTML desactivado y sin enlaces inseguros. Las rutas de imagen
se reescriben a una ruta autenticada (§5).

---

## 5. Permisos

Regla única, usada por el menú, las pestañas, las imágenes y el PDF:

> Un capítulo o sección es visible si no declara permiso, o si el usuario lo tiene
> (`$user->can($permiso)`). Una sección exige además el permiso de su capítulo.

| Punto | Comportamiento |
|---|---|
| Menú lateral y tarjetas de Inicio | Solo capítulos visibles |
| Pestañas | Solo secciones visibles |
| URL directa a un capítulo o sección no permitidos | 404 (no revela que existe) |
| Imagen de una captura | Ruta autenticada que comprueba el permiso del capítulo; las capturas no están en `public/` |
| PDF | Se arma con la misma regla (§8) |
| Acceso al panel `/guia` | El mismo criterio que el panel admin (`canAccessPanel`) |

No se crean permisos nuevos: se usan los de Shield. Así, cambiar un rol en **Roles y
permisos** cambia su guía al instante.

Un test recorre todos los `capitulo.php` y falla si alguno declara un permiso que no existe
(evita un capítulo invisible para todos por un error de tipeo).

---

## 6. Capturas automáticas

Un comando hace todo:

```bash
vendor/bin/sail artisan guia:capturar            # todos los capítulos
vendor/bin/sail artisan guia:capturar equipos    # solo uno
```

Qué hace:

1. Prepara la base de datos **de demostración** (`guia_demo`): migra y corre
   `GuiaDemoSeeder`, con datos ficticios y fijos (mismos nombres y fechas siempre).
2. Levanta el sistema contra esa base en un puerto interno.
3. Abre Chrome con Puppeteer, entra como un super administrador de demostración y recorre
   la receta de cada capítulo.
4. Antes de cada captura resalta el elemento indicado (borde y número ①②③).
5. Guarda cada pantalla como `.webp` en `resources/guia/<capitulo>/img/`.
6. Se niega a correr fuera de desarrollo.

Con `--sin-preparar` reutiliza la base de demostración existente (más rápido al ajustar una
receta).

Receta de un capítulo (`capturas.mjs`): la lista de pantallas, no un lenguaje nuevo.

```js
export default [
    { archivo: 'ver-y-buscar-01', ir: '/admin/equipos/equipos' },
    { archivo: 'registrar-01', ir: '/admin/equipos/equipos', resaltar: 'Nuevo equipo' },
    {
        archivo: 'registrar-02',
        ir: '/admin/equipos/equipos/create',
        pasos: async (page, guia) => guia.escribir('input[id$="service_tag"]', 'DM7X4N9'),
        resaltar: 'Crear',
        completa: true,
    },
];
```

| Campo | Para qué |
|---|---|
| `archivo` | Nombre de la captura (`img/<archivo>.webp`) |
| `ir` | Pantalla a abrir. Si falta, sigue en la pantalla anterior |
| `resaltar` | Texto visible del botón o enlace, `{ selector }`, o una lista (se numeran ①②) |
| `pasos` | Interacción previa. Recibe la página de Puppeteer y ayudas: `guia.clic('Texto')`, `guia.escribir(selector, texto)`, `guia.esperar()` |
| `completa` | Captura la página entera, no solo lo visible |
| `sinSesion` | Pantalla pública, como el login |

**Flujo de trabajo por módulo:** escribir las secciones → escribir la receta → correr
`guia:capturar <capitulo>` → revisar en `/guia` → commit. Cuando cambie una pantalla del
sistema, se vuelve a correr el comando y git muestra qué capturas cambiaron.

Un test comprueba que toda imagen citada en un `.md` existe en `img/`, y que no quedan
imágenes huérfanas.

---

## 7. Edición por el super administrador

Solo el rol `super_admin` ve el botón **Editar** en cada sección de la guía.

| Acción | Qué hace |
|---|---|
| Editar texto | Abre el Markdown de la sección en un editor; al guardar, el cambio se ve al instante para todos |
| Reemplazar captura | Sube una imagen que sustituye a la captura automática |
| Restaurar original | Borra la edición y vuelve a la versión que vino con el sistema |

- Las ediciones se guardan en la tabla `guia_ediciones` (capítulo, sección, texto, quién y
  cuándo) y las capturas reemplazadas en el disco confidencial.
- Una sección editada muestra una marca "Editada en el sistema" (solo al super admin).
- La edición tiene prioridad sobre el archivo. Si un despliegue trae una versión nueva del
  archivo, la edición sigue mandando hasta que se restaure el original.
- El PDF usa la versión editada.
- Cada edición queda en el registro de actividad.

---

## 8. PDF según permisos

Botón **Descargar PDF** en la guía, con dos opciones: *Este capítulo* y *Toda mi guía*.

```text
Usuario pulsa Descargar
  └─► se calcula su conjunto visible (capítulos + secciones, regla del §5)
        └─► clave = hash(versión del contenido + conjunto visible)
              ├─ ya existe guia/pdf/<clave>.pdf  → descarga inmediata
              └─ no existe → job en cola → notificación "Tu PDF está listo" con el enlace
```

- **Seguridad:** la ruta de descarga recalcula la clave a partir del usuario en sesión.
  Nunca acepta la clave por URL, así que nadie puede pedir el PDF de otro rol.
- **Eficiencia:** todas las personas con el mismo conjunto visible comparten el archivo. Con
  pocos roles habrá pocos PDF distintos.
- **Vigencia:** la clave sale del contenido visible, así que cambiar un texto, una captura
  o un permiso da una clave nueva; los PDF viejos dejan de usarse y una tarea programada
  (`guia:limpiar-pdf`, semanal) borra los de más de 30 días.
- **Generación:** Blade + `BrowsershotPdfFactory::fromView()` (el que ya existe), con
  portada, índice y un capítulo por sección. Las capturas van embebidas.
- **Job:** `timeout = 180`, `tries = 2`, igual que los PDF de equipos. Si falla, avisa.
- **Almacenamiento:** disco confidencial, nunca `public`.

---

## 9. Piezas

| Área | Ruta |
|---|---|
| Panel | `app/Providers/Filament/GuiaPanelProvider.php` |
| Lector de contenido | `app/Support/Guia/` (`Guia`, `Capitulo`, `Seccion`): lee `resources/guia`, aplica la regla de permisos, calcula la versión |
| Páginas | `app/Filament/Guia/Pages/Inicio.php`, `VerCapitulo.php` + vistas Blade |
| Imágenes | Ruta autenticada `guia/img/{capitulo}/{archivo}` |
| PDF | `app/Services/Guia/GenerarGuiaPdf.php`, `app/Jobs/GenerarGuiaPdfJob.php`, vista `PlantillasPdf/guia-usuario.blade.php`, ruta de descarga |
| Capturas | `app/Console/Commands/GuiaCapturar.php`, `scripts/guia/capturar.mjs`, `database/seeders/GuiaDemoSeeder.php` |
| Contenido | `resources/guia/<capitulo>/…` |
| Enlace | "Guía de usuario" en el menú de usuario de `AdminPanelProvider` |
| Edición | Migración y modelo `GuiaEdicion`, acción Editar en `VerCapitulo` |
| Tests | `tests/Feature/Guia/` |

Tema, colores, logo y fuente del panel nuevo salen de la misma configuración que el panel
admin (`TemaService`), para que se vean idénticos.

---

## 10. Fases

| Fase | Entrega | Estado |
|---|---|---|
| **1. Base** | Panel `/guia`, lector de contenido, regla de permisos, Inicio y capítulo con pestañas, 2 capítulos piloto solo con texto, tests de permisos | **Hecha (2026-11-11)** |
| **2. Capturas** | `GuiaDemoSeeder`, `guia:capturar`, recetas de los 2 capítulos piloto, ruta de imágenes, ampliación al clic | **Hecha (2026-11-11)** |
| **3. Edición** | Tabla `guia_ediciones`, botón Editar para el super admin, reemplazo de capturas, restaurar original | **Hecha (2026-11-11)** |
| **4. PDF** | Servicio, job, plantilla, descarga, limpieza programada | **Hecha (2026-11-11)** |
| **5. Contenido** | Un capítulo por módulo, uno a la vez | Pendiente |

### Lo que ya existe (fase 1)

- Panel `/guia` (`GuiaPanelProvider`): mismo tema y sesión que el admin, menú lateral con
  Inicio, los capítulos visibles por módulo y "Volver al sistema".
- `App\Support\Guia\{Guia, Capitulo, Seccion, Permisos}`: leen `resources/guia` y aplican
  la regla de permisos.
- Páginas `Inicio` (tarjetas de capítulos) y `VerCapitulo` (pestañas, texto y recuadro de
  Reglas).
- Enlace **Guía de usuario** en el menú de usuario del panel admin.
- Capítulos piloto, solo texto: *Primeros pasos* (para todos) y *Equipos* (por permisos:
  ver, registrar, editar y cambiar estado, eliminar).
- Tests en `tests/Feature/Guia/GuiaUsuarioTest.php`, con contenido de prueba en
  `tests/Fixtures/guia`.

### Lo que ya existe (fase 2)

- `vendor/bin/sail artisan guia:capturar [capitulo…] [--sin-preparar]`: crea y reconstruye
  la base `guia_demo`, levanta una copia temporal del sistema en el puerto interno 8090 y
  toma las capturas. Las 12 de los capítulos piloto salen en menos de un minuto.
- `GuiaDemoSeeder`: 7 personas, 12 equipos y su catálogo, todo ficticio y con fechas fijas.
  Se niega a sembrar otra base que no sea `guia_demo`.
- `guia:preparar-demo` (interno): el único paso que borra tablas; antes comprueba que la
  base conectada sea `guia_demo` y que el entorno sea desarrollo.
- La copia temporal corre aislada: caché en memoria, cola síncrona y correo desactivado,
  para no mezclar nada con el entorno real.
- `scripts/guia/capturar.mjs` (Puppeteer) y una receta `capturas.mjs` por capítulo.
- Ruta autenticada `/guia/img/{capitulo}/{archivo}`: solo entrega la captura a quien puede
  ver el capítulo.
- Las capturas se amplían al hacer clic.
- Tests: permisos de las imágenes, y que toda captura citada exista y ninguna sobre.

### Lo que ya existe (fase 3)

- Botones **Editar sección**, **Reemplazar captura** y **Restaurar original** en cada
  sección, solo para el rol `super_admin`.
- Tabla `guia_ediciones` y modelo `GuiaEdicion`: el texto editado tiene prioridad sobre el
  archivo. `CapturasReemplazadas` guarda los reemplazos como WebP en el disco confidencial.
- Marca "Editada en el sistema" visible solo para el super administrador.
- Validaciones: no se guarda un texto que cite capturas inexistentes; solo se reemplazan
  capturas que la sección usa.
- Cambios de texto y de capturas en el registro de actividad.
- Tests en `tests/Feature/Guia/GuiaEdicionTest.php`.

### Lo que ya existe (fase 4)

- Botones **Descargar mi guía en PDF** (Inicio) y **Descargar PDF → Este capítulo / Toda mi
  guía** (capítulo), para cualquier usuario de la guía.
- `GuiaPdf`: arma lo visible para el usuario, calcula la clave, genera, descarga y limpia.
- `GenerarGuiaPdfJob` en cola, con aviso en la campana; ruta `/guia/pdf/{capitulo?}`.
- Plantilla `PlantillasPdf/GuiaUsuario.blade.php`: portada, índice y capítulos, con las
  capturas incrustadas.
- `guia:limpiar-pdf`, programado cada semana.
- Tests en `tests/Feature/Guia/GuiaPdfTest.php`.
- Medido con los 2 capítulos piloto: 15 páginas, 12 capturas, 3 MB, 2 segundos.

La documentación de uso está en
`.Doc_Laravel_13/Documentacion Sistema/Guia-de-Usuario/README.md`.

**Capítulos piloto:** *Primeros pasos* y *Equipos*. Equipos es un módulo
típico (listar, crear, editar) y ejercita los permisos por sección. *Firma digital* queda
para la fase 5: su flujo (certificado, lienzo de firma) es el más difícil de capturar y no
conviene estrenar el mecanismo con él.

### Tests principales (PHPUnit)

- Un rol sin `ViewAny:Equipo` no ve el capítulo en el menú y recibe 404 por URL directa.
- Un rol sin `Create:Equipo` ve el capítulo pero no la pestaña "Registrar".
- La imagen de un capítulo no permitido responde 404; sin sesión redirige al login.
- El PDF de un usuario contiene solo sus capítulos y secciones (se prueba el contenido que
  se envía a la plantilla).
- Dos usuarios con los mismos permisos comparten clave; con distintos, no.
- Cambiar el contenido cambia la versión.
- Integridad: permisos declarados existen; imágenes citadas existen; slugs únicos.

Al terminar cada fase se corre la suite completa
(`vendor/bin/sail php -d memory_limit=1G artisan test --compact`).

---

## 11. Riesgos y puntos abiertos

| Punto | Detalle | Cómo se trata |
|---|---|---|
| 2FA en las capturas | El sistema puede exigir 2FA al entrar | Resuelto: el usuario de demostración se crea sin 2FA obligatorio |
| Estilos nuevos | Las clases de la guía viven en el tema del panel | Tras desplegar o cambiar estilos, correr `vendor/bin/sail pnpm run build` |
| Capturas en git | Cada `.webp` pesa unas decenas de KB; un manual completo serán algunos MB, y cada regeneración suma historial | Aceptable al inicio. Si crece, se pasan a Git LFS |
| Pantallas que cambian | Una captura queda vieja si cambia la interfaz | Se vuelve a correr `guia:capturar`; git muestra las diferencias |
| Flujos difíciles de capturar | Firma digital, subida de archivos, PDF | Se dejan para la fase 5, con `pasos` a medida |
| PDF muy grande | Los 2 capítulos piloto pesan 3 MB y tardan 2 s; con todos los módulos serán decenas de MB | Si se acerca a los 120 s de Browsershot o pesa demasiado, se bajan de resolución las capturas del PDF o se parte por módulo |
| Crear capítulos desde el navegador | No está en este plan: la estructura (capítulos, pestañas, permisos) vive en archivos | Si se necesita, se agrega una administración completa encima |
| Edición y despliegue | Una sección editada en producción no recibe los cambios del archivo hasta restaurar el original | La marca "Editada en el sistema" lo hace visible |

---

## 12. Operación

- El worker de colas debe estar arriba para generar los PDF.
- Desplegar la guía = desplegar el código: texto y capturas van en el repositorio.
- `guia:capturar` se corre solo en desarrollo.
- Tras crear recursos nuevos con permisos, correr `RolesPermissionsSeeder` como siempre; la
  guía no agrega permisos propios.
