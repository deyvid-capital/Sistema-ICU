# 8. Decisiones y riesgos

## Decisiones tomadas

| # | Decisión | Resolución |
|---|---|---|
| 1 | Motor de firma | ✅ **Propio** (`firmador` con pyHanko) |
| 2 | Carga y activación del `.p12` | ✅ El admin **solo sube** el archivo. La **activación la hace el titular** |
| 3 | Documentos por envío | ✅ **Uno o varios** por envío. El firmante puede firmar **todos de una vez** (una contraseña) o **uno por uno** |
| 4 | 2FA al firmar | ✅ No es necesario. Queda como opción en *Configuración → Firma digital*, **desactivada por defecto** |
| 5 | Sello de tiempo (TSA) | ⏸️ **Para después.** Por ahora la fecha de la firma la pone el reloj del servidor/sistema (ver explicación abajo) |
| 6 | Rechazar una firma | ✅ Incluido, con motivo obligatorio |
| 7 | Quién sube los `.p12` | ✅ **Siempre el admin**, para todos los usuarios, incluido él mismo |
| 8 | Emisores permitidos | ✅ **Sin validación de emisor.** Todas las firmas son de Digicert y de origen legal: el sistema acepta cualquier `.p12` válido que suba el admin |
| 9 | Dependencias nuevas | ✅ Aprobadas: contenedor `firmador` y `pdfjs-dist` |
| 10 | Contraseña para firmar | ✅ **La del sistema** (opción A). La del `.p12` se pide solo al activar o renovar |
| 11 | Orden de firma | ✅ **Libre por defecto**. Opción "Exigir orden", con el orden elegido por quien envía |
| 12 | Contraseña restablecida (admin, correo o importación) | ✅ La firma pasa a "requiere reactivación" y se avisa por correo al usuario, a los admins y a los super admins, con instrucciones |

## Respuestas del negocio

- **Documentos obligatorios:** ninguno. Simplemente se envían a firmar.
- **Usuarios sin `.p12`:** no se incluyen. Solo firman quienes tienen firma activa.
- **Plazo y recordatorios:** se configuran en cada envío, con valores por defecto editables en la configuración.
- **Cancelar un envío en curso:** solo quien lo creó o un admin.

## Para más adelante

### Decisión 5: Sello de tiempo (TSA)

**Por ahora no se implementa.** La fecha de la firma (la del sello visible y la interna de la firma) la pone **el reloj del servidor/sistema**, en hora de Bolivia. Hay que mantener ese reloj sincronizado (NTP), algo que el servidor normalmente ya hace.

Qué aportaría el TSA si se agrega más adelante: hoy la fecha la pone **el reloj de nuestro servidor**. Técnicamente, alguien con control del servidor podría atrasarla o adelantarla.

Un **sello de tiempo** (TSA) es un servicio externo y confiable que certifica: *"este documento ya existía, firmado, a esta hora exacta"*. Sirve para dos cosas:

1. **Prueba de la hora:** nadie puede discutir cuándo se firmó.
2. **Validez a largo plazo:** si el certificado vence o se revoca **después**, el sello prueba que la firma se hizo **cuando todavía era válido**.

Para agregarlo hace falta un servicio TSA (Digicert Bolivia o ADSIB podrían ofrecerlo, a veces con costo). En el `firmador` es un cambio pequeño: se le pasa la URL del TSA. No cambia nada del resto del módulo, por eso puede ir después.

### Decisión 8: Emisor de las firmas

**Resuelta: no se valida el emisor.** Hoy todas las firmas de la empresa las emite *Entidad Certificadora Autorizada Digicert* y son de origen legal. Solo el admin sube los `.p12`, así que el sistema confía en lo que se carga.

- Igual se guarda el **emisor** de cada certificado (columna `emisor`) y se muestra en la tabla de firmas, como dato informativo.
- Se mantienen las validaciones técnicas: que el archivo abra con su contraseña, que la clave corresponda al certificado, la vigencia y que el CI coincida con el usuario.
- Si en el futuro llegan firmas de otra entidad, no hace falta cambiar nada: se suben igual.

## Riesgos

| Riesgo | Mitigación |
|---|---|
| Robo del `.p12` cifrado | Inútil sin la clave del servidor **y** la contraseña del titular. Respaldos separados |
| Contraseña filtrada en logs o colas | Firma síncrona, nunca encolada, redacción en logs y una prueba que lo verifica |
| Fuerza bruta sobre la contraseña envuelta (si roban la base de datos y la clave) | Argon2id costoso + política de contraseñas fuertes + bloqueo tras N intentos en línea |
| Usuario olvida la contraseña del `.p12` tras un restablecimiento | Instrucciones en el correo. Si no la recuerda, reposición con Digicert y renovación por el admin |
| Adobe muestra "identidad desconocida" | No es un error: Adobe no trae la raíz de Digicert Bolivia. Se resuelve importando la raíz o con la verificación del propio sistema |
| Regenerar el PDF de origen con un proceso de firma en curso | Bloqueo de la acción y trabajo siempre sobre el snapshot |
| Firmante que no firma nunca | Orden libre (no traba a los demás), recordatorios, fecha límite y reemplazo o cancelación |
| Certificados vencidos sin renovar | Avisos a 30, 15 y 7 días, y renovación sin interrupción (el anterior sigue activo hasta activar el nuevo) |
| Caída del `firmador` | Healthcheck, `restart: unless-stopped` y error controlado sin pérdida de datos |
| PDFs actuales en el disco **público** | Los documentos firmados viven solo en el disco privado. Evaluar aparte mover también los generados |
