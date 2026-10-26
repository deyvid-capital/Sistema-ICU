# Firma digital (.p12) — estado actual

Módulo para firmar documentos PDF con los certificados **.p12** de cada persona (emitidos por
*Entidad Certificadora Autorizada Digicert*, los mismos que se usan en Viafirma). El plan
completo, con todas las fases y decisiones, está en [`Docs/firma-digital/`](../../../Docs/firma-digital/README.md).

Este documento describe **lo que ya está construido**:

| Fase | Estado | Qué incluye |
|---|---|---|
| 1. Motor de firma | ✅ | Contenedor `firmador` (Python + pyHanko) y cliente Laravel |
| 2. Certificados y contraseña única | ✅ | Carga de `.p12` por el admin, activación por el titular, firma con la contraseña del sistema, suspensión al restablecer la contraseña |
| 3. Preparar envíos | ✅ | Envíos a firmar en borrador: documentos (del sistema o subidos), firmantes, orden opcional, plazo y recordatorio |
| 4. Canvas de posiciones y envío | ✅ | Se ubican los recuadros sobre cada PDF y se envía a los firmantes |
| 5. Firma | ✅ | Firmar con la contraseña del sistema (uno o varios), orden por documento, rechazo, cancelación, reemplazo de firmantes |
| 6–8. Recordatorios, integración, endurecimiento | ⏳ | Ver el plan |

> **Regla de oro:** la firma nunca es automática. El titular firma escribiendo **su contraseña
> del sistema** (la misma del login). Ninguna contraseña se guarda en texto plano.

---

## 1. Resumen

| Pieza | Rol |
|---|---|
| Contenedor `firmador` (`docker/firmador/`) | Abre `.p12`, prepara campos de firma, firma PAdES y verifica. Solo red interna + token |
| `App\Services\Firma\FirmadorClient` | Cliente HTTP del firmador; errores → `FirmadorException` con `codigo` estable |
| `App\Services\Firma\BovedaCertificados` | Guarda/lee los `.p12` cifrados (AES-256-GCM, `FIRMA_ENCRYPTION_KEY`) en el disco privado |
| `App\Services\Firma\EnvolturaContrasena` | Cifra la contraseña del `.p12` con una llave derivada (Argon2id) de la contraseña del sistema |
| Tabla `certificados_firma` / modelo `CertificadoFirma` | Una fila por `.p12` asignado (incluye historial de renovaciones) |
| Tabla `firma_settings` / modelo `FirmaSetting` | Ajustes globales (fila única, `FirmaSetting::actual()`) |
| Servicios de caso de uso (`app/Services/Firma/`) | Asignar, activar, recifrar, suspender, revocar, firmar documento de prueba |
| `App\Observers\UserObserver` | Detecta cambios de contraseña fuera de Mi perfil y suspende la firma |
| `App\Livewire\Profile\UpdatePassword` | Cambio de contraseña en Mi perfil: vuelve a envolver la contraseña del `.p12` |
| Recurso `CertificadoFirmaResource` | Pantalla del admin (cluster Configuración) |
| Página `FirmaDigital` | Ajustes de firma (cluster Configuración) |
| Página `MiFirmaDigital` | Pantalla de cada usuario: activar, reactivar, probar |
| Notificaciones `App\Notifications\Firma\*` | Activación pendiente y reactivación requerida (campana + correo) |
| Recurso `SolicitudFirmaResource` (cluster **Firma digital**) | Preparar y ver envíos a firmar |
| `App\Services\Firma\CrearSolicitudFirma` / `DocumentosFirmables` | Crear el envío con copias privadas de los PDF / buscar PDF del sistema |

---

## 2. Qué se ve en el panel

