# Plugin `documentacion-sistema-icu`: plan

**Estado (2026-11-13):** fases 0 a 3 hechas. La guía ya vive en el paquete
`packages/documentacion-sistema-icu/` y el sistema la usa desde ahí. Lo que falta está en
`packages/documentacion-sistema-icu/TAREAS.md`.
**Fecha:** 2026-11-13.
**Reemplaza a:** `Docs/Guia-SistemaICU/README.md` como plan vigente. Aquel documento queda
como registro de lo ya construido (fases 1 a 4), que es la base de este plugin.

Las decisiones del usuario están en el §11. Donde este plan y el paquete difieran, manda
el paquete (`README.md`, `AGENTS.md` y `TAREAS.md`).

---

## 1. Qué cambia y por qué

Hoy la guía de usuario vive mezclada con el código del Sistema ICU: clases en `app/`,
vistas en `resources/`, contenido en `resources/guia/`, tests en `tests/`. Nada de eso
tiene commit.

El cambio:

1. **Todo lo de la guía sale del proyecto** y pasa a un paquete propio,

   `documentacion-sistema-icu`, en un repositorio privado aparte.
2. **El Sistema ICU solo lo instala y lo configura.** No guarda componentes, vistas,

   textos ni capturas de la guía.
3. **El contenido se organiza como la documentación de Filament:** carpetas y archivos

   numerados, una página por tema, menú lateral por secciones, índice de la página a la
    derecha, anterior/siguiente y buscador.
4. **Cada versión del sistema lleva su versión del plugin.** Cuando cambia una pantalla,

   se actualiza la guía en el plugin y se publica una versión nueva.

Lo que **no** cambia: las reglas que ya fijaste siguen valiendo.

- Cada persona ve solo lo que su rol permite; lo demás responde 404.
- El PDF trae solo lo que esa persona puede ver.
- Las capturas nunca se sacan a mano y nunca usan datos reales.
- La edición desde el navegador **no entra en la primera versión** (§11.4).

---

## 2. Dónde vive cada cosa

