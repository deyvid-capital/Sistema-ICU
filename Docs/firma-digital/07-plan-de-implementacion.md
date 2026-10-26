# 7. Plan de implementación

Cada fase se puede entregar y probar por separado. Las pruebas siguen la convención actual: PHPUnit, `tests/Feature/...`, métodos `test_snake_case`, `Storage::fake()` y `Http::fake()` para el `firmador`.

## Fase 0: Preparación

- [x] Decisiones tomadas ([ver documento 8](08-decisiones-pendientes.md)).
- [x] Dependencias aprobadas: contenedor `firmador` (Python) y `pdfjs-dist` (pnpm).
- [x] Crear la carpeta `docker/firmador/`.
- [x] `FIRMADOR_URL`, `FIRMADOR_TOKEN` y `FIRMA_ENCRYPTION_KEY` en `.env.example` (sin valores) y en el `.env` local.

## Fase 1: Motor de firma (`firmador`) ✅

- [x] `docker/firmador/`: `Dockerfile`, `requirements.txt`, `app.py` (FastAPI) y `sello.py`, migrando la lógica de `.firma-digital/firmar.py`. El sello se arma en memoria, sin archivos temporales.
- [x] Endpoints: `inspeccionar` certificado, `inspeccionar`/`preparar`/`firmar`/`verificar` PDF y `salud`.
- [x] Servicio `firmador` en `compose.yaml`: sin puertos publicados, raíz de solo lectura, `/tmp` en memoria y healthcheck.
- [x] `config/firma.php`: URL, token, clave de cifrado y datos del sello.
- [x] `App\Services\Firma\FirmadorClient` + `App\Exceptions\FirmadorException` (con un código por tipo de error).
- [x] **Pruebas:**
  - Python (`docker/firmador/tests`, 22 pruebas): firma en orden libre (el 2º recuadro antes que el 1º) con ambas firmas válidas, firma incremental, contraseña incorrecta, certificado vencido, `.p12` con bytes extra (como el de Digicert), PDF cifrado o inválido, campos duplicados y token.
  - Laravel (`tests/Feature/Firma/FirmadorClientTest.php`, 11 pruebas) con `Http::fake()`.
  - Prueba real Laravel → `firmador` con un certificado de prueba: firma íntegra y válida, y sello igual al aprobado.

**Correr las pruebas del firmador:**

```bash
cd docker/firmador && docker build -t icu-firmador:dev . && \
docker run --rm -u root -v "$PWD/tests:/app/tests:ro" -v "$PWD/requirements-dev.txt:/app/requirements-dev.txt:ro" \
  -v "$PWD/../../public/images:/logo:ro" icu-firmador:dev \
  sh -c "pip install -q -r requirements-dev.txt && python -m pytest -q -p no:cacheprovider tests"
```

## Fase 2: Certificados y contraseña única ✅

- [x] Migraciones `certificados_firma` y `firma_settings`, modelos `CertificadoFirma` y `FirmaSetting`, factory con estados (`activo`, `vencido`, `pendienteActivacion`, `requiereReactivacion`) y enum `EstadoCertificadoFirma`.
- [x] `BovedaCertificados`: cifrado del archivo con la clave dedicada. `EnvolturaContrasena`: Argon2id + AES-256-GCM, con la sal y los parámetros dentro de la envoltura.
- [x] Servicios `AsignarCertificadoFirma`, `ActivarCertificadoFirma`, `RecifrarCertificadoFirma`, `SuspenderCertificadoFirma`, `RevocarCertificadoFirma` y `FirmarDocumentoDePrueba`, más `FirmaDigitalException` con los mensajes para el usuario.
- [x] `CertificadoFirmaResource` en `ConfiguracionCluster` (`/admin/configuracion/firmas-digitales`) con las acciones Asignar, Renovar, Editar sello, Reenviar aviso y Eliminar.
- [x] Página de ajustes **Configuración → Ajustes de firma** (`FirmaSetting`, auditada como `SeccionConfiguracion::FirmaDigital`).
- [x] Página **Mi firma digital** (menú de usuario): asistente de 2 pasos con el error bajo el campo, detección de "escribiste la contraseña del sistema" y "Probar mi firma" (descarga un PDF de ejemplo firmado).
- [x] Cambios de contraseña: `UpdatePassword` (perfil) vuelve a envolver en la misma transacción, y `UserObserver` suspende la firma y avisa al usuario, a los admins y a los super admins en los demás casos.
- [x] Notificaciones `FirmaActivacionPendienteNotification` y `FirmaRequiereReactivacionNotification` (campana + correo, siempre).
- [x] Auditoría: dominio `DominioActividad::FirmaDigital` y eventos `firma_digital_activada`, `firma_digital_contrasena_incorrecta` y `firma_digital_suspendida`.
- [x] Policy `CertificadoFirmaPolicy` y permisos generados con el seeder de roles.
- [x] **Pruebas** (`tests/Feature/Firma`, 63 pruebas en verde, incluidas las 11 del cliente de la Fase 1): custodia y envoltura, asignar y activar (todas las validaciones), cambios de contraseña (perfil, admin, correo), pantalla de administración, ajustes y Mi firma digital. Además, prueba real con el `firmador`: asignar → activar → probar firma → firma íntegra y válida.

## Fase 3: Envíos y preparación (Pantalla 2, sin canvas) ✅