| Pantalla | Ruta | Quién | Contenido |
|---|---|---|---|
| **Firmas digitales** | `/admin/configuracion/firmas-digitales` | Admin (permisos `*:CertificadoFirma`) | Tabla de firmas y acciones |
| **Ajustes de firma** | `/admin/configuracion/firma-digital` | Permiso de página `View:FirmaDigital` | 2FA al firmar, intentos, bloqueo, plazo y recordatorio por defecto |
| **Mi firma digital** | `/admin/mi-firma-digital` | Cualquier usuario con una firma asignada | Estado, datos del certificado, activar / reactivar, probar |
| **Envíos a firmar** | `/admin/firmas/solicitudes` | Quien tenga `Create:SolicitudFirma` (preparar) · creador y firmantes (ver) | Lista, asistente para preparar, detalle del envío |

Accesos:

- **Menú de usuario → Configuraciones**: aparece si hay acceso a alguna página del cluster,
  ahora también a *Firmas digitales* o *Ajustes de firma*.
- **Menú de usuario → Mi firma digital**: solo si el usuario tiene una firma pendiente, activa
  o que requiere reactivación (`MiFirmaDigital::tieneFirmaAsignada()`).

### 2.1 Firmas digitales (admin)

Columnas: usuario, titular del certificado, CI, cargo en sello, emisor (oculta), vence (amarillo
a ≤ 30 días, rojo si venció), estado y cargado (oculta). Por defecto filtra los estados
vigentes; el historial (reemplazado, revocado) se ve cambiando el filtro *Estado*.

| Acción | Dónde | Qué hace |
|---|---|---|
| **Asignar firma** | Cabecera | Usuario (solo los que no tienen firma) + archivo `.p12`/`.pfx` (≤ 100 KB) + nombre y cargo del sello (se prellenan desde el usuario). Queda *pendiente de activación* y se avisa al titular |
| **Renovar (nuevo .p12)** / **Reemplazar archivo .p12** | Fila | Sube otro `.p12`. Si había una firma activa, sigue funcionando hasta que el titular active la nueva |
| **Editar sello** | Fila | Nombre y cargo que se imprimen en el recuadro (para tildes que el certificado no trae) |
| **Reenviar aviso de activación** | Fila (solo pendientes) | Vuelve a mandar la notificación |
| **Eliminar asignación** | Fila | Borra el `.p12` del disco y deja el registro como *revocado* |

> Al subir el `.p12` la tabla **no** muestra titular, CI ni vigencia: dentro del archivo esos
> datos están cifrados con su contraseña, que el admin no conoce. Se completan al activar.

### 2.2 Mi firma digital (titular)

Asistente de **2 pasos** al activar, reactivar o activar una renovación:

1. **Contraseña de tu archivo de firma (.p12)** — texto aclaratorio: es la de Digicert/Viafirma,
   **no** la del sistema. Se valida en el momento contra el firmador; si el usuario escribió su
   contraseña del sistema, el error lo dice explícitamente.
2. **Tu contraseña del sistema ICU** — muestra el resumen del certificado validado y explica que
   desde ahora firmará con esa contraseña.

**Probar mi firma**: pide la contraseña del sistema y descarga `prueba-firma-digital.pdf`
firmado (documento base: `resources/firma/documento-prueba.pdf`). No guarda nada.

### 2.3 Envíos a firmar

Menú lateral **Firma digital → Envíos a firmar**.

**Preparar envío** (asistente de 3 pasos):

1. **Documentos** — buscar PDF ya generados en el sistema (entregas, recepciones, notas, altas y
   bajas, recibos) y/o subir PDF. Máximo 20 por envío.
2. **Firmantes** — solo aparecen usuarios con firma **activa y vigente**. *Exigir orden de firma*
   (apagado por defecto): si se enciende, la lista se ordena arrastrando.
3. **Detalles** — asunto, mensaje, fecha límite y recordatorio (prellenados con *Ajustes de firma*).

