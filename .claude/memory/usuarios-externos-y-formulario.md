---
name: usuarios-externos-y-formulario
description: 2026-11-26 usuarios externos (acceso a SharePoint/OneDrive) con recordatorio y Formulario de Usuario Externo en PDF; soporte técnico renombrado; decisiones del usuario
metadata:
  type: project
---

Dos entidades distintas (decisión del usuario, 2026-11-25/26):
- **Soporte técnico externo** (empresas como Fortaleza que revisan equipos; notas de salida/recepción): tabla `personal_externo_soporte_tecnico`, modelo `PersonalExternoSoporteTecnico`, cluster Equipos, título corto «Soporte externo». FK en notas: `personal_externo_soporte_id`.
- **Usuarios externos** (auditores con acceso a documentos): tabla `usuarios_externos`, modelo `UsuarioExterno`, cluster Usuarios.

Reglas de usuarios externos que fijó el usuario:
- Estado booleano; permiso enum `PermisoDocumentoCompartido` (el usuario cambió los casos).
- SharePoint y/u OneDrive, al menos uno; fecha fin opcional.
- Recordatorio por fecha fin a los **administradores por correo**, no al responsable; comando `usuarios-externos:recordar-fin` en el scheduler existente.
- Formulario PDF: tipo siempre «Alta», cargo obligatorio, firmas solo con rótulos (Gerente de Área / Subgerente de TI), PDF en tabla `pdf_usuarios_externos` (no en columnas del registro).
- N° correlativo por año, automático y no editable: `UsuarioExterno::crearNumerado()` + `pg_advisory_xact_lock`. El 2026-11-26 el usuario quitó el campo N° del formulario (no se muestra al crear/editar; sí en tabla, ficha y PDF).
- Acciones del PDF (`UsuarioExternoFormularioPdfActions`): vista previa, generar/regenerar en cola, ver, descargar, enviar a firmar, subir firmado a mano. La ficha incrusta el PDF solo si existe.

**Why:** decisiones explícitas del usuario; el mismo nombre «usuario externo» se reutilizó para la entidad nueva.

**How to apply:** no mezclar ambas entidades; para formularios nuevos con firma, registrar el PDF en DocumentosFirmables, DocumentoPdfController, FirmaEnOrigenActions, ReemplazoDocumentoFirmado, filament-shield, archivos y MoverArchivosCommand. Relacionado: [[firma-digital-avance]], [[software-por-cargo-y-categorias]].