| Cosa | Hoy | Después |
|---|---|---|
| Panel `/guia`, páginas, vistas, estilos | `app/`, `resources/views/filament/guia/` | Plugin |
| Lector de contenido y regla de permisos | `app/Support/Guia/` | Plugin |
| PDF (servicio, job, plantilla, limpieza) | `app/`, `resources/views/PlantillasPdf/` | Plugin |
| Edición desde el navegador y su tabla | `app/Models/GuiaEdicion.php`, migración | Plugin |
| Textos `.md`, capturas y recetas | `resources/guia/` | Plugin (`docs/`) |
| Script de capturas | `scripts/guia/capturar.mjs` | Plugin |
| Comando `guia:capturar` | `app/Console/Commands/` | Plugin |
| Tests de la guía | `tests/Feature/Guia/` | Plugin |
| Datos ficticios (`GuiaDemoSeeder`) | `database/seeders/` | **Se queda en el Sistema ICU** |
| Conexión con tema, disco, avisos, 2FA | Directa (clases de `App\`) | Adaptadores pequeños en el Sistema ICU |

El seeder de demostración se queda porque crea equipos, áreas y cargos con los modelos
del sistema. El plugin solo sabe el nombre de la clase que debe llamar.

---

## 3. El paquete

| Dato | Valor |
|---|---|
| Nombre Composer | `deyvid-capital/documentacion-sistema-icu` |
| Namespace | `SistemaIcu\Documentacion` |
| Repositorio | Privado, en tu cuenta de GitHub |
| Requiere | PHP 8.5, Laravel 13, Filament 5, `spatie/browsershot` |

### Estructura

```text
documentacion-sistema-icu/
├── composer.json
├── README.md
├── CHANGELOG.md
├── config/documentacion-icu.php       lo que el sistema anfitrión configura
├── database/migrations/               tabla de ediciones
├── docs/                              EL CONTENIDO (§5)
├── resources/
│   ├── css/documentacion.css          estilos propios de la guía
│   └── views/                         páginas, componentes y plantilla PDF
├── scripts/capturar.mjs               capturas con Puppeteer
├── src/
│   ├── DocumentacionServiceProvider.php
│   ├── DocumentacionPanelProvider.php panel /guia
│   ├── DocumentacionPlugin.php        enlace "Guía de usuario" en el panel admin
│   ├── Contracts/                     lo que el anfitrión debe aportar (§4)
│   ├── Contenido/                     lector, capítulo, página, permisos, buscador
│   ├── Filament/Pages/
│   ├── Http/Controllers/
│   ├── Pdf/
│   ├── Edicion/
│   ├── Console/
│   └── Models/
├── tests/                             con contenido de prueba propio
└── .claude/skills/                    las skills de este plan, para los agentes
```

### Cómo se instala en el Sistema ICU

Durante el desarrollo el paquete se trabaja dentro del proyecto, en
`packages/documentacion-sistema-icu/`, como repositorio git propio e ignorado por el
`.gitignore` del sistema. La razón es Sail: el contenedor solo ve la carpeta del proyecto,
así que una carpeta hermana no le sería visible sin tocar `compose.yaml`.

```json
"repositories": [
    { "type": "path", "url": "packages/*" }
]
```

Para producción se cambia a instalación desde el repositorio privado, con versión fija
(`"deyvid-capital/documentacion-sistema-icu": "^1.0"`), antes del primer despliegue.

Agregar el paquete cambia `composer.json` del sistema; lo hago cuando lo apruebes.

---

## 4. Frontera entre el plugin y el sistema

El plugin no puede usar clases de `App\`. Hoy la guía usa estas, y cada una pasa a ser
una pieza configurable:

| Hoy usa | En el plugin | Lo aporta el Sistema ICU |
|---|---|---|
| `App\Models\User` | `config('documentacion-icu.modelo_usuario')` | El nombre de la clase |
| `TemaService` (logo, colores, favicon) | Contrato `Contracts\Tema` | Adaptador sobre `TemaService` |
| `ArchivosService::disco()` | `config('documentacion-icu.disco')` | El nombre del disco confidencial |
| `BrowsershotPdfFactory` | Generador propio en el plugin | Nada (solo la ruta de Chrome) |
| `CriticalEventNotification` | Contrato `Contracts\Avisos` | Adaptador que envía a la campana |
| `EnsureTwoFactorWhenRequired` | `config('documentacion-icu.middleware_auth')` | La lista de middleware |
| Rol `super_admin` | `config('documentacion-icu.puede_editar')` | Un permiso o una función |
| `DominioActividad`, `LogsIcuActivity` | Evento `SeccionEditada`, `CapturaReemplazada` | Un listener que registra la actividad |
| `GuiaDemoSeeder`, base `guia_demo` | `config('documentacion-icu.demo')` | El seeder y el nombre de la base |
| Logo fijo en la plantilla PDF | Lo entrega el contrato `Tema` | — |

En el Sistema ICU quedan, entonces, cuatro cosas: la línea de Composer, el archivo de
configuración, dos o tres adaptadores cortos y el seeder de demostración.

Regla para los agentes: **si una clase del plugin necesita algo del sistema, se agrega al
contrato o a la configuración; nunca un** `use App\...`**.**

---

## 5. El contenido, al estilo de Filament

Filament organiza su documentación en carpetas y archivos numerados
(`docs/03-resources/02-listing-records.md`), cada archivo con un encabezado de metadatos y
una página por tema. Aquí se hace igual, sumando lo que Filament no necesita: permisos.

```text
docs/
├── 01-primeros-pasos/
│   ├── index.md              el capítulo: título, grupo, icono, permiso, resumen
│   ├── 01-entrar.md          una página
│   ├── 02-navegar.md
│   ├── 03-mi-perfil.md
│   ├── capturas.mjs          receta de capturas del capítulo
│   └── img/                  capturas generadas
└── 02-equipos/
    ├── index.md
    ├── 01-ver-y-buscar.md
    ├── 02-registrar.md
    └── ...
```

- **El número del nombre da el orden**; no hay campo `orden`. La URL no lleva el número:
`/guia/equipos/registrar`.
- **Metadatos en el encabezado del** `.md` (frontmatter), en vez del `capitulo.php` actual:
  ```markdown
  ---
  titulo: Registrar un equipo
  permiso: Create:Equipo
  resumen: Cómo dar de alta un equipo nuevo en el inventario.
  ---
  ```
- **Sin dependencias nuevas:** el lector de Markdown que ya trae Laravel sabe leer ese
encabezado.
- **Bloques destacados** para notas, avisos y reglas, en lugar del `### Reglas` actual:
  ```markdown
  > [!REGLA]
  > Un equipo asignado no se puede eliminar.
  ```

  Tipos: `NOTA`, `CONSEJO`, `AVISO`, `REGLA`.
- **Capturas** igual que hoy: `![Listado de equipos](img/ver-01.webp)`.

### Cómo se ve

```text
┌──────────────┬──────────────────────────────────────┬───────────────┐
│ [logo]       │ Equipos › Registrar un equipo        │ En esta página│
│ Buscar…      │                                      │  Antes de     │
│              │ # Registrar un equipo                │  empezar      │
│ PRIMEROS     │ Cómo dar de alta un equipo…          │  Paso a paso  │
│  Entrar      │                                      │  Reglas       │
│  Navegar     │ ## Antes de empezar                  │               │
│ EQUIPOS      │ …                                    │ [Descargar    │
│  Ver y buscar│ [captura]                            │  PDF]         │
│ ▸Registrar   │                                      │               │
│  Editar      │ ← Ver y buscar     Editar y estado → │               │
└──────────────┴──────────────────────────────────────┴───────────────┘
```

- **Menú lateral:** grupo → capítulo → páginas. Solo lo que el usuario puede ver.
- **Índice de la página** a la derecha, con los subtítulos.
- **Anterior / siguiente** al pie, saltando lo que el usuario no puede ver.
- **Buscador** sobre títulos y texto, limitado a lo visible para ese usuario.
- **Las pestañas actuales desaparecen:** cada pestaña pasa a ser una página del menú.

Se sigue usando el tema, los colores y los componentes de Filament del sistema.

---

## 6. Qué se conserva de lo ya hecho

Nada se reescribe desde cero. Cada pieza se muda y se adapta:

| Pieza actual | Destino en el plugin | Cambio |
|---|---|---|
| `Guia`, `Capitulo`, `Seccion`, `Permisos` | `src/Contenido/` | Lee `docs/` numerado y frontmatter; `Seccion` pasa a `Pagina` |
| `GuiaPanelProvider` | `DocumentacionPanelProvider` | Tema y middleware por contrato y configuración |
| `Inicio`, `VerCapitulo` y sus vistas | `src/Filament/Pages/`, `resources/views/` | Menú por páginas, índice lateral, anterior/siguiente |
| `ImagenGuiaController` | `src/Http/Controllers/` | Sin cambios de fondo |
| `GuiaPdf`, `GenerarGuiaPdfJob`, plantilla | `src/Pdf/` | Avisos por contrato; logo desde `Tema` |
| `GuiaEdicion`, `CapturasReemplazadas`, migración | `src/Edicion/`, `src/Models/` | Actividad por eventos |
| `guia:capturar`, `guia:preparar-demo`, `capturar.mjs` | `src/Console/`, `scripts/` | Seeder y base desde configuración |
| `guia:limpiar-pdf` | `src/Console/` | El plugin registra su propia tarea programada |
| `resources/guia/{inicio,equipos}` | `docs/01-primeros-pasos`, `docs/02-equipos` | Formato nuevo (§5) |
| Regla CSS `.guia-contenido img` | `resources/css/documentacion.css` | El sistema ya no la tiene en su `theme.css` |
| 35 tests de `tests/Feature/Guia` | `tests/` del plugin | Corren con un sistema mínimo de prueba |
| `GuiaDemoSeeder` | Se queda en el Sistema ICU | Sin cambios |

### Lo que se retira del Sistema ICU al terminar

Los archivos nuevos sin commit (`app/Filament/Guia/`, `app/Support/Guia/`,
`app/Http/Controllers/Guia/`, `resources/guia/`, `scripts/`, `tests/Feature/Guia/`,
`tests/Fixtures/`, los comandos, el job, el modelo, la migración, la plantilla PDF) y los
cambios en `AppServiceProvider`, `bootstrap/providers.php`, `DominioActividad`,
`routes/console.php`, `theme.css` y el enlace del `AdminPanelProvider`.

---

## 7. Fases

| Fase | Qué se hace | Cómo se sabe que terminó |
|---|---|---|
| **0. Respaldo** | Guardar el trabajo actual sin commit antes de mover nada (§8) | Existe una copia recuperable |
| **1. Repositorio y base** | Crear el paquete y copiar la guía tal cual, como primer commit | El repositorio privado tiene la base intacta |
| **2. Separar** | Namespace propio, contratos, configuración, proveedor, migración, vistas y estilos | Ningún `use App\` en el plugin; sus tests pasan solos |
| **3. Conectar** | Instalarlo en el Sistema ICU, escribir adaptadores, retirar los archivos viejos | `/guia` funciona igual que hoy; suite completa en verde |
| **4. Formato y pantalla** | Contenido numerado con frontmatter; menú por páginas, índice, anterior/siguiente, buscador, bloques destacados | Los dos capítulos piloto se ven con el diseño nuevo |
| **5. Capturas y PDF** | Adaptar ambos al formato nuevo | `guia:capturar` y la descarga funcionan desde el plugin |
| **6. Contenido** | Un capítulo por módulo del sistema | Cada módulo tiene su capítulo con capturas |
| **7. Versiones** | Etiquetar el plugin e instalarlo por versión desde el repositorio privado | Producción instala una versión fija |

Las fases 1 a 5 van en orden. La fase 6 se puede repartir: cada capítulo es independiente,
así que varios agentes pueden escribir módulos distintos a la vez.

Después de cada fase se corre la suite del plugin y la del Sistema ICU
(`vendor/bin/sail php -d memory_limit=1G artisan test --compact`).

---

## 8. Fase 0: no perder la base

Todo lo de la guía está sin commit. Antes de mover un solo archivo:

1. Crear en el Sistema ICU una rama `guia-usuario-base` con un commit de todo lo actual.

   La rama no se fusiona a `main`; sirve solo de respaldo y de referencia.
2. Volver a `main` para seguir con el plan.
3. La fase 1 copia los archivos desde esa rama al paquete nuevo.

Así la base queda en dos lugares (la rama y el primer commit del plugin) antes de retirar
nada. Necesito tu visto bueno para hacer ese commit.

---

## 9. Skills para los agentes

Están en `.claude/skills/` de este proyecto. Como `.claude/` está en el `.gitignore` del
Sistema ICU, en la fase 1 se copian al repositorio del plugin, donde sí se versionan.

| Skill | Para qué |
|---|---|
| `doc-icu-arquitectura` | Estructura del paquete, frontera con el sistema, contratos, tests, convenciones |
| `doc-icu-interfaz` | La pantalla: menú, índice, buscador, anterior/siguiente, estilos, accesibilidad |
| `doc-icu-contenido` | Escribir un capítulo o una página: formato, tono, permisos, bloques destacados |
| `doc-icu-capturas` | Recetas de capturas y datos ficticios, sin tocar datos reales |

Reparto pensado:

- Fases 2 y 3: un agente con `doc-icu-arquitectura`.
- Fase 4: uno con `doc-icu-interfaz` y otro con `doc-icu-arquitectura` para el lector.
- Fase 6: un agente por módulo, con `doc-icu-contenido` y `doc-icu-capturas`.

---

## 10. Riesgos

| Riesgo | Detalle | Cómo se trata |
|---|---|---|
| Perder trabajo sin commit | Mover archivos sin respaldo | Fase 0 antes de todo |
| Versión del plugin y del sistema desfasadas | La guía describe una pantalla que ya cambió, o cita un permiso que no existe | Un test en el Sistema ICU comprueba que todo permiso citado por la guía existe; versión fija en Composer |
| Estilos que no cargan | Tailwind del sistema no ve las vistas del plugin | El plugin trae su CSS y el tema del sistema agrega una línea `@source` hacia el paquete |
| El worker no ve el job nuevo | El job cambia de namespace | `queue:restart` al desplegar |
| Ediciones hechas en el navegador | Guardadas por capítulo y sección con los nombres viejos | Hoy no hay ninguna en producción; la tabla nace con los nombres nuevos |
| Repositorio privado en producción | El servidor necesita credenciales para instalarlo | Token de solo lectura en el servidor |
| PDF muy grande | Con todos los módulos serán decenas de MB | Bajar la resolución de las capturas en el PDF o partir por módulo |

---

## 11. Decisiones del usuario (2026-11-13)

1. **Nombre:** `deyvid-capital/documentacion-sistema-icu`. Repositorio:
   `https://github.com/deyvid-capital/documentacion-sistema-icu-.git`.
2. **Ubicación:** `packages/documentacion-sistema-icu/` dentro del proyecto, con git
   propio e ignorado por el sistema.
3. **Pantalla:** libre. El plugin puede crear sus propios componentes y su menú, o cambiar
   los de Filament. Lo único fijo es la paleta de colores del sistema.
4. **Edición desde el navegador:** no entra en la primera versión. Queda guardada en la
   rama de respaldo por si se retoma.
5. **Respaldo:** hecho, en la rama local `guia-usuario-base` (commit `65a333d`), sin tocar
   `main`.
6. **Alcance:** solo para el Sistema ICU.
7. **Skills:** viven en el paquete, en `.agents/skills/`, y se versionan con él.

## 12. Cómo quedó (fases 0 a 3)

Diferencias con lo planeado arriba:

- **Un solo contrato** en vez de varios: `Contracts\Anfitrion`, con cinco métodos (panel,
  disco, URL del sistema, logo del PDF y avisos). El sistema lo implementa en
  `app/Support/Guia/AnfitrionIcu.php`.
- **Sin `DocumentacionPlugin`:** el enlace "Guía de usuario" del menú de usuario sigue en
  `AdminPanelProvider`, apuntando a la página del paquete.
- **Sin migraciones:** al no haber edición, el paquete no tiene tablas.
- **Los tests del paquete corren con la aplicación del sistema**
  (`--testsuite=Documentacion`), no aislados: el paquete es solo para este sistema.
- **El contenido sigue en el formato anterior** (`capitulo.php` + `.md`) dentro de
  `docs/`. El formato numerado del §5 es la tarea T1.

Lo que hay en el Sistema ICU por la guía:

| Archivo | Para qué |
|---|---|
| `composer.json` | Requiere el paquete; repositorio `path` hacia `packages/*` |
| `config/documentacion-icu.php` | Anfitrión, modelo de usuario y datos de demostración |
| `app/Support/Guia/AnfitrionIcu.php` | Conecta tema, disco, avisos y logo |
| `database/seeders/GuiaDemoSeeder.php` | Datos ficticios de las capturas |
| `resources/css/filament/admin/theme.css` | Importa los estilos del paquete |
| `app/Providers/Filament/AdminPanelProvider.php` | Enlace "Guía de usuario" |
| `phpunit.xml` | Suite `Documentacion` |
| `.gitignore` | Ignora `/packages/` |

**Pendiente antes de producción:** el `composer.json` y el `composer.lock` del sistema
apuntan al paquete por carpeta (`path`). Antes de desplegar hay que cambiarlo al
repositorio privado con versión fija (tarea T5); mientras tanto, quien clone el sistema
sin la carpeta `packages/` no podrá instalar dependencias.