Al guardar, el envío queda en **borrador** con código `SF-AAAA-NNNNN`. Cada PDF se copia al disco
privado (`firma-digital/solicitudes/{codigo}/{documento}/original.pdf`) con su hash y el tamaño de
sus páginas: lo que se firma es esa copia, nunca el archivo original del sistema. PDF con
contraseña o dañados se rechazan con un mensaje claro y no queda nada guardado.

**Lista**: pestañas *Pendientes de mi firma*, *Enviados por mí* y *Todos* (solo con
`View:SolicitudFirma`). Sin ese permiso, cada usuario ve únicamente los envíos que creó o que debe
firmar.

**Detalle**: datos del envío, documentos (clic para abrir el PDF) y firmantes. El creador (o un
admin con `Delete:SolicitudFirma`) puede **eliminar el borrador**; los envíos ya enviados no se
eliminan.

Los PDF del envío se sirven por `firma.documentos.ver` / `firma.documentos.descargar`, que verifican
que el usuario pueda ver el envío.

**Ubicar firmas y enviar** (botón en el detalle de un borrador → `/admin/firmas/solicitudes/{id}/posiciones`):

- A la izquierda, los firmantes (cada uno con un color) y los documentos (con `ubicados/total`).
- Se elige un firmante y se hace **clic** en el PDF: aparece su recuadro de 200 × 95 pt centrado en el
  clic y se pasa solo al siguiente firmante. El recuadro se **arrastra** para moverlo y no puede salir de
  la página. La ✕ lo quita.
- **Copiar posiciones a los demás documentos** (si hay varios) y **Usar posiciones de la última vez**
  (si hay un envío anterior del mismo tipo de documento del sistema; asigna 1º con 1º, 2º con 2º…).
- **Guardar posiciones** guarda aunque falten firmantes. **Enviar a firmar** exige todos los recuadros,
  crea los campos vacíos en cada PDF (versión 0), pasa el envío a *en curso* y avisa por campana y correo
  a todos los firmantes, o solo al primero si el envío exige orden. Desde ahí ya no se pueden cambiar
  documentos ni posiciones.

Técnica: el canvas es `resources/js/firma/canvas-firmas.js` (pdf.js + Alpine), cargado bajo demanda con
`x-load` solo en esta pantalla. Las posiciones se guardan en puntos PDF (origen abajo-izquierda) y el
servidor las vuelve a validar (`GuardarPosicionesFirma`).

> En páginas rotadas el sello saldría girado; los PDF que genera el sistema no están rotados.

### 2.4 Firmar (detalle del envío)

El firmante entra desde la campana o el correo ("Revisar y firmar"):

- Una pestaña por documento: visor del PDF, **Abrir**, **Descargar** y el estado de cada firma (quién
  firmó y cuándo, o el motivo de rechazo). Con varios documentos, **Descargar todo (ZIP)**.
- **Firmar** (o **Firmar (N)**): se eligen los documentos (todos marcados), se escribe la **contraseña del
  sistema** (y el código 2FA si *Ajustes de firma* lo exige) y se marca *Declaro haber revisado…*. Con
  varios documentos el resultado es un resumen: lo que no se pudo firmar (p. ej. porque otra persona lo
  estaba firmando en ese momento) queda pendiente.
- Con **orden obligatorio** el turno es **por documento**: si falta un firmante anterior, el botón queda
  deshabilitado con "Esperando la firma de X". Al firmar se avisa al siguiente.
- **Rechazar**: documento + motivo. Ese documento queda rechazado; los demás siguen.
- **Más acciones** (creador o admin): **Reemplazar firmante** (a alguien que no firmó nada), **Quitar orden
  obligatorio** y **Cancelar envío** (motivo obligatorio; para envíos ajenos hace falta
  `Cancel:SolicitudFirma`).

Qué pasa al firmar (`FirmarDocumento`):

1. Contraseña del sistema → con 5 intentos fallidos (configurable) la firma se bloquea 15 min y se avisa a
   los admins.
