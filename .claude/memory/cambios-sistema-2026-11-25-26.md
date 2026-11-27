---
name: cambios-sistema-2026-11-25-26
description: "Bitácora 2026-11-25/26: software por cargo y categorías, renombres de usuarios externos, módulo Usuarios externos con formulario PDF y firma, DAZA, Azure AD, campo CrudSelect y fin del módulo Inventario; commits y qué quedó pendiente"
metadata:
  type: project
---

Trabajo hecho con el usuario el 2026-11-25 y 26, en orden (commits en `main`, sin push; el push lo hace él):

1. `8dcc399` **Software por cargo y categorías.** `software_areas` → `software_cargos` (`SoftwareCargo`); tabla `categorias_software` (`CategoriaSoftware`, `codigo` = valor del enum `CategoriasSoftware`); acciones «Asignar por cargo» y «Asignar por categoría» (servicio `AsignarSoftwareEnBloque`). Detalle: [[software-por-cargo-y-categorias]].
2. `e076f66` test de alta de usuario: faltaba `telefono` (obligatorio desde bf02e9f).
3. `114cbae` → luego `8ed81f9` **Soporte técnico externo:** `usuarios_externos` pasó a `personal_externo_soporte_tecnico` / `PersonalExternoSoporteTecnico`, cluster Equipos, título «Soporte externo»; FK de notas `personal_externo_soporte_id`.
4. `17681c2` factory de cargadores usaba `'danado'` (sin ñ): fallo aleatorio 1/6. Ahora toma `EstadoCargador::cases()`.
5. `31ebe9c` **Usuarios externos** (tabla `usuarios_externos`, cluster Usuarios) con recordatorio de fecha fin a los admins y **Formulario de Usuario Externo** en PDF con firma digital. Detalle: [[usuarios-externos-y-formulario]].
6. `0ccd766` el usuario ocultó el campo N° del formulario de usuarios externos (se sigue asignando solo).
7. `f4da280` (del usuario) enum `AmbienteDaza` (Producción, Contingencia, Test) y rótulo `DazProd-Nivel`/`DazCont-Nivel`/`DazTest-Nivel` en el formulario de alta/baja.
8. `0531a0f` + `36786d1` Azure AD: se quitó la opción «Local / No sincronizado» del formulario; el default pasó a «Local / Nube» (con test). La tabla y la ficha mantienen color y filtro de ese valor para cuentas antiguas.
9. `c7c9793` **Campo CrudSelect** (`App\Filament\Forms\Components\CrudSelect`): select propio con «+ Crear» y menú ⋮ Editar/Eliminar por opción; doc en `.Doc_Laravel_13/Documentacion Sistema/Componente-CrudSelect`.
10. `b64462f` **Se eliminó el módulo Inventario** (cluster, 4 resources y sus importadores). Modelo, micro, RAM y GPU se gestionan con CrudSelect en el formulario de equipo. Detalle: [[inventario-eliminado-crudselect]].
11. Descartado: una **versión 2 del formulario de alta/baja** (`FormAltaBajaV2`) se diseñó y se borró a pedido del usuario el 2026-11-26 (plantilla, test, rutas y `previewHtmlV2`). El formulario oficial sigue siendo `FormAltaBaja`.

Repo de la guía (`packages/capitalsafi/documentacion-sistema-icu`): `b067131` y anteriores ajustan permisos y URL al renombre del soporte externo.

**Why:** el usuario revisa cada parte antes del commit y quiere constancia de lo hecho para su otro equipo.

**How to apply:** tras `git pull` en otro equipo hay que correr `vendor/bin/sail artisan migrate:fresh --seed` (se editaron migraciones existentes) y así se regeneran los permisos Shield nuevos. Pendientes: textos y capturas de la guía para «Soporte externo» (ahora en Equipos) y el módulo nuevo «Usuarios externos»; el capítulo C6 debe incluir categorías de software y software por cargo. La guía también debe dejar de mencionar el módulo Inventario. Relacionado: [[commits-sin-co-authored-by]], [[guia-usuario-avance]].
