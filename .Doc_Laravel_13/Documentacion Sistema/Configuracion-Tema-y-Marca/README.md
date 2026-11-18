# Apariencia y marca del panel

> **La apariencia vive en el plugin `capitalsafi/filament-apariencia`.** Para colores, temas,
> logos, favicon y fuentes, ver su README:
> [`packages/capitalsafi/filament-apariencia/README.md`](../../../packages/capitalsafi/filament-apariencia/README.md).

Las páginas **Tema y colores** y **Marca e identidad** del cluster de Configuración, junto con
`TemaService`, `config/tema.php` y el observer de `AppearanceSetting`, se retiraron
(PLAN-002 del plugin). Este documento resume qué quedó en el Sistema ICU.

---

## 1. Qué hace ahora cada pieza

| Pieza | Rol |
|---|---|
| `AparienciaPlugin` (en `AdminPanelProvider`) | Única fuente de colores, temas, logos, favicon y fuente. Solo el `super_admin` edita la apariencia global (`canCustomize`). |
| `AdminPanelProvider` | Deja solo **respaldos fijos**: `images/Capital-Safi-SA-logo-.png`, `favicon.ico` y alto `2.5rem`. El plugin los reemplaza si hay marca subida. |
| `App\Support\Guia\AnfitrionIcu` | El panel de la guía registra el plugin en **solo lectura** (`settingsPage(false)`, `personalAppearance(false)`) y hereda la apariencia. `logoParaPdf()` usa `StudioBrand::logoPath()` con el PNG fijo como respaldo. |
| `App\Listeners\AuditarApariencia` | Escucha `AparienciaActualizada` y escribe en la auditoría de configuración (`SeccionConfiguracion::Apariencia`). Solo audita la apariencia **global**. |
| `apariencia:importar-marca` | Importa la marca antigua (`appearance_settings`) al plugin. Ver §2. |
| «Configuraciones» (menú de usuario) | Ya no incluye Tema y colores ni Marca: queda Archivos, Correo, Firma digital y Certificados. La apariencia está en el ítem propio del plugin. |

## 2. Importar la marca antigua

```sh
vendor/bin/sail artisan apariencia:importar-marca --dry-run   # solo muestra qué se importaría
vendor/bin/sail artisan apariencia:importar-marca             # importa
vendor/bin/sail artisan apariencia:importar-marca --force     # repite aunque el plugin ya tenga marca
```

Qué importa de `AppearanceSetting::actual()`:

- **Logo claro, logo oscuro y favicon:** desde Media Library o, si no hay, desde las columnas
  heredadas. Cada archivo pasa por `StudioImageProcessor` (se recodifica; nunca se copia el
  original). Un `.svg` o `.ico` **no se importa**: se avisa en la salida y se mantiene el respaldo.
  El favicon debe ser un PNG cuadrado de al menos 180×180.
- **Alto del logo** (`alto_logo`) → `logo_height`.
- **Fondo del logo** (`fondo_logo`): `auto` → `auto`, `claro` → `always`, `ninguno` → `never`.
- **Tema:** `safi`, `confianza`, `turquesa` y `neutro` se aplican con `StudioTheme::themeValues()`,
  solo si el plugin sigue con el tema por defecto (o con `--force`). `personalizado` y los colores
  de sidebar y topbar **no** se importan.

Es idempotente: si el plugin ya tiene logo o favicon no hace nada, salvo con `--force`. **No borra**
la tabla `appearance_settings` ni sus archivos (limpieza pendiente, T8 del PLAN-002).

## 3. Permisos (Shield)

La migración `2026_11_15_230000_delete_tema_colores_and_marca_permissions` borra `View:TemaColores`
y `View:Marca` (y sus asignaciones a roles y usuarios). Como las páginas ya no existen,
`shield:generate` no los vuelve a crear.

## 4. Auditoría

- `SeccionConfiguracion::Apariencia` registra la apariencia global: campos cambiados, y archivos
  subidos o eliminados.
- `SeccionConfiguracion::TemaColores` y `::Marca` se **conservan** (`@deprecated`) porque hay filas
  antiguas de `activity_log` con esos valores.

## 5. Archivos

| Archivo | Rol |
|---|---|
| `app/Providers/Filament/AdminPanelProvider.php` | Respaldos fijos y registro del plugin |
| `app/Support/Guia/AnfitrionIcu.php` | Plugin en solo lectura para la guía |
| `app/Listeners/AuditarApariencia.php` | Auditoría del evento del plugin |
| `app/Support/Auditoria/AuditorConfiguracion.php` | Método `cambiado()` para ajustes sin modelo |
| `app/Enums/SeccionConfiguracion.php` | Sección `Apariencia` y casos antiguos `@deprecated` |
| `app/Console/Commands/ImportarMarcaAparienciaCommand.php` | Importación de la marca antigua |
| `app/Models/AppearanceSetting.php` | Modelo heredado: se conserva hasta T8 |

## 6. Pruebas

```sh
vendor/bin/sail artisan test --compact tests/Feature/Configuracion/ImportarMarcaTest.php
vendor/bin/sail artisan test --compact tests/Feature/Auditoria/AuditoriaConfiguracionTest.php
```