2. Bloqueo por documento (`Cache::lock`): dos personas nunca firman el mismo PDF a la vez.
3. Se comprueba el hash del archivo actual; si alguien lo tocó en disco, se detiene y se alerta.
4. El `firmador` firma de forma incremental y se verifican **todas** las firmas del resultado.
5. Se guarda `v{n}.pdf` con bloqueo optimista por versión (un reintento si otro firmó en medio).
6. El documento queda *completado* cuando no le faltan firmas y el envío *finalizado* cuando no le quedan
   documentos en curso.

Mientras un documento del sistema está en firma, su botón **Generar PDF** queda deshabilitado (y el
servicio de generación también lo rechaza): lo firmado debe coincidir con lo que el sistema muestra.

---

## 3. Estados de una firma

| Estado | Significado | Puede firmar |
|---|---|---|
| `pendiente_activacion` | El admin subió el `.p12`; falta que el titular lo active | No |
| `activo` | Activada y vigente | Sí (si no venció ni está bloqueada) |
| `requiere_reactivacion` | La contraseña del sistema se restableció fuera de Mi perfil | No, hasta reactivar |
| `reemplazado` | Sustituida por la renovación anual (historial) | No |
| `revocado` | Asignación eliminada por un admin (historial) | No |

Reglas en base de datos: como máximo **una** firma `activo` y **una** `pendiente_activacion` por
usuario (índices únicos parciales).

```text
Admin asigna ─► pendiente_activacion ─(titular activa)─► activo
                                                          │
         contraseña restablecida por admin / correo /     ▼
         importación ───────────────────────────► requiere_reactivacion ─(titular reactiva)─► activo

Renovación: nuevo pendiente_activacion (el activo sigue) ─(activa)─► activo + anterior = reemplazado
Admin elimina: cualquier estado vigente ─► revocado (archivo borrado)
```

---

## 4. Contraseña única: cómo funciona

```text
Activación (una vez al año)
  contraseña .p12 ──► firmador abre el .p12 y valida  ─┐
  contraseña sistema ──► Hash::check ──► Argon2id + sal ─► llave del titular
                                                        └► cifra la contraseña del .p12
                                                            └► se vuelve a cifrar con FIRMA_ENCRYPTION_KEY
                                                                └► certificados_firma.contrasena_p12_envuelta

Cada firma
  contraseña sistema ──► Hash::check ──► Argon2id ──► descifra la contraseña del .p12 (en memoria)
                                                       └► firmador firma con el .p12
```

- El servidor **no puede** firmar por nadie: le falta la contraseña del sistema del titular.
- Los parámetros de Argon2id y la sal viajan **dentro** de la envoltura: subir el costo en
  `config/firma.php` no rompe firmas ya activadas.

### Cambios de la contraseña del sistema

| Cómo cambia | Resultado |
|---|---|
| **Mi perfil** (`UpdatePassword`) | Se descifra con la anterior y se cifra con la nueva **en la misma transacción**. La firma sigue activa |
| Admin (formulario de usuario, Seguridad), recuperación por correo, importación | `UserObserver` detecta el cambio, la firma pasa a `requiere_reactivacion` y se avisa (campana + correo) al **titular** (con los pasos) y a **admins y super admins** (`AdminRecipients`) |

Detección: al activar o recifrar se guarda `contrasena_usuario_huella` (SHA-256 del hash de
contraseña con que se envolvió). Si el hash del usuario cambia y la huella ya no coincide, el
cambio no vino de Mi perfil.

---

## 5. Seguridad

