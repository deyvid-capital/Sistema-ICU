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
- [x] `SolicitudFirmaResource` (*Envíos a firmar*, entrada **Firma digital** del menú lateral, `/admin/firmas/solicitudes`; sin cluster): asistente de 3 pasos (documentos del sistema y/o PDF subidos → firmantes con "Exigir orden" opcional → asunto, mensaje, fecha límite y recordatorio con los valores por defecto de la configuración).
- [x] `DocumentosFirmables`: buscador de PDF ya generados (equipos, celulares, altas/bajas, recibos) y lectura de su archivo.
- [x] `CrearSolicitudFirma`: valida PDF con el firmador (cifrado o dañado → error claro), guarda una copia privada de cada uno (snapshot + hash + páginas), firmantes en orden y código `SF-AAAA-NNNNN`. Si algo falla no queda nada.
- [x] El envío queda en **borrador**. El creador puede eliminarlo (`EliminarBorradorSolicitudFirma` borra también las copias).
- [x] Lista con pestañas: *Pendientes de mi firma*, *Enviados por mí*, *Todos* (con `View:SolicitudFirma`).
- [x] `SolicitudFirmaPolicy`: el creador y los firmantes siempre ven su envío; preparar exige `Create:SolicitudFirma`.
- [x] Ruta protegida para ver o descargar los PDF del envío (`firma.documentos.ver` / `firma.documentos.descargar`).
- [x] Regla `FirmanteDisponible`: solo usuarios con firma activa y vigente.
- [x] **Pruebas:** `CrearSolicitudFirmaTest` (11) y `SolicitudFirmaResourceTest` (10).

## Fase 4: Canvas de posiciones ✅

- [x] `pdfjs-dist` (pnpm) + entrada Vite `resources/js/firma/canvas-firmas.js` (componente Alpine cargado bajo demanda con `x-load`). En `vite.config.js` se agregó `preserveEntrySignatures: 'exports-only'`: sin eso Vite descarta el `export default` que necesita `x-load`.
- [x] Página `ConfigurarPosicionesFirma` (`/admin/firmas/solicitudes/{id}/posiciones`): documentos y firmantes a la izquierda, PDF a la derecha; clic para ubicar, arrastrar para mover, quitar, zoom, "Copiar posiciones a los demás documentos" y "Usar posiciones de la última vez".
- [x] Conversión píxeles ↔ puntos PDF con la matriz del viewport de pdf.js (los objetos de pdf.js quedan fuera del estado reactivo de Alpine, que los rompe).
- [x] `GuardarPosicionesFirma`: valida en el servidor documento y firmante del envío, página existente, recuadro dentro de la página y sin superposiciones.
- [x] `EnviarSolicitudFirma`: exige todas las posiciones, crea los campos vacíos en cada PDF (`/pdf/preparar`), guarda la versión 0, pasa el envío a *en curso* y notifica (`FirmaSolicitadaNotification`) a todos, o solo al primero si hay orden.
- [x] `PosicionesFirmaAnteriores`: sugerencias por tipo de documento del sistema.
- [x] **Pruebas:** `PosicionesYEnvioFirmaTest` (17). Además, prueba en Chrome headless con el módulo compilado: render del PDF, clic (coordenadas exactas), arrastre, límites de la página y copia entre documentos; y firma real con el `firmador` en la posición obtenida (el sello cae donde estaba el recuadro).
- [ ] Pendiente conocido: en páginas **rotadas** el recuadro se ubica bien, pero el sello sale girado. Los PDF que genera el sistema no están rotados.

## Fase 5: Firma (Pantalla 3) ✅

- [x] Detalle del envío: una pestaña por documento con visor del PDF, estado de cada firma (quién, cuándo o motivo de rechazo), abrir y descargar; firmantes con su avance; "Descargar todo (ZIP)".
- [x] **Firmar**: modal con la contraseña del sistema (+ código 2FA si está activado en los ajustes) y "Declaro haber revisado…". Con varios documentos pendientes se eligen cuáles (todos marcados por defecto).
- [x] `AbrirCredencialesFirma`: firma activa, vigente y no bloqueada; 2FA opcional; contraseña con contador de intentos (bloqueo temporal y aviso a admins al llegar al máximo). El `.p12` se abre una sola vez por petición (`CredencialesFirma`, no serializable).
- [x] `FirmarDocumento`: bloqueo por documento (`Cache::lock`), verificación de hash del archivo (si no coincide se detiene y se alerta), firma con el `firmador`, verificación de todas las firmas, nueva versión con bloqueo optimista y **un reintento** si otro firmó en medio. Cierra el documento y el envío cuando corresponde.
- [x] `FirmarLote`: una contraseña, varios documentos (máx. 20), resumen de firmados y fallidos.
- [x] Orden obligatorio **por documento**: con orden, el botón queda deshabilitado con "Esperando la firma de X"; al firmar se avisa al siguiente.
- [x] `RechazarFirma` (motivo obligatorio, cierra solo ese documento) y `CancelarSolicitudFirma` (creador o `Cancel:SolicitudFirma`, permiso creado por migración).
- [x] `AjustarFirmantesEnCurso`: reemplazar a un firmante que no firmó nada y quitar el orden obligatorio (avisa a quienes esperaban).
- [x] No se puede regenerar el PDF de origen mientras su documento esté en firma (`DocumentosEnFirma`: botón "Generar PDF" deshabilitado con aviso + bloqueo en los servicios `Generate*FormularioPdf`).
- [x] El sello se ajusta a cualquier tamaño de recuadro (`config/firma.php` → `sello.ancho/alto`): el `firmador` lo dibuja a tamaño natural y lo reduce proporcionalmente.
- [x] **Pruebas:** `FirmarDocumentoTest` (22) y `FirmarPantallaTest` (12): orden libre y obligatorio, firma ajena, doble firma, intentos y bloqueo, firma suspendida, 2FA, lock ocupado, archivo alterado, carrera con reintento, verificación fallida, lote parcial, rechazo, cancelación, reemplazo, quitar orden, bloqueo de regeneración, ZIP y permisos.
- [ ] Pendiente: resaltar en el visor el recuadro del firmante (hoy se ve el PDF con los campos vacíos).

