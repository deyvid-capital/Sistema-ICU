---
name: cambios-sistema-2026-11-13
description: "Tres cambios de comportamiento del Sistema ICU hechos el 2026-11-13 a raíz de la revisión de la guía: permisos de cargadores, celulares sin doble asignación y «Reasignar todo» al reingresar"
metadata:
  type: project
---

Al revisar la guía de usuario aparecieron huecos del sistema; el usuario pidió corregirlos, cada uno con su commit en `main` (2026-11-13):

- `21da21f` **Cargadores:** «Usar cargador de otro equipo» exige `Create:AsignacionCargador` y «Registrar cargador comprado» exige `Update:AsignacionCargador` (antes solo miraban el estado del registro). Sin permisos nuevos.
- `a3742cf` **Celulares:** un celular con asignación activa no se puede volver a asignar (regla `CelularDisponibleParaAsignacion` + aviso «No se puede asignar el celular»); el formulario de cargador de celular lista solo los libres (regla `CargadorCelSinAsignar`). El usuario pidió algo simple: notificación para el celular y filtro para los cargadores.
- `2dcdbad` **Reingreso:** acción «Reasignar todo» en la ficha del usuario (`ReasignarTrasReingresoAction`, servicio `App\Services\Movimientos\ReasignarTrasReingreso`). La Baja sigue siendo unidireccional y el Alta sola no devuelve nada; solo avisa «Reingreso detectado».

Decisiones del usuario en el reingreso: que reasigne **todo sin filtrar y sin excepción**, con un aviso previo que lo diga. Por eso reactiva todos los accesos inactivos (también los que ya lo estaban antes de la baja: el sistema no guarda cuáles apagó la baja). Límites que puse yo y le informé: de las asignaciones solo se reabren las cerradas en la fecha de la última baja, y lo que hoy tiene otra persona o no está disponible se omite y se lista.

**Why:** el usuario quiere que la guía describa un sistema coherente; cuando la revisión destapa un hueco del sistema, lo decide él y se corrige en `main` con commit propio.

**How to apply:** si se toca la baja/alta de colaboradores, mantener: baja unidireccional, devolución solo a pedido y con aviso. Tras cambiar comportamiento del sistema, anotar en `.avance/revision/` del paquete lo que la guía debe reflejar.

Relacionado: [[guia-usuario-avance]], [[control-acceso-seguridad-usuarios]]
