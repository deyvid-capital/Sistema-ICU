# Excepciones de software: aprobación

Una **excepción de software** es un permiso especial (temporal, permanente o de emergencia)
para que una persona use un software que no le correspondería por su área o asignación
habitual. **No queda vigente al registrarla**: alguien del área responsable debe **aprobarla**
(o rechazarla).

Este documento explica **cómo usarla en el panel** y **cómo funciona por dentro**.

Guía de notificaciones (campana, correo y colas):
[`../Notificaciones-Criticas-y-Colas/README.md`](../Notificaciones-Criticas-y-Colas/README.md).

---

## 1. Resumen para el usuario

| Qué | Detalle |
|---|---|
| Dónde | **Software y licencias → Excepciones de software** |
| Al crear | Eliges el **área que aprueba** y **una persona de esa área** |
| Estado inicial | **Pendiente** |
| Quién decide | La persona elegida (por ejemplo, la gerente de Riesgo) |
| Si no está | Un **Super Admin** puede intervenir, **con una nota de respaldo** |
| Cambiar de área | Sí, **mientras esté pendiente** (por si debe aprobar Cumplimiento en lugar de Riesgo) |
| Avisos | **Campana** del panel (con enlace a la ficha) **y correo** |

Flujo:

```text
Registrar excepción
  └─► Elegir área + persona de esa área
        └─► Estado: Pendiente
              ├─► Aprobador: Aprobar o Rechazar
              ├─► Super Admin (si el aprobador no está): igual, con nota
              └─► Mientras esté pendiente: se puede cambiar área y aprobador
                    └─► El nuevo aprobador recibe campana + correo
```

---

## 2. Cómo se llega y qué se ve

Con permiso para ver el recurso (o si te asignaron una excepción para aprobar):

1. Entra al panel (`/admin`).
2. Abre el grupo **Software y licencias**.
3. Entra a **Excepciones de software**.

| Ruta | Página |
|---|---|
| `/admin/software-licencias/software-excs` | Listado |
| `/admin/software-licencias/software-excs/create` | Nueva excepción |
| `/admin/software-licencias/software-excs/{id}` | Ver (aquí se aprueba o rechaza) |
| `/admin/software-licencias/software-excs/{id}/edit` | Editar |

También puedes registrar una excepción desde:

- la ficha de un **usuario** (pestaña / relación de excepciones);
- la ficha de un **software** (relación de excepciones).

En el listado verás, entre otras columnas: usuario, software, tipo, **estado de aprobación**,
**área que aprueba** y **aprobador**.

Filtros útiles:

| Filtro | Para qué |
|---|---|
| Estado de aprobación | Pendiente / Aprobada / Rechazada |
| **Pendientes de mi aprobación** | Solo las que te toca decidir a ti |
| Software, tipo, papelera | Igual que el resto del catálogo |

Si una excepción está **pendiente**, al hacer clic en la fila se abre la ficha de **ver**
(no la de editar), para que el aprobador vea los botones **Aprobar** y **Rechazar**.

---

## 3. Estados

| Estado | Color | Significado |
|---|---|---|
| **Pendiente** | amarillo | Esperando decisión. El área y el aprobador se pueden cambiar. |
| **Aprobada** | verde | Quedó autorizada. Área y aprobador quedan bloqueados. |
| **Rechazada** | rojo | No se autorizó. Hace falta una nota con el motivo. |

Las excepciones que ya existían **antes** de este flujo se marcaron como **Aprobadas**, para no
dejar el histórico colgado.

La **baja de un colaborador** sigue cerrando las excepciones abiertas con `fecha_fin`. Eso no
cambia el estado de aprobación: solo pone fin a la vigencia.

---

## 4. Guía: registrar una excepción

Necesitas permiso `Create:SoftwareExc`.

1. **Software y licencias → Excepciones de software → Nueva excepcion**.
2. Completa:

   | Campo | Obligatorio | Notas |
   |---|---|---|
   | Usuario | sí | La persona que recibe la excepción. No puede ser el aprobador. |
   | Software | sí | El programa o sistema de la excepción. |
   | Tipo | no | Temporal, Permanente o Emergencia. |
   | **Área que aprueba** | sí | Ej. Riesgo. Puedes cambiarla después si otra área debe firmar. |
   | **Aprobador** | sí | Se habilita al elegir el área. Solo gente **de esa área**, con **acceso al sistema** y **al menos un rol**. |
   | Motivo | sí | Por qué se pide la excepción. |
   | Fecha de inicio | sí | |
   | Fecha de fin | no | Si la pones, no puede ser anterior al inicio. |

