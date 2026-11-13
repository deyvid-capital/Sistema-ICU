# Guía de usuario (panel `/guia`)

Manual de uso del sistema, dentro del propio sistema: una pantalla aparte del panel admin
donde cada persona ve **solo los capítulos y secciones que su rol permite**, con capturas
generadas automáticamente y descarga en PDF.

Desde el 2026-11-13 la guía **no vive en este proyecto**. Es un paquete aparte,
`deyvid-capital/documentacion-sistema-icu`, con su propio repositorio. Durante el
desarrollo está en `packages/documentacion-sistema-icu/` (carpeta ignorada por git).

La documentación de la guía está en el paquete:

| Documento | Contenido |
|---|---|
| `packages/documentacion-sistema-icu/README.md` | Qué trae, estructura y comandos |
| `packages/documentacion-sistema-icu/AGENTS.md` | Reglas para trabajar en el paquete |
| `packages/documentacion-sistema-icu/TAREAS.md` | Lo que falta y cómo se reparte |
| `Docs/Plugin-Documentacion-SistemaICU/README.md` | El plan y las decisiones |

---

## 1. Lo que este sistema aporta a la guía

| Archivo | Rol |
|---|---|
| `composer.json` | Requiere el paquete |
| `config/documentacion-icu.php` | Clase anfitriona, modelo de usuario y datos de demostración |
| `app/Support/Guia/AnfitrionIcu.php` | Le da a la guía el tema, logo, colores y 2FA del panel admin; el disco confidencial para sus PDF; los avisos por la campana; el logo del PDF |
| `database/seeders/GuiaDemoSeeder.php` | Datos ficticios para las capturas. Solo siembra la base `guia_demo` |
| `resources/css/filament/admin/theme.css` | Importa los estilos de la guía y deja que Tailwind lea sus vistas |
| `app/Providers/Filament/AdminPanelProvider.php` | Enlace **Guía de usuario** en el menú de usuario |
| `phpunit.xml` | Suite de tests `Documentacion` |

La guía no agrega tablas ni permisos. Usa los permisos de Shield que ya existen.

## 2. Comandos

```bash
vendor/bin/sail artisan test --compact --testsuite=Documentacion   # tests de la guía
vendor/bin/sail artisan guia:capturar [capitulo]                   # capturas (solo desarrollo)
vendor/bin/sail artisan guia:limpiar-pdf                           # borra PDF viejos (corre solo cada semana)
```

## 3. Seguridad de los datos

- Las capturas se toman contra la base `guia_demo`, con datos ficticios. La base real no
  se lee ni se modifica.
- El comando que reconstruye esa base comprueba el entorno y el nombre de la base antes de
  borrar nada, y `GuiaDemoSeeder` se niega a sembrar cualquier otra.
- Los PDF se guardan en el disco confidencial y se entregan solo por una ruta con sesión.

## 4. Despliegue

1. Instalar la versión del paquete que corresponde a la versión del sistema.
2. `vendor/bin/sail pnpm run build` (estilos de la guía).
3. `vendor/bin/sail artisan queue:restart` (el PDF se genera en cola).
4. `guia:capturar` no se corre en producción.

## 5. Problemas comunes

| Síntoma | Causa probable | Solución |
|---|---|---|
| `composer install` falla por el paquete | Falta la carpeta `packages/documentacion-sistema-icu` | Clonar ahí el repositorio del paquete |
| La guía se ve sin estilos | Assets sin compilar | `vendor/bin/sail pnpm run build` |
| Un capítulo no aparece | El rol no tiene su permiso | Revisar el rol en Roles y permisos |
| Pedí el PDF y el aviso no llega | El worker de colas está caído o sin reiniciar | Levantar el worker; `queue:restart` |
| No puedo editar la guía desde el navegador | Esa función no está en esta versión | El contenido se cambia en el paquete |