| Medida | Detalle |
|---|---|
| `.p12` cifrado en reposo | AES-256-GCM con `FIRMA_ENCRYPTION_KEY` (distinta de `APP_KEY`), disco `local` (privado) |
| Temporales de subida | El `.p12` subido se borra de `livewire-tmp` apenas se lee |
| Contraseñas | Nunca en BD, caché, sesión, colas ni logs. `#[SensitiveParameter]` en todos los métodos que las reciben |
| `$hidden` / auditoría | `archivo`, `contrasena_p12_envuelta` y `contrasena_usuario_huella` fuera de `toArray()` y del activity log |
| Validaciones al activar | Abre con la contraseña · la clave corresponde al certificado · vigente · uso de firma · CI del certificado = carnet del usuario · el certificado no está asignado a otro usuario |
| CI | Digicert lo guarda en el atributo `1.3.6.1.1.1.1.0`. Se compara solo la parte numérica (el carnet puede tener `LP`, `-1B`, etc.). Si el usuario no tiene carnet, no se puede activar |
| Emisor | **No** se valida (decisión: todas las firmas son de Digicert y las sube el admin) |
| Firmador | Sin puertos publicados, token `X-Firmador-Token`, raíz de solo lectura, `/tmp` en memoria, no registra peticiones |
| Borrado | Al revocar o reemplazar, el archivo se borra físicamente; queda solo el registro |

Eventos de auditoría (`activity_log`, dominio `seguridad`):

| Evento | Cuándo |
|---|---|
| `firma_digital_activada` | El titular activa o reactiva su firma |
| `firma_digital_contrasena_incorrecta` | Contraseña del `.p12` incorrecta al activar |
| `firma_digital_suspendida` | La firma pasó a *requiere reactivación* (con motivo y causante) |
| `firma_digital_intento_fallido` | Contraseña del sistema incorrecta al firmar |
| `firma_digital_bloqueada` | Firma bloqueada por intentos fallidos |

Además, los cambios del modelo `CertificadoFirma` se registran en el dominio **Firma digital**, y
la página de ajustes como `SeccionConfiguracion::FirmaDigital`.

---

## 6. Servicio firmador

| Endpoint | Uso |
|---|---|
| `GET /salud` | Healthcheck (sin token) |
| `POST /certificados/inspeccionar` | `.p12` + contraseña → titular, CI, emisor, vigencia, huella, uso de clave |
| `POST /pdf/inspeccionar` | Páginas (tamaño, rotación), campos de firma, nº de firmas |
| `POST /pdf/preparar` | Agrega campos de firma vacíos |
| `POST /pdf/firmar` | Firma incremental de un campo con el sello visible |
| `POST /pdf/verificar` | Integridad y validez de cada firma |

Sello visible (200 × 95 pt): logo `public/images/Logo-Firma.png`, **"Firmado digitalmente por:"**
en Times-Bold, nombre, cargo y fecha `dd/mm/YYYY HH:MM BOT` en Times-Roman, color `#1F3A8A`.
La fecha la pone el reloj del servidor (el sello de tiempo TSA quedó para después).

---

## 7. Tablas

### `certificados_firma`

| Columna | Notas |
|---|---|
| `usuario_id` | UUID del titular |
| `archivo` | Ruta del `.p12` cifrado (`firma-digital/certificados/{usuario}/{uuid}.p12.enc`) |
| `contrasena_p12_envuelta` | Contraseña del `.p12` envuelta (ver §4). `null` hasta activar |
| `contrasena_usuario_huella` | Marca para detectar cambios de contraseña |
| `huella_sha256`, `titular_*`, `emisor`, `numero_serie`, `vigente_desde/hasta` | Se completan al activar |
| `nombre_en_sello`, `cargo_en_sello` | Texto del sello (editable por el admin) |
| `estado` | Ver §3 |
| `reemplaza_a_id` | Firma que reemplaza la renovación |
| `intentos_fallidos`, `bloqueado_hasta` | Reservados para la fase de firma |
| `cargado_por` | Admin que subió el archivo |

### Envíos (`solicitudes_firma` y relacionadas)