3. Guarda. El sistema deja la excepción en **Pendiente** y avisa al aprobador **por campana y
   por correo**, con un botón/enlace **Ver en el sistema** que abre esa ficha.

El mensaje al guardar: *«La excepcion quedó pendiente de aprobación. Se notificó al aprobador
por campana y correo.»*

### Quién aparece en la lista de aprobadores

Solo usuarios que:

- pertenecen al **área elegida**;
- tienen el **acceso habilitado**;
- tienen **al menos un rol** (si no pueden entrar al panel, no podrían aprobar);
- **no** son el mismo usuario de la excepción.

Si cambias el área, el aprobador se limpia y vuelves a elegir a alguien de la nueva área.

---

## 5. Guía: aprobar o rechazar (persona designada)

Eres la persona que eligieron como **Aprobador**.

1. Entra por la **campana** (icono de campana arriba a la derecha) → **Ver en el sistema**,
   o por el **correo** (mismo enlace), o por **Excepciones de software**.
2. Opcional: activa el filtro **Pendientes de mi aprobación**.
3. Abre la ficha.
4. Pulsa **Aprobar** o **Rechazar**.

| Acción | Comentario | Resultado |
|---|---|---|
| **Aprobar** | Opcional | Pasa a **Aprobada**. Se guarda quién decidió y cuándo. |
| **Rechazar** | **Obligatorio** | Pasa a **Rechazada**. La nota queda como respaldo. |

Los botones solo los ves si la excepción está pendiente **y** eres el aprobador (o Super Admin).
Otra persona del mismo área **no** puede decidirla: hay que asignársela cambiando el
aprobador, o que intervenga Super Admin.

Si no tienes el permiso de ver **todas** las excepciones, el listado te muestra **solo las
asignadas a ti**. El aviso de la campana/correo igual te lleva a esa ficha.

---

## 6. Guía: cambiar el área o el aprobador

Sirve cuando te equivocaste de área o cuando **otra área** debe aprobar (Riesgo → Cumplimiento,
por ejemplo).

1. Abre la excepción **pendiente**.
2. **Editar**.
3. Cambia **Área que aprueba**. El campo **Aprobador** se vacía.
4. Elige a la persona de la **nueva** área.
5. Guarda.

El **nuevo** aprobador recibe campana + correo. Cuando ya está **Aprobada** o **Rechazada**,
esos dos campos quedan bloqueados: no se pueden cambiar.

---

## 7. Guía: Super Admin interviene

Si el aprobador no está (vacaciones, baja, etc.), un usuario con rol **`super_admin`** puede
aprobar o rechazar en su lugar.

1. Abre la ficha pendiente.
2. **Aprobar** o **Rechazar**.
3. Completa la **nota de intervención** (obligatoria). El texto de ayuda pide indicar **por qué
   actúas en lugar de {nombre del aprobador}**. Esa nota queda en la ficha y en el historial.

Excepción: si el Super Admin **es también** el aprobador designado, actúa como esa persona y
**no** se le exige la nota al aprobar (sí al rechazar, como a cualquiera).

Tras intervenir:

- la ficha muestra **Decidido por**, **Fecha de decisión** y **Nota de decisión**;
- los administradores reciben campana + correo;
- el aprobador original recibe un aviso de que Super Admin intervino en su lugar.

---

## 8. Notificaciones (campana y correo)

Este flujo usa **los dos canales**, con enlace a la ficha:

| Momento | Quién recibe | Campana | Correo |
|---|---|---|---|
| Se crea la excepción o se cambia el aprobador | Persona designada | sí | sí |
| Se aprueba o rechaza | Administradores (`super_admin` y `admin`), salvo quien decidió | sí | sí |
| Super Admin intervino | El aprobador original, además de los admins | sí | sí |
| Se registra / se elimina (aviso genérico) | Administradores | sí | no (severidad media) |

En la campana verás el título, el texto y el botón **Ver en el sistema**. El correo lleva el
mismo asunto/cuerpo y el mismo enlace.

Las preferencias del usuario (opt-out por tipo de evento y canal) siguen valiendo: si alguien
apagó el correo de eventos críticos en **Seguridad**, no le llegará el mail. Por defecto ambos
canales están activos.

El envío va **en cola** (Redis + worker). Si la campana o el correo no aparecen, revisa que el
contenedor worker esté arriba. Detalle:
[`../Notificaciones-Criticas-y-Colas/README.md`](../Notificaciones-Criticas-y-Colas/README.md).