## Fase 6: Notificaciones y recordatorios

- [x] `TipoEventoNotificacion::FirmaDigital`: interruptor por usuario (campana / correo) en *Usuarios → Seguridad → Notificaciones*, con aviso de qué pasa al apagarlo. **Cambio respecto al plan:** ya no hay eventos 🔒; todos los avisos de firma respetan el interruptor (pedido para poder probar sin mandar correos reales).
- [x] `SolicitudFirmaNotification` (+ `EventoSolicitudFirma`): firmó, finalizado, rechazado, cancelado, plazo vencido. `FirmaSolicitadaNotification` también hace de recordatorio. `CertificadoPorVencerNotification`.
- [x] `AvisosSolicitudFirma`: destinatarios de cada evento, sin avisarse a uno mismo; "Firmar todos" avisa una sola vez.
- [x] Comandos `firmas:enviar-recordatorios` (con `--fuera-de-horario` para pruebas) y `firmas:avisar-vencimientos` (columna `aviso_vencimiento_dias` para no repetir).
- [x] `Schedule` en `routes/console.php` + servicio `scheduler` en `compose.yaml`.
- [x] `Cancel:SolicitudFirma` declarado en `config/filament-shield.php` para que la pantalla de roles no lo borre al guardar.
- [x] **Pruebas:** `NotificacionesFirmaTest` (19) y `SeguridadGuardadoTest::test_apagar_y_volver_a_encender_los_avisos_de_firma_digital`.

## Fase 7: Integración con documentos existentes

Se fusionó con el [documento 9](09-documentos-firmados-en-origen.md) (documentos firmados en su origen y almacenamiento confidencial).

- [x] Acción **"Enviar a firmar"** en los módulos de origen (entregas, recepciones, notas, celulares, movimientos, asignaciones) y en *PdfEquipos* / *PdfCelulares*, más una **acción masiva** en esas dos tablas. Abre el asistente con los documentos preseleccionados (`?documentos=pdf_equipo:1,…`).
- [x] Columna **"Firma"** (Sin firma, En firma 1/2, Firmado, Firmado a mano, Rechazado) y filtro por estado en esas tablas.
- [x] Estado de firma en los PDF de origen (`estado_firma`, `firmado_at`, `firma_documento_id`, `hash_sha256`), con el enum `EstadoFirmaOrigen` y el trait `TieneEstadoFirma`.
- [x] Al completarse un documento, el job `PublicarDocumentoFirmado` copia el firmado sobre el original (misma ruta). Los rechazados no se publican. `firmas:sincronizar-origen` hace el backfill y la reparación.
- [x] **"Subir firmado a mano"** con permisos `UploadSigned:PdfEquipo|PdfCelular|PdfMovimiento`.
- [x] Una sola regla para reemplazar un PDF (`ReemplazoDocumentoFirmado`): bloqueado en firma; si está firmado, modal con la lista de firmas y casilla obligatoria. "Guardar y generar PDF" no regenera un firmado.
- [x] Asistente: filtro por categoría, del más reciente al más antiguo, firmados ocultos (con "Mostrar firmados" para consultar, sin poder elegirlos) y validación en el servidor. Los recibos de equipo quedan fuera de la firma.
- [x] Almacenamiento: marca en `public`; PDF y capturas en el disco confidencial (`local` o `s3`), servidos con la ruta protegida `archivos.pdf.*` o URL temporales; comando `archivos:mover`.
- [ ] Posiciones predeterminadas por plantilla (pendiente; hoy se usa "Usar posiciones de la última vez").
- [x] **Pruebas:** `DocumentosEnOrigenTest` (9), `FirmaEnModulosTest` (6), `AlmacenamientoConfidencialTest` (8), más ajustes en las pruebas de almacenamiento.

## Fase 8: Endurecimiento y salida a producción

- [ ] Auditoría completa (`DominioActividad::FirmaDigital`, eventos explícitos).
- [ ] Revisión de seguridad: ejecutar `/security-review` sobre la rama.
- [ ] Prueba E2E manual con 2–3 `.p12` reales: orden libre, orden obligatorio, lote, restablecimiento de contraseña y verificación en Adobe Reader.
- [ ] Respaldos: `storage/app/private/firma-digital` y la clave `FIRMA_ENCRYPTION_KEY`, por separado.