| Tabla | Contenido |
|---|---|
| `solicitudes_firma` | Código, asunto, mensaje, `orden_obligatorio`, estado (`borrador`, `en_curso`, `finalizada`, `cancelada`), fecha límite, recordatorio, creador |
| `solicitud_firma_documentos` | Cada PDF: nombre, origen (morph a `PdfEquipo`, `PdfCelular`, `PdfMovimiento`, `ReciboEquipo` o nulo si se subió), copia original y actual, hash, versión, páginas |
| `solicitud_firma_firmantes` | Usuario y `orden` |
| `solicitud_firma_firmas` | Recuadro de cada firmante en cada documento (se llena en la fase 4) |
| `solicitud_firma_versiones` | Historial de archivos de cada documento (fases 4 y 5) |

### `firma_settings`

`requiere_2fa_al_firmar` (false), `intentos_maximos` (5), `minutos_bloqueo` (15),
`plazo_dias_por_defecto` (3), `recordatorio_horas_por_defecto` (24).

---

## 8. Archivos clave

| Ruta | Responsabilidad |
|---|---|
| `docker/firmador/` | Servicio Python (`app.py`, `sello.py`, `Dockerfile`, `tests/`) |
| `config/firma.php` | URL y token del firmador, clave de cifrado, costo de Argon2id, sello |
| `app/Services/Firma/` | Cliente del firmador, bóveda, envoltura y casos de uso |
| `app/Exceptions/FirmadorException.php` | Errores técnicos del firmador (con `codigo`) |
| `app/Exceptions/FirmaDigitalException.php` | Errores de negocio con mensaje para el usuario |
| `app/Models/CertificadoFirma.php`, `app/Models/FirmaSetting.php` | Modelos |
| `app/Enums/EstadoCertificadoFirma.php` | Estados |
| `app/Observers/UserObserver.php` | Suspensión al cambiar la contraseña |
| `app/Livewire/Profile/UpdatePassword.php` | Recifrado desde Mi perfil |
| `app/Filament/Resources/CertificadosFirma/` | Recurso del admin (tabla y acciones) |
| `app/Filament/Clusters/Configuracion/Pages/FirmaDigital.php` | Ajustes |
| `app/Filament/Pages/MiFirmaDigital.php` + `resources/views/filament/pages/mi-firma-digital.blade.php` | Pantalla del titular |
| `app/Notifications/Firma/` | Notificaciones |
| `app/Policies/CertificadoFirmaPolicy.php` | Permisos Shield |
| `resources/firma/documento-prueba.pdf` | PDF base de "Probar mi firma" |
| `app/Filament/Clusters/Firmas/FirmasCluster.php` | Cluster *Firma digital* del menú lateral |
| `app/Filament/Resources/SolicitudesFirma/` | Recurso de envíos (asistente, lista, detalle) |
| `app/Models/SolicitudFirma*.php` | Envío, documentos, firmantes, firmas (recuadros) y versiones |
| `app/Policies/SolicitudFirmaPolicy.php` | Quién ve, prepara y elimina envíos |
| `app/Http/Controllers/Firma/DocumentoFirmaController.php` | Ver / descargar los PDF del envío |
| `app/Rules/FirmanteDisponible.php` | Validación: firmante con firma activa y vigente |
| `resources/js/firma/canvas-firmas.js` | Canvas (pdf.js + Alpine) |
| `app/Filament/Resources/SolicitudesFirma/Pages/ConfigurarPosicionesFirma.php` + vista `configurar-posiciones.blade.php` | Pantalla del canvas |
| `app/Services/Firma/GuardarPosicionesFirma.php`, `EnviarSolicitudFirma.php`, `PosicionesFirmaAnteriores.php` | Guardar, enviar y sugerir posiciones |
| `app/Notifications/Firma/FirmaSolicitadaNotification.php` | Aviso al firmante (campana + correo, solo enlace, sin adjuntos) |
| `app/Services/Firma/AbrirCredencialesFirma.php`, `CredencialesFirma.php` | Contraseña, 2FA, intentos y apertura del .p12 en memoria |
| `app/Services/Firma/FirmarDocumento.php`, `FirmarLote.php` | Firma con bloqueo, verificación y versiones |
| `app/Services/Firma/RechazarFirma.php`, `CancelarSolicitudFirma.php`, `AjustarFirmantesEnCurso.php` | Rechazo, cancelación, reemplazo y quitar orden |
| `app/Services/Firma/DocumentosEnFirma.php` | Bloquea regenerar PDF de origen en firma |
| `resources/views/filament/resources/solicitudes-firma/documento.blade.php` | Pestaña de cada documento (visor y firmas) |