---

## 9. Qué se ve en la ficha del usuario

En **Usuarios → ver colaborador**, la sección **Excepciones de software** muestra también el
**estado de aprobación** y el **aprobador**, además del software, tipo, fechas y motivo.

---

## 10. Permisos

No hace falta un permiso extra tipo `Approve:SoftwareExc`. La decisión la autoriza la policy
según **quién eres** y **si está pendiente**.

| Situación | Qué puede hacer |
|---|---|
| `Create:SoftwareExc` | Registrar excepciones y elegir área + aprobador |
| `ViewAny:SoftwareExc` / `View:SoftwareExc` | Ver el catálogo completo |
| Eres el **aprobador** de una excepción | Ver **esa** ficha y **Aprobar / Rechazar**, aunque no tengas `ViewAny` |
| Rol **`super_admin`** | Ver todas, decidir las pendientes (con nota si no eres el designado) |
| `Update:SoftwareExc` | Editar (área/aprobador solo si sigue pendiente) |

Quien crea la excepción **no** la aprueba por el hecho de crearla: tiene que ser el aprobador
elegido o Super Admin.

---

## 11. Cómo funciona por dentro

| Pieza | Rol |
|---|---|
| Tabla `software_excepciones` | Datos de la excepción + aprobación |
| `App\Enums\EstadoAprobacionExcepcion` | `pendiente` / `aprobada` / `rechazada` |
| `App\Models\SoftwareExc` | Relaciones, `puedeDecidir()`, bloqueo de área/aprobador al estar decidida |
| `App\Services\Software\DecidirExcepcionSoftware` | Aprueba o rechaza, valida actor y nota, persiste la decisión |
| `App\Filament\Actions\AprobarExcepcionSoftwareAction` | Botón **Aprobar** |
| `App\Filament\Actions\RechazarExcepcionSoftwareAction` | Botón **Rechazar** |
| `App\Support\Notifications\NotifyExcepcionSoftware` | Campana + correo al aprobador (severidad **alta**) |
| `App\Observers\SoftwareExcObserver` | Al crear / reasignar / eliminar |
| `App\Policies\SoftwareExcPolicy` | `view`, `approve`, `reject` |

Campos de aprobación:

| Columna | Qué guarda |
|---|---|
| `area_aprobadora_id` | Área elegida |
| `aprobador_id` | Persona designada |
| `estado_aprobacion` | Pendiente / aprobada / rechazada (nuevo registro: pendiente) |
| `decidido_por_id` | Quién pulsó Aprobar o Rechazar (puede ser Super Admin) |
| `decidido_at` | Cuándo se decidió |
| `comentario_decision` | Nota de rechazo o de intervención |

```text
Aprobar / Rechazar (Filament)
  └─► DecidirExcepcionSoftware
        ├─ pendiente, no en papelera, actor = aprobador o super_admin
        ├─ rechazo → nota obligatoria
        ├─ Super Admin ≠ designado → nota obligatoria
        └─ transacción: estado + decidido_por + decidido_at + comentario
              └─► NotifyExcepcionSoftware (campana + correo)
```

Si alguien intenta cambiar área o aprobador **después** de decidir, el modelo **restaura** los
valores originales al guardar.

El listado (`SoftwareExcResource::getEloquentQuery`) muestra todo a quien tiene `ViewAny` o es
`super_admin`. Al resto, solo las filas donde `aprobador_id` es su usuario.

Tests: `tests/Feature/Filament/AprobarExcepcionSoftwareTest.php`.

---

## 12. Preguntas frecuentes

**¿La excepción ya autoriza el software al guardarla?**  
No. Hasta que esté **Aprobada** es solo una solicitud registrada.

**¿Puede aprobar cualquiera del área?**  
No. Solo la persona que elegiste. Si debe ser otra, cámbiala mientras esté pendiente.

**¿Puedo ser aprobador de mi propia excepción?**  
No. El formulario no deja elegir al mismo usuario de la excepción.

**¿Por qué no me aparece nadie en Aprobador?**  
Elige primero el área. Tiene que haber gente de esa área con acceso habilitado y un rol.
Si no tienen usuario de panel, no podrán entrar a aprobar.

**¿Llegó la campana pero no el correo?**  
Revisa preferencias de notificación del usuario, SMTP
([`../Correo-SMTP/README.md`](../Correo-SMTP/README.md)) y que el **worker** de colas esté
en marcha.

**¿Qué pasa con las excepciones viejas?**  
Quedaron **Aprobadas**. Las nuevas nacen **Pendientes**.
