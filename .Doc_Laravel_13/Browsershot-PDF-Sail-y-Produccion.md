# Browsershot / PDF — Guía de desarrollo y producción

Documentación del proyecto **Sistema-ICU** para generar PDFs con
[Spatie Browsershot](https://spatie.be/docs/browsershot/v4/introduction)
(Chrome headless vía Puppeteer). Equivale a **Imprimir → Guardar como PDF** en el navegador.

Docs oficiales útiles:

- [Requirements](https://spatie.be/docs/browsershot/v4/requirements)
- [Installation & setup](https://spatie.be/docs/browsershot/v4/installation-setup)
- [Creating PDFs](https://spatie.be/docs/browsershot/v4/usage/creating-pdfs)

---

## 1. Qué usa este proyecto

| Pieza | Rol |
|---|---|
| `spatie/browsershot` | Paquete PHP (Composer) |
| `puppeteer` | Paquete Node (pnpm) que descarga Chrome |
| `App\Services\Pdf\BrowsershotPdfFactory` | Genera el PDF desde una vista Blade |
| `App\Http\Controllers\PdfPreviewController` | Vista previa inline en el navegador |
| Plantillas | `resources/views/PlantillasPdf/*.blade.php` |

### Variables de entorno

En `.env`:

```env
BROWSERSHOT_CHROME_PATH=
```

- Si está vacío, el factory busca Chrome en `~/.cache/puppeteer/...`
- Si falla en prod, pon la ruta absoluta al binario (recomendado)

Config: `config/services.php` → `services.browsershot.chrome_path`

### Rutas de preview

| URL | Descripción |
|---|---|
| `/pdf/preview/entrega` | PDF Formulario de Entrega |
| `/pdf/preview/recepcion` | PDF Formulario de Recepción |
| `/pdf/preview/nota-salida` | PDF Nota de Salida |
| `/pdf/preview/recepcion-externa` | PDF Recepción Externa |
| `/api/ver-form-*` | HTML de la plantilla (sin PDF) |

### Requisitos mínimos

- **Node.js 22+** (en Sail suele venir Node 24)
- **pnpm** (este proyecto usa pnpm, no npm)
- Puppeteer + Chrome / chrome-headless-shell
- Librerías del sistema de Chrome (Linux)

En `pnpm-workspace.yaml` debe existir:

```yaml
allowBuilds:
  puppeteer: true
```

Sin eso, pnpm puede instalar Puppeteer **sin** ejecutar el script que descarga Chrome.

---

## 2. Desarrollo con Laravel Sail (recomendado en este repo)

Todos los comandos van con `vendor/bin/sail`.

### 2.1 Arrancar

```bash
vendor/bin/sail up -d
```

### 2.2 Dependencias PHP y Node

```bash
vendor/bin/sail composer install
vendor/bin/sail pnpm install
```

### 2.3 Instalar Chrome (obligatorio la primera vez o tras rebuild)

```bash
vendor/bin/sail bash -c 'npx puppeteer browsers install chrome'
vendor/bin/sail bash -c 'npx puppeteer browsers install chrome-headless-shell'
```

En Sail el usuario es `sail` y el home suele ser `/home/sail`. Ejemplo de ruta:

```text
/home/sail/.cache/puppeteer/chrome-headless-shell/linux-XXXX/chrome-headless-shell-linux64/chrome-headless-shell
```

Opcional en `.env` (dentro del contexto Sail):

```env
BROWSERSHOT_CHROME_PATH=/home/sail/.cache/puppeteer/chrome-headless-shell/linux-150.0.7871.24/chrome-headless-shell-linux64/chrome-headless-shell
```

> La carpeta `linux-150.0.7871.24` cambia de versión. Si no la conoces, deja `BROWSERSHOT_CHROME_PATH` vacío y deja que el factory haga `glob`, o busca la ruta con:

```bash
vendor/bin/sail bash -c 'find /home/sail/.cache/puppeteer -name chrome-headless-shell -type f'
```

### 2.4 Librerías del sistema (si Chrome no arranca)

La imagen Sail ya trae muchas deps (incluye `playwright install-deps`). Si igual falla, instálalas como root:

```bash
vendor/bin/sail root-shell
```

Dentro del contenedor (Ubuntu 24.04):

```bash
apt-get update
apt-get install -y \
  libx11-xcb1 libxcomposite1 libasound2t64 libatk1.0-0 libatk-bridge2.0-0 \
  libcairo2 libcups2 libdbus-1-3 libexpat1 libfontconfig1 libgbm1 \
  libglib2.0-0 libgtk-3-0 libnspr4 libpango-1.0-0 libpangocairo-1.0-0 \
  libstdc++6 libx11-6 libxcb1 libxcursor1 libxdamage1 libxext6 \
  libxfixes3 libxi6 libxrandr2 libxrender1 libxss1 libxtst6
```

En Ubuntu 22.04 usa `libasound2` en lugar de `libasound2t64`.

> **Importante:** lo instalado con `apt` dentro del contenedor **se pierde** si haces `sail build --no-cache` / recreas la imagen. Vuelve a instalar Chrome y, si hace falta, las libs.

### 2.5 Probar

```bash
vendor/bin/sail artisan test --compact tests/Feature/PdfPreviewTest.php
```

En el navegador (APP_URL típico `http://localhost`):

- http://localhost/pdf/preview/entrega

El factory usa `noSandbox()` porque en Docker el sandbox de Chrome suele fallar.

---

## 3. Desarrollo sin Sail (PHP/Node en el host)

Útil si corres Laravel directo en WSL2/Linux/macOS sin Docker.

### 3.1 Requisitos

1. PHP 8.3+ con extensiones del proyecto
2. Composer
3. Node 22+ y pnpm
4. En Linux: librerías de Chrome (mismo `apt install` de la sección 2.4)

### 3.2 Instalación

```bash
composer install
pnpm install
npx puppeteer browsers install chrome
npx puppeteer browsers install chrome-headless-shell
```

### 3.3 Localizar Chrome y configurar `.env`

```bash
# Linux / WSL
find ~/.cache/puppeteer -name chrome-headless-shell -type f

# macOS (ruta puede variar)
find ~/Library/Caches/puppeteer -name 'chrome*' -type f 2>/dev/null | head
```

En `.env`:

```env
APP_URL=http://127.0.0.1:8000
BROWSERSHOT_CHROME_PATH=/ruta/absoluta/a/chrome-headless-shell
```

### 3.4 ¿Hace falta `noSandbox()`?

- **Linux con AppArmor / contenedores / algunos WSL:** a veces sí (el factory ya lo activa siempre).
- **macOS / Windows nativo:** suele funcionar; `noSandbox()` no suele molestar en local de bajo riesgo.

Si ves el error *"No usable sandbox"* en Ubuntu 23.10+, Spatie sugiere en `/etc/sysctl.conf`:

```text
kernel.apparmor_restrict_unprivileged_userns=0
kernel.apparmor_restrict_unprivileged_unconfined=0
```

Luego `sudo sysctl -p` (solo si hace falta; en Sail ya mitigamos con `noSandbox()`).

### 3.5 Servir y probar

```bash
php artisan serve
# o tu stack habitual
php artisan test --compact tests/Feature/PdfPreviewTest.php
```

---

## 4. Producción (Forge / VPS Ubuntu)

Con 5–10 PDF al mes **no** tendrás problema de RAM ni tráfico. El riesgo es olvidar instalar Node/Chrome en el server.

### 4.1 Checklist de deploy

1. Código desplegado (incluye `composer.json` + `package.json` + `pnpm-lock.yaml`)
2. `composer install --no-dev --optimize-autoloader`
3. `pnpm install --prod` (o `pnpm install` según tu pipeline)
4. Instalar Chrome en el usuario que ejecuta PHP (forge, www-data, etc.)
5. Librerías `apt` de Chrome
6. Definir `BROWSERSHOT_CHROME_PATH` en `.env` de producción
7. `php artisan config:cache`
8. Probar una URL de preview o generar un PDF real

### 4.2 Instalación en Forge (Ubuntu 24.04)

Según la [documentación Spatie](https://spatie.be/docs/browsershot/v4/requirements):

```bash
node -v && npm -v   # o pnpm -v

# En el directorio del proyecto
cd /home/forge/tu-sitio
pnpm install --prod

# Chrome para Puppeteer (como el mismo usuario del sitio)
npx puppeteer browsers install chrome
npx puppeteer browsers install chrome-headless-shell

# Dependencias del sistema
sudo apt update
sudo apt install -y \
  libx11-xcb1 libxcomposite1 libasound2t64 libatk1.0-0 libatk-bridge2.0-0 \
  libcairo2 libcups2 libdbus-1-3 libexpat1 libfontconfig1 libgbm1 \
  libglib2.0-0 libgtk-3-0 libnspr4 libpango-1.0-0 libpangocairo-1.0-0 \
  libstdc++6 libx11-6 libxcb1 libxcursor1 libxdamage1 libxext6 \
  libxfixes3 libxi6 libxrandr2 libxrender1 libxss1 libxtst6
```

Ubuntu 22.04: reemplaza `libasound2t64` por `libasound2`.

### 4.3 `.env` de producción

```env
APP_ENV=production
APP_URL=https://tu-dominio.com

# Obligatorio recomendado: ruta real del binario en ESTE servidor
BROWSERSHOT_CHROME_PATH=/home/forge/.cache/puppeteer/chrome-headless-shell/linux-XXXX/chrome-headless-shell-linux64/chrome-headless-shell
```

Cómo obtener la ruta exacta (SSH como usuario forge):

```bash
find ~/.cache/puppeteer -name chrome-headless-shell -type f
```

### 4.4 Timeouts

Un PDF puede tardar unos segundos. Si PHP-FPM/nginx cortan muy pronto:

- `max_execution_time` de PHP ≥ 60 (el factory usa timeout 120 s en Browsershot)
- timeout de proxy/nginx acorde

Con pocos PDF/mes casi nunca es un tema.

### 4.5 Seguridad

- No pases HTML/URLs no confiables a Browsershot (regla de Spatie).
- Las plantillas de este proyecto son internas; el logo se embebe en base64 (no depende de `APP_URL` para la imagen).
- `noSandbox()` es habitual en servers/contenedores; en un VPS dedicado de confianza es aceptable para este caso de uso.

### 4.6 Qué NO hace falta por el volumen bajo

- Servicio Gotenberg aparte
- Colas obligatorias
- Más RAM solo por Browsershot (Chrome vive segundos y se cierra)

---

## 5. Problemas frecuentes

| Síntoma | Causa probable | Qué hacer |
|---|---|---|
| 500: Could not find chrome-headless-shell | No instalaste Chrome o mala ruta | `npx puppeteer browsers install chrome-headless-shell` + `BROWSERSHOT_CHROME_PATH` |
| 500: No usable sandbox | AppArmor / Docker | Ya usamos `noSandbox()`; en host revisar sysctl Spatie |
| 500: error de librería .so | Faltan deps apt | Instalar paquetes de la sección 2.4 / 4.2 |
| Logo no sale | Archivo ausente | Verificar `public/images/Capital-Safi-SA-logo-.png` |
| Tras `sail build` deja de funcionar | Cache Chrome / apt del contenedor limpios | Reinstalar Chrome (y libs si hace falta) |
| pnpm no descarga Chrome | `allowBuilds` | `pnpm-workspace.yaml` → `puppeteer: true` |
| Tests OK en Sail, falla en prod | Otro usuario/`HOME` | Fijar `BROWSERSHOT_CHROME_PATH` en prod |

### Verificar que Chrome responde (Sail)

```bash
vendor/bin/sail bash -c '$(find /home/sail/.cache/puppeteer -name chrome-headless-shell -type f | head -1) --version'
```

### Auditoría de vulnerabilidades (Node)

```bash
vendor/bin/sail pnpm audit --prod
# sin Sail:
pnpm audit --prod
```

---

## 6. Archivos clave del proyecto

```text
app/Services/Pdf/BrowsershotPdfFactory.php   # Generación PDF
app/Http/Controllers/PdfPreviewController.php
config/services.php                          # browsershot.chrome_path
routes/web.php                               # /pdf/preview/*
resources/views/PlantillasPdf/               # Plantillas Blade
tests/Feature/PdfPreviewTest.php
package.json                                 # dependency: puppeteer
pnpm-workspace.yaml                          # allowBuilds.puppeteer
composer.json                                # spatie/browsershot
```

---

## 7. Resumen rápido

| Entorno | Comando típico | Chrome | `.env` |
|---|---|---|---|
| Sail | `sail pnpm install` + `sail … puppeteer browsers install …` | Cache en `/home/sail/.cache/puppeteer` | Opcional `BROWSERSHOT_CHROME_PATH` |
| Sin Sail | `pnpm install` + `npx puppeteer browsers install …` | Cache en `~/.cache/puppeteer` | Recomendado fijar ruta |
| Producción | Deploy + `pnpm install` + install browsers + apt | Cache del usuario PHP | **Fijar** `BROWSERSHOT_CHROME_PATH` |

Flujo mental: **Blade → HTML → Chrome imprime PDF → respuesta `application/pdf`**.