---

## 9. Operación

### 9.1 Asignar la firma de un usuario

1. Revisar que el usuario tenga **carnet** cargado (se compara con el CI del certificado).
2. *Configuraciones → Firmas digitales → Asignar firma*: elegir usuario, subir el `.p12`,
   revisar nombre y cargo del sello (corregir tildes).
3. El usuario recibe el aviso y entra a *Mi firma digital → Activar mi firma*.

### 9.2 Renovación anual

*Firmas digitales → (fila) → Renovar (nuevo .p12)*. El titular la activa igual que la primera
vez (con la contraseña del **nuevo** `.p12`). La firma anterior funciona hasta ese momento.

### 9.3 Un usuario no puede firmar después de restablecer su contraseña

Es lo esperado: llega un correo a él y a los admins. Debe entrar a *Mi firma digital →
Reactivar mi firma* con la contraseña del `.p12` y su nueva contraseña del sistema. Si perdió la
contraseña del `.p12`: reposición en Digicert y *Renovar* con el archivo nuevo.

### 9.4 Las notificaciones fallan con `Route [...] not defined`

El worker de colas arrancó antes de que existiera la página nueva. Reiniciarlo:

```bash
vendor/bin/sail artisan queue:restart     # o: docker restart sistema-icu-worker-1
vendor/bin/sail artisan queue:retry all   # reintenta los trabajos fallidos
```

---

## 10. Tests

```bash
# Laravel
vendor/bin/sail artisan test --compact tests/Feature/Firma

# Servicio firmador (Python)
cd docker/firmador && docker build -t icu-firmador:dev . && \
docker run --rm -u root -v "$PWD/tests:/app/tests:ro" -v "$PWD/requirements-dev.txt:/app/requirements-dev.txt:ro" \
  -v "$PWD/../../public/images:/logo:ro" icu-firmador:dev \
  sh -c "pip install -q -r requirements-dev.txt && python -m pytest -q -p no:cacheprovider tests"
```

- `tests/Feature/Firma/Concerns/FakeFirmador.php` simula el firmador con `Http::fake()`.
- `phpunit.xml` define una `FIRMA_ENCRYPTION_KEY` de prueba y un Argon2id barato
  (`FIRMA_KDF_*`) para que la suite sea rápida.

---

## 11. Checklist de despliegue

1. Variables en el `.env`:
   - `FIRMADOR_URL=http://firmador:8000`
   - `FIRMADOR_TOKEN=` → `openssl rand -hex 32`
   - `FIRMA_ENCRYPTION_KEY=` → `php artisan key:generate --show`
2. Levantar el contenedor: `vendor/bin/sail up -d --build firmador`.
3. `vendor/bin/sail artisan migrate`.
4. Permisos: `vendor/bin/sail artisan db:seed --class=RolesPermissionsSeeder` (le da todo a
   `super_admin`) y asignar `*:CertificadoFirma`, `View:FirmaDigital` y `Create:SolicitudFirma`
   (quién prepara envíos) a otros roles si hace falta.
5. Compilar el frontend (incluye el canvas): `vendor/bin/sail pnpm install && vendor/bin/sail pnpm run build`.
6. Reiniciar el worker: `vendor/bin/sail artisan queue:restart`.
7. **Respaldar `FIRMA_ENCRYPTION_KEY`** por separado de `storage/app/private`: sin ella no se
   pueden abrir los `.p12` y todos los usuarios tendrían que recibir el archivo de nuevo.