- [x] Migración `create_solicitudes_firma_tables` (solicitudes, documentos, firmantes, firmas y versiones), modelos, enums (`EstadoSolicitudFirma`, `EstadoDocumentoFirma`, `EstadoFirma`) y factories.
- [x] Cluster **Firma digital** (`/admin/firmas`) con `SolicitudFirmaResource` (*Envíos a firmar*): asistente de 3 pasos (documentos del sistema y/o PDF subidos → firmantes con "Exigir orden" opcional → asunto, mensaje, fecha límite y recordatorio con los valores por defecto de la configuración).
- [x] `DocumentosFirmables`: buscador de PDF ya generados (equipos, celulares, altas/bajas, recibos) y lectura de su archivo.
- [x] `CrearSolicitudFirma`: valida PDF con el firmador (cifrado o dañado → error claro), guarda una copia privada de cada uno (snapshot + hash + páginas), firmantes en orden y código `SF-AAAA-NNNNN`. Si algo falla no queda nada.
- [x] El envío queda en **borrador**. El creador puede eliminarlo (`EliminarBorradorSolicitudFirma` borra también las copias).
- [x] Lista con pestañas: *Pendientes de mi firma*, *Enviados por mí*, *Todos* (con `View:SolicitudFirma`).
- [x] `SolicitudFirmaPolicy`: el creador y los firmantes siempre ven su envío; preparar exige `Create:SolicitudFirma`.
- [x] Ruta protegida para ver o descargar los PDF del envío (`firma.documentos.ver` / `firma.documentos.descargar`).
- [x] Regla `FirmanteDisponible`: solo usuarios con firma activa y vigente.
- [x] **Pruebas:** `CrearSolicitudFirmaTest` (11) y `SolicitudFirmaResourceTest` (10).

## Fase 4: Canvas de posiciones

- [ ] `pnpm add pdfjs-dist` + entrada Vite `resources/js/firma/canvas-firmas.js` (componente Alpine).
- [ ] Página `ConfigurarPosicionesFirma`: pestañas por documento, recuadros arrastrables y "Copiar posiciones a los demás".
- [ ] Conversión px ↔ puntos PDF (rotación y CropBox), validación de límites y superposición.
- [ ] "Usar posiciones de la última vez" por tipo de documento.
- [ ] "Enviar a firmar": `/pdf/preparar` por documento → versión 0 → notificaciones.
- [ ] **Pruebas:** posiciones válidas o inválidas, que el envío prepare todos los documentos y notifique a todos (orden libre) o solo al primero (con orden). Revisión manual en el navegador.

## Fase 5: Firma (Pantalla 3)

- [ ] Página del envío: pestañas por documento, visor, firmantes y acciones.
- [ ] `FirmarDocumento`: política, contraseña del sistema → `.p12`, `Cache::lock` por documento, hash, `firmador`, verificación, versión optimista con un reintento.
- [ ] `FirmarLote`: una contraseña, varios documentos, resumen de resultados y tope de 20.
- [ ] Orden obligatorio: política "le toca", reemplazar firmante y quitar orden en curso.
- [ ] Intentos fallidos y bloqueo temporal (valores de la configuración). 2FA si está activado.
- [ ] Rechazar (por documento) y Cancelar (creador o admin).
- [ ] Bloquear la regeneración del PDF de origen mientras esté en curso.
- [ ] **Pruebas:**
  - Firma en orden libre (Carlos firma antes que Pedro).
  - Orden obligatorio (Carlos no puede firmar antes de Pedro → 403).
  - Lock ocupado → mensaje.
  - Versión desactualizada → reintento correcto.
  - Hash alterado → aborta.
  - 5 contraseñas incorrectas → bloqueo.
  - Lote con un documento bloqueado → resumen parcial.
  - Último firmante → documento completado y envío finalizado.
  - Firma `requiere_reactivacion` → no puede firmar.
  - Rechazo, cancelación por el creador, cancelación por el admin y cancelación por un tercero (→ 403).

## Fase 6: Notificaciones y recordatorios

- [ ] Nuevos `TipoEventoNotificacion`, `SolicitudFirmaNotification` y `FirmaRequiereReactivacionNotification`.
- [ ] Comandos `firmas:enviar-recordatorios` y `firmas:avisar-vencimientos`.
- [ ] `Schedule` en `routes/console.php` + servicio `scheduler` en `compose.yaml`.
- [ ] **Pruebas:** `Notification::fake()` por evento, eventos 🔒 que ignoran las preferencias, recordatorio según la configuración del envío y el modo de orden, solo en horario hábil, avisos a 30, 15 y 7 días, contenido del correo de reactivación (usuario y admins).

## Fase 7: Integración con documentos existentes

- [ ] Acción **"Enviar a firmar"** en las vistas y como acción masiva en las tablas de `PdfEquipo`, `PdfCelular` y `PdfMovimiento`, que abre el wizard con los documentos preseleccionados.
- [ ] Badge de estado de firma en esas tablas ("Firmado" o "En firma 1/2").
- [ ] Posiciones predeterminadas por plantilla: los recuadros sobre las líneas de firma izquierda/centro/derecha de las Blade actuales.
- [ ] **Pruebas:** la acción aparece con permiso, la acción masiva crea un envío con N documentos y la regeneración se bloquea en curso.

## Fase 8: Endurecimiento y salida a producción

- [ ] Auditoría completa (`DominioActividad::FirmaDigital`, eventos explícitos).
- [ ] Revisión de seguridad: ejecutar `/security-review` sobre la rama.
- [ ] Prueba E2E manual con 2–3 `.p12` reales: orden libre, orden obligatorio, lote, restablecimiento de contraseña y verificación en Adobe Reader.
- [ ] Respaldos: `storage/app/private/firma-digital` y la clave `FIRMA_ENCRYPTION_KEY`, por separado.
