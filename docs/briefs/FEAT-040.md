# Feature Brief — FEAT-040

> Brief final del Arquitecto (2026-09-30). Consolida decisiones confirmadas en chat Ask / AgentSj (mapa permisos, columnas Excel MT-GH-04, retiros auto, historial por pestaña, edición libre). **No es borrador.**

## Identificacion

| Campo | Valor |
| --- | --- |
| ID | FEAT-040 |
| Modulo / area | Gestion humana — tablero **Reportes-Novedades** (`reportes_novedades`) |
| Titulo | Reportes-Novedades (Vacaciones, Incapacidades, Retiros, Permisos) con review Nómina |
| Solicitante | Usuario / AgentSj (chat 2026-09-30) |
| Fecha | 2026-09-30 |

## Objetivo

Dar a Gestion Humana un **tablero dedicado** para registrar y exportar las **cuatro hojas de novedades** del Excel operativo MT-GH-04 (`NOVEDADES … .xlsm`), y a **Nómina** un canal de **review** solo sobre columnas finales (observaciones Nómina / campos exclusivos), sin workflow de estados.

Hoy el proceso vive en Excel (hojas VACACIONES / INCAPACIDADES / RETIROS / PERMISOS-LICENCIA). Este módulo lo materializa en la plataforma: GH escribe columnas GH; Nómina revisa columnas Nómina; ambas roles exportan; los **Retiros** se alimentan automáticamente al desvincular (Ficha individual o Masivos) y se anulan al revertir.

## Decisiones de negocio (ley — no reabrir)

| # | Tema | Decision |
| --- | --- | --- |
| 1 | Nombre UI tablero | **Reportes-Novedades** (label exacto). |
| 2 | Board key / hogar | `reportes_novedades`; hogar sidebar `gestion_humana`. |
| 3 | Pestañas | Exactas: **Vacaciones**, **Incapacidades**, **Retiros**, **Permisos**. |
| 4 | Permisos | Por hoja: `.view` / `.edit` / `.review` + `view.board.gestion_humana.reportes_novedades`. Ver § Permisos. |
| 5 | Admin UI | Grupo **Gestion humana** → subgroup **Reportes-Novedades** (incluye `.review`; usuarios Nómina se asignan ahí). Board en *Ver tableros*. |
| 6 | Implicaciones | `edit` ⇒ `view`; `review` ⇒ `view` + export; `edit` **no** escribe cols Nómina; `review` **no** crea/borra ni muta cols GH. |
| 7 | Edicion | **Libre siempre** (sin borrador/enviado ni máquina de estados). |
| 8 | Historial | Auditoría **dentro de cada pestaña** (modal/panel); **no** pestaña global Auditoría del tablero. |
| 9 | Audit escritura | Todas las mutaciones + export → `SystemAuditService` vía wrapper `ReportesNovedadesAuditLogService`. |
| 10 | Export | `BaseExport` + `<x-export-excel>`; GH y Nómina exportan (con `.view` o `.review`). |
| 11 | Fuera Excel | Hojas **BD / BD FERIADOS / Tabla1** del xlsm: **fuera de alcance** (sin pantallas). |
| 12 | Retiros auto | Alta automática al crear followup de desvinculación (Ficha individual **o** Masivos) en `EmployeeTerminationFollowupService`. Prefill desde ficha/seguimiento. Columna Nómina vacía hasta review. |
| 13 | Revertir | Revertir desvinculación **anula / soft-delete** la fila Retiros vinculada. |
| 14 | Catalogos | Fijos por hoja (desde MT-GH-04); ver § Catálogos. No pantallas de administración de catálogo en V1. |
| 15 | UI tecnica | DataTables `serverSide: true`; `<x-searchable-select>` para catálogos; **sin** Select2; **sin** `excelHtml5`. |
| 16 | Lookup | Cédula → Ficha (nombre, cargo, destino, fecha ingreso según aplique). |

## Decisiones tecnicas del Arquitecto

| Tema | Decision | Justificacion |
| --- | --- | --- |
| Modulo | Area GH, board `reportes_novedades`, tabs `vacaciones` / `incapacidades` / `retiros` / `permisos` | Mismo patrón Desvinculaciones / Cursos / Comercial. |
| Permisos storage | Keys en `system_permissions` (como Ficha/Desvinculaciones/Cursos GH) + `boards` + `board_canonical_areas` + `reportes_novedades_tabs` + Admin subgroup | Consistente con tableros GH de área única; labels legibles Admin. |
| Access | `ReportesNovedadesAccessService`: `edit`⇒`view`, `review`⇒`view`; `canEdit*` solo cols GH; `canReview*` solo cols Nómina; `canExport*` = view∨review; bypass `manage.users` | Extiende modelo Comercial view/edit con tercer verbo `review`. |
| Sidebar board | Visible con `view.board…reportes_novedades` **o** cualquier `.view`/`.edit`/`.review` de hoja (patrón Gestión Clientes) | Nómina puede operar sin marcar board explícito si tiene `.review` (recomendado asignar board + review juntos). |
| Persistencia | **4 tablas** tipadas (una por hoja), no polimórfica única | Columnas distintas por Excel; ownership GH/Nómina claro; índices y soft-delete Retiros simples. |
| Soft delete | Solo tabla **Retiros** (`SoftDeletes`) para anular al revertir; resto: hard delete con audit si `edit` | Cumple decisión 13 sin complicar Vacaciones/Incapacidades/Permisos. |
| FK Retiros | `employee_termination_followup_id` nullable unique (entre no-deleted); también FKs opcionales a ficha entry / periodo si el Feature las necesita para prefill | Unique evita doble fila por el mismo followup; nullable permite altas manuales GH sin desvinculación. |
| Hook create | Tras `ensureForClosedPeriod` crea fila Retiros si no existe (idempotente) | Misma puerta para Ficha individual y Masivos. |
| Hook revert | En `EmployeeTerminationFollowupService::revert`, soft-delete fila Retiros por FK followup **antes/con** el delete del followup | Evita huérfanos; columna Nómina no se conserva operativa (fila anulada). |
| Catalogos | Constantes / `config/reportes_novedades.php` (arrays label→value); UI vía `<x-searchable-select>` | Fijos V1; sin tabla BD ni pestaña parámetros. |
| Destino lookup | Mapear a **cliente** de ficha (vía entry/requisition/`clientName` o equivalente documentado en módulo) | Excel “destino”; ficha no tiene columna `destino`. |
| Historial UI | Endpoint JSON filtrado `audit_logs` por `module=reportes_novedades` + `auditable` de la fila (o metadata sheet+id); modal/panel en cada pestaña | Sin pestaña Auditoría; lectura acotada a quien tenga `view`/`edit`/`review` de esa hoja. |
| Audit config | Entrada `reportes_novedades` en `config/audit.php`; wrapper `module=reportes_novedades`, `area=gestion_humana` | Patrón Desvinculaciones / Ficha. |
| Rutas | Grupo en `routes/areas/gestion_humana.php` | Ownership GH; no tocar `web.php` salvo require ya existente. |
| Controllers | Un controller shell + controllers/servicios por hoja **o** un `ReportesNovedades{Hoja}Controller` por pestaña (Feature elige; mismo módulo) | Evitar god-controller de 4 hojas. |
| Datatable | Servicio por hoja (query + format row) estilo `TerminationFollowupDatatableService` / Cursos | Listados operativos grandes. |
| Export | Clases `*Export` extends `BaseExport` por hoja (o una con columnas dinámicas por sheet); botón `<x-export-excel>` | Estándar proyecto; respeta columns visibles según rol (GH ve/exporta GH+Nómina si filled; review exporta todo de la hoja). Ambos roles exportan **todas** las columnas de la hoja (GH + Nómina) para el corte operativo — **escritura** sí está segregada. |
| Form Requests | Separar `Store/Update` GH vs `ReviewUpdate` Nómina | Enforce ownership de columnas en validación, no solo en UI. |
| Nav | Extender `NavigationResolver`, `SidebarVisibilityService`, `User::defaultReportesNovedadesBoardUrl()` | Igual Ficha / Desvinculaciones. |
| Migrate | Solo `php artisan migrate` incremental. **Prohibido** `migrate:fresh` / wipe. | Protección de datos. |
| Slice | Task Cards verticales T1–T4 (ver § Slice) | Shared-files en T1; hooks Desvinculaciones en T4. |

## Alcance

### Incluye

- Tablero sidebar **Reportes-Novedades** en Gestion humana, con 4 pestañas nombradas arriba.
- Permisos nuevos (board + 12 funcionales) + Admin UI subgroup + sync seeder/`PermissionCatalog`.
- CRUD GH por hoja (crear / editar / eliminar filas) sobre columnas GH; edición libre.
- Review Nómina por hoja (PATCH solo columnas Nómina).
- Lookup cédula → datos Ficha para prefill.
- Catálogos fijos (novedad / tipo incapacidad / motivo retiro / novedad permisos) con searchable-select.
- DataTables server-side + filtros simples (texto cédula/nombre, rango fechas inicio/fin o fecha retiro según hoja).
- Export Excel por hoja (`BaseExport` + `<x-export-excel>`).
- Modal/panel **Historial** por pestaña (eventos audit de esa hoja / fila).
- Alta automática Retiros al crear followup; soft-delete al revertir desvinculación.
- Auditoría de create/update/delete/review/export (+ event auto-retiro).
- Tests PHPUnit (permisos, ownership columnas, lookup, CRUD, review, export gate, retiros auto, revert soft-delete).
- Docs: `docs/modules/reportes-novedades.md` + `docs/user/reportes-novedades.md` (+ ACCESS / INDEX / nota en desvinculaciones). Documentador al cierre.

### Fuera de alcance

- Import masivo `.xlsm` / pegar Excel.
- Dashboard KPI del tablero.
- Correos / notificaciones.
- Motor de días por feriados (BD FERIADOS) o pantallas BD / Tabla1.
- Workflow estados (borrador → enviado → cerrado).
- Pestaña global Auditoría dentro del tablero.
- Administración UI de catálogos (quedan fijos en config).
- Roles Spatie nuevos (`nomina`, etc.): solo permisos asignables en Admin.
- Backfill histórico desde Excel legacy.
- Cambiar reglas de terminate/Masivos salvo hooks create/revert Retiros.
- Select2 / `excelHtml5`.

## Reglas de negocio

### Tablero y acceso

1. Labels exactos: tablero **Reportes-Novedades**; pestañas **Vacaciones** / **Incapacidades** / **Retiros** / **Permisos**.
2. Visibilidad sidebar: `view.board.gestion_humana.reportes_novedades` **o** cualquier permiso funcional de hoja; bypass `manage.users` / super-admin.
3. Entrar a una pestaña: `.view` **o** `.edit` **o** `.review` de esa hoja (o bypass).
4. Mutar columnas GH (create/update/delete fila): `.edit` de la hoja.
5. Mutar columnas Nómina: `.review` de la hoja.
6. Export: `.view` **o** `.review` (o `.edit` vía implicación view) de la hoja.
7. Botones crear/eliminar y campos GH: solo UI con `edit`. Campos Nómina: solo UI con `review` (editables); con solo `view` o solo `edit`, columnas Nómina **solo lectura** (o ocultas si vacías — Feature elige; default: visibles RO).
8. Usuario con solo `review` (Nómina): ve listado + export + edita cols Nómina; **no** alta/baja ni cambia GH.

### Edicion libre

9. Sin estados de flujo. Una fila creada es editable de inmediato según permisos.
10. Validaciones de integridad (fechas, catálogos, required mínimos) aplican siempre; no hay “enviar a Nómina”.

### Lookup Ficha

11. Al ingresar/blur cédula (create/edit GH): resolver empleado en Ficha.
12. Prefill: `employee_name`, `cargo` (posición), `destino` (cliente), y en Retiros también `fecha_ingreso` + `tipo` (tipo empleado si existe en ficha/perfil; si no hay campo canónico, nullable editable GH).
13. Si cédula no existe en Ficha: permitir alta manual con mensaje (no bloquear V1) **excepto** Retiros auto (siempre desde followup). Feature documenta si lookup fallido deja campos vacíos editables.
14. Lookup usable con `edit` (y opcionalmente con `view` solo lectura de datos — no requerido).

### Vacaciones (cols GH)

15. Campos GH: `document_number`, `employee_name`, `cargo`, `destino`, `novedad` (catálogo Vacaciones), `dias_novedad`, `fecha_inicio`, `fecha_fin`, `observaciones`.
16. Col Nómina: `observacion_nomina` (texto, ej. OK).

### Incapacidades (cols GH)

17. Campos GH: `document_number`, `employee_name`, `cargo`, `destino`, `tipo_incapacidad` (catálogo), `dias`, `fecha_inicio`, `fecha_fin`, `fecha_recepcion`, `fecha_devolucion`, `observacion_devolucion`, `fecha_registro_control_roll`, `fecha_envio_final`, `novedad_control_roll`, `extemporanea` (sí/no), `observaciones`.
18. Cols Nómina: `observacion_nomina`, `dias_entrega`.

### Retiros (cols GH)

19. Campos GH: `document_number`, `employee_name`, `fecha_ingreso`, `tipo`, `cargo`, `destino`, `novedad` (típica **RETIRO**; catálogo fijo o valor fijo + editable según Feature — default valor inicial `RETIRO`), `fecha_retiro`, `motivo_retiro` (catálogo motivos; **solo aplica a retiro**), `observaciones`, FK `employee_termination_followup_id`.
20. Col Nómina: `observacion_nomina` (campo NOMINA del Excel).
21. Alta **automática** al crear followup: prefill desde snapshots followup + ficha/periodo (`document_number`, `full_name`→employee_name, `position_name`→cargo, destino vía ficha, `termination_date`→fecha_retiro, motivo si se puede mapear desde causal — si no mapea, dejar `motivo_retiro` null para completar GH; `observacion_nomina` null).
22. Idempotencia: un followup → como máximo una fila Retiros activa (no soft-deleted).
23. Alta **manual** GH permitida (sin FK) para casos no ligados a desvinculación plataforma.
24. Al **revertir** desvinculación: soft-delete de la fila Retiros con ese `employee_termination_followup_id`.
25. Filas soft-deleted: **no** aparecen en DT/export por defecto.

### Permisos (cols GH)

26. Campos GH: `document_number`, `employee_name`, `tipo`, `cargo`, `novedad` (catálogo Permisos), `dias_novedad`, `fecha_inicio`, `fecha_fin`, `marca_gh` (boolean o flag operativo; default false/null).
27. Col Nómina: `observacion_nomina`.
28. Nota: pestaña UI **Permisos** = hoja Excel PERMISOS-LICENCIA.

### Catalogos fijos (MT-GH-04)

29. **Vacaciones — novedad:** `VACACIONES DISF`, `VACACIONES COMP`.
30. **Incapacidades — tipo_incapacidad:** `EG`, `ACC/TRANS`, `ARL`, `LP`, `LM`.
31. **Retiros — motivo_retiro:** `RENUNCIA`, `SIN JUSTA CAUSA`, `CON JUSTA CAUSA`, `TERMINACION DE CONTRATO`, `FALLECIMIENTO`, `PERIODO DE PRUEBA`. Novedad típica: `RETIRO`.
32. **Permisos — novedad:** `LICENCIAS NO REMUN / AUSENCIAS`, `SANCIONES`, `PERMISOS REMUNERADOS`, `DIA DE LA FAMILIA`, `MATRIMONIO`, `LUTO`.

### Auditoria

33. Eventos sugeridos (taxonomía Feature puede afinar nombres): `{sheet}_novedad` actions `create` / `update` / `delete` / `review`; `export` action `{sheet}_excel`; `retiro_auto` action `create` / `annul` (desde hook desvinculación).
34. Metadata: ids, cédula, sheet, before/after de campos mutados (truncado estándar audit); sin PII excesiva innecesaria.
35. Historial en pestaña: lista cronológica legible (usuario, fecha, acción, resumen cambios).

## Permisos (`config/access.php`)

### Keys concretas

| Permiso | Rol(es) | Descripcion |
| --- | --- | --- |
| `view.board.gestion_humana.reportes_novedades` | `super-admin` (todos); asignar Admin | Ver tablero **Reportes-Novedades** en sidebar GH |
| `reportes_novedades.vacaciones.view` | Paquete GH / consulta | Vacaciones: Ver / filtrar / export |
| `reportes_novedades.vacaciones.edit` | Paquete GH | Vacaciones: CRUD columnas GH |
| `reportes_novedades.vacaciones.review` | Paquete Nómina | Vacaciones: editar columnas Nómina (+ ver/export) |
| `reportes_novedades.incapacidades.view` | Paquete GH / consulta | Incapacidades: Ver / filtrar / export |
| `reportes_novedades.incapacidades.edit` | Paquete GH | Incapacidades: CRUD columnas GH |
| `reportes_novedades.incapacidades.review` | Paquete Nómina | Incapacidades: review Nómina |
| `reportes_novedades.retiros.view` | Paquete GH / consulta | Retiros: Ver / filtrar / export |
| `reportes_novedades.retiros.edit` | Paquete GH | Retiros: CRUD columnas GH (manual) |
| `reportes_novedades.retiros.review` | Paquete Nómina | Retiros: review Nómina |
| `reportes_novedades.permisos.view` | Paquete GH / consulta | Permisos: Ver / filtrar / export |
| `reportes_novedades.permisos.edit` | Paquete GH | Permisos: CRUD columnas GH |
| `reportes_novedades.permisos.review` | Paquete Nómina | Permisos: review Nómina |

**Paquetes de asignación (documentar en Admin / doc usuario; no roles nuevos):**

| Perfil | Permisos tipicos |
| --- | --- |
| GH operativo | board + las 4× (`view`+`edit`) |
| Nómina | board + las 4× `.review` (implican view/export en AccessService) |
| Solo consulta hoja | `.view` (+ board) |

### Implicaciones en AccessService (obligatorio)

| Permiso otorgado | Efecto runtime |
| --- | --- |
| `.edit` | Puede `.view` (aunque Spatie no tenga view marcado) |
| `.review` | Puede `.view` + export; **no** implica `.edit` |
| `.edit` | **No** puede escribir cols Nómina |
| `.review` | **No** puede create/delete ni mutar cols GH |

Side-effect Retiros auto / annul en desvinculación: corre bajo el flujo ya autorizado de Desvinculaciones/Ficha (**no** exige `reportes_novedades.retiros.edit` en el actor del terminate/revert).

### Cambios en `config/access.php`

- `system_permissions`: agregar las 12 keys funcionales con labels Admin legibles (`Vacaciones: Ver`, `Vacaciones: Editar`, `Vacaciones: Revisar (Nomina)`, …).
- `boards`: `'reportes_novedades' => 'Reportes-Novedades'`.
- `board_canonical_areas`: `reportes_novedades` → `home => gestion_humana`, `base_area_tab => false`.
- `reportes_novedades_tabs`: `vacaciones => Vacaciones`, `incapacidades => Incapacidades`, `retiros => Retiros`, `permisos => Permisos`.
- `admin_permission_groups` → `other_areas.gestion_humana`:
  - `boards.permissions`: + `view.board.gestion_humana.reportes_novedades`
  - subgroup `reportes_novedades` label **Reportes-Novedades** con las 12 keys (view/edit/review × 4).
- `PermissionCatalog`: aceptar board solo en `gestion_humana` (mismo patrón Desvinculaciones).

### Seeders

- `RoleAndPermissionSeeder` / `PermissionCatalog::sync()`: crea permisos; `super-admin` recibe todos.
- **No** meter el paquete en `administrador` / `usuario` por defecto; asignación manual Admin.
- Bypass runtime: `manage.users` en `ReportesNovedadesAccessService`.

## Rutas

Archivo: `routes/areas/gestion_humana.php` (grupo nuevo; **no** editar `routes/web.php` salvo require ya existente).

Prefijo: `/gestion-humana/reportes-novedades` · nombre `gestion-humana.reportes-novedades.`

Middleware grupo: `auth`, `active`, `password.changed` (igual resto GH).

| Metodo | URI | Nombre | Permiso / notas |
| --- | --- | --- | --- |
| GET | `/` | `index` | Redirect a primera pestaña visible |
| GET | `/vacaciones` | `vacaciones` | Vista. view∨edit∨review vacaciones |
| GET | `/vacaciones/datatable` | `vacaciones.datatable` | JSON server-side |
| GET | `/vacaciones/exportar` | `vacaciones.export` | Excel. view∨review |
| GET | `/vacaciones/historial` | `vacaciones.historial` | JSON audit hoja/fila (query `id` opcional) |
| POST | `/vacaciones` | `vacaciones.store` | create GH. edit |
| PATCH | `/vacaciones/{row}` | `vacaciones.update` | update GH. edit |
| PATCH | `/vacaciones/{row}/review` | `vacaciones.review` | cols Nómina. review |
| DELETE | `/vacaciones/{row}` | `vacaciones.destroy` | edit |
| GET | `/incapacidades` | `incapacidades` | Vista |
| GET | `/incapacidades/datatable` | `incapacidades.datatable` | |
| GET | `/incapacidades/exportar` | `incapacidades.export` | |
| GET | `/incapacidades/historial` | `incapacidades.historial` | |
| POST | `/incapacidades` | `incapacidades.store` | edit |
| PATCH | `/incapacidades/{row}` | `incapacidades.update` | edit |
| PATCH | `/incapacidades/{row}/review` | `incapacidades.review` | review |
| DELETE | `/incapacidades/{row}` | `incapacidades.destroy` | edit |
| GET | `/retiros` | `retiros` | Vista |
| GET | `/retiros/datatable` | `retiros.datatable` | excluye soft-deleted |
| GET | `/retiros/exportar` | `retiros.export` | |
| GET | `/retiros/historial` | `retiros.historial` | |
| POST | `/retiros` | `retiros.store` | alta manual. edit |
| PATCH | `/retiros/{row}` | `retiros.update` | edit GH |
| PATCH | `/retiros/{row}/review` | `retiros.review` | review |
| DELETE | `/retiros/{row}` | `retiros.destroy` | soft o hard: **soft-delete** preferible para alinear con annul auto. edit |
| GET | `/permisos` | `permisos` | Vista |
| GET | `/permisos/datatable` | `permisos.datatable` | |
| GET | `/permisos/exportar` | `permisos.export` | |
| GET | `/permisos/historial` | `permisos.historial` | |
| POST | `/permisos` | `permisos.store` | edit |
| PATCH | `/permisos/{row}` | `permisos.update` | edit |
| PATCH | `/permisos/{row}/review` | `permisos.review` | review |
| DELETE | `/permisos/{row}` | `permisos.destroy` | edit |
| POST | `/lookup` | `lookup` | Body `document_number` → datos Ficha. edit (cualquier hoja con edit) o permiso board+funcional Feature |

**Hooks sin rutas nuevas** (Desvinculaciones / Ficha):

| Flujo | Punto de enganche | Efecto Retiros |
| --- | --- | --- |
| Create followup | `EmployeeTerminationFollowupService::ensureForClosedPeriod` (tras create) | `ensureRetiroForFollowup` idempotente |
| Revertir | `EmployeeTerminationFollowupService::revert` | soft-delete Retiros por FK + audit `annul` |

## Base de datos

Solo migración(es) **nuevas**. Prohibido `migrate:fresh`. Multi-driver (MySQL + sqlite tests).

Convenciones comunes en las 4 tablas: `id`, `created_by` / `updated_by` (FK users `nullOnDelete`), `timestamps`; índices `(document_number)`, fechas relevantes.

### Tabla `reportes_novedades_vacaciones`

| Columna | Tipo | Ownership |
| --- | --- | --- |
| `document_number` | string(50) | GH |
| `employee_name` | string(255) | GH |
| `cargo` | string(150) nullable | GH |
| `destino` | string(255) nullable | GH |
| `novedad` | string(80) | GH (catálogo) |
| `dias_novedad` | unsignedSmallInteger / decimal | GH |
| `fecha_inicio` | date | GH |
| `fecha_fin` | date | GH |
| `observaciones` | text nullable | GH |
| `observacion_nomina` | string(255) nullable | **Nómina** |

### Tabla `reportes_novedades_incapacidades`

| Columna | Tipo | Ownership |
| --- | --- | --- |
| `document_number` | string(50) | GH |
| `employee_name` | string(255) | GH |
| `cargo` | string(150) nullable | GH |
| `destino` | string(255) nullable | GH |
| `tipo_incapacidad` | string(40) | GH (catálogo) |
| `dias` | unsignedSmallInteger / decimal | GH |
| `fecha_inicio` | date | GH |
| `fecha_fin` | date | GH |
| `fecha_recepcion` | date nullable | GH |
| `fecha_devolucion` | date nullable | GH |
| `observacion_devolucion` | text nullable | GH |
| `fecha_registro_control_roll` | date nullable | GH |
| `fecha_envio_final` | date nullable | GH |
| `novedad_control_roll` | string(120) nullable | GH |
| `extemporanea` | boolean default false | GH |
| `observaciones` | text nullable | GH |
| `observacion_nomina` | string(255) nullable | **Nómina** |
| `dias_entrega` | unsignedSmallInteger / decimal nullable | **Nómina** |

### Tabla `reportes_novedades_retiros`

| Columna | Tipo | Ownership |
| --- | --- | --- |
| `document_number` | string(50) | GH |
| `employee_name` | string(255) | GH |
| `fecha_ingreso` | date nullable | GH |
| `tipo` | string(80) nullable | GH |
| `cargo` | string(150) nullable | GH |
| `destino` | string(255) nullable | GH |
| `novedad` | string(80) default `RETIRO` | GH |
| `fecha_retiro` | date nullable | GH |
| `motivo_retiro` | string(80) nullable | GH (catálogo) |
| `observaciones` | text nullable | GH |
| `employee_termination_followup_id` | FK nullable → `employee_termination_followups.id` | sistema; `nullOnDelete` |
| `personal_requisition_ficha_entry_id` | FK nullable → ficha entries | opcional prefill |
| `observacion_nomina` | string(255) nullable | **Nómina** |
| `deleted_at` | softDeletes | sistema / annul |

Unique compuesto recomendado: unique (`employee_termination_followup_id`) donde no null — en MySQL unique permite varios NULL; en sqlite OK. Feature valida unicidad activa en servicio.

### Tabla `reportes_novedades_permisos`

| Columna | Tipo | Ownership |
| --- | --- | --- |
| `document_number` | string(50) | GH |
| `employee_name` | string(255) | GH |
| `tipo` | string(80) nullable | GH |
| `cargo` | string(150) nullable | GH |
| `novedad` | string(80) | GH (catálogo) |
| `dias_novedad` | unsignedSmallInteger / decimal | GH |
| `fecha_inicio` | date | GH |
| `fecha_fin` | date | GH |
| `marca_gh` | boolean default false | GH |
| `observacion_nomina` | string(255) nullable | **Nómina** |

## Capas a implementar

- [ ] Migración(es) — 4 tablas
- [ ] Modelo(s) — 4 modelos Eloquent (+ SoftDeletes Retiros)
- [ ] Config — `config/reportes_novedades.php` (catálogos) + `config/access.php` + `config/audit.php`
- [ ] Access — `ReportesNovedadesAccessService`
- [ ] Audit — `ReportesNovedadesAuditLogService`
- [ ] Servicios — lookup Ficha; CRUD/review por hoja; DatatableServices; `ReportesNovedadesRetiroSyncService` (ensure/annul) enganchado en `EmployeeTerminationFollowupService`
- [ ] Controlador(es) — shell + por hoja (o agrupado)
- [ ] Form Request(s) — store/update GH, review, lookup
- [ ] Exports — 4× `BaseExport` (o factory)
- [ ] Vista(s) Blade — layout tablero + subnav 4 tabs + 4 listados (DT, filtros, modal form, historial, export)
- [ ] JavaScript — DT serverSide + Alpine modales (Vite entry si aplica)
- [ ] Nav — `NavigationResolver`, `SidebarVisibilityService`, helper URL en `User`
- [ ] Hook — `EmployeeTerminationFollowupService` create + revert
- [ ] Tests PHPUnit
- [ ] Docs técnicas/usuario (Documentador)

## Componentes reutilizables

- `<x-searchable-select>` para catálogos de novedad / tipo / motivo.
- `<x-export-excel>` + `App\Exports\BaseExport`.
- Estilo nav pills / `module-tab` / `module-subnav` (branding GH).
- Botones icon-only: `.req-manage-filters__icon-btn` / `.cursos-catalogo-page__icon-btn`.
- Patrón audit wrapper (`DesvinculacionesAuditLogService` / `EmployeeFichaAuditLogService`).
- Lookup Ficha: reutilizar queries de ficha activa/perfil (Entry + Profile + requisition client) — **no** Select2.
- **No** Repository.
- **No** `excelHtml5`.

## Documentacion a actualizar

- [ ] `docs/modules/reportes-novedades.md` (**crear** — Documentador)
- [ ] `docs/user/reportes-novedades.md` (**crear** — Documentador; Objetivo, Alcance, Definiciones, Responsabilidades, Desarrollo, Control de cambios)
- [ ] `docs/modules/desvinculaciones.md` — nota: create/revert followup sincroniza Retiros FEAT-040
- [ ] `docs/user/desvinculaciones.md` — mención breve si aplica
- [ ] `docs/ACCESS_CONTROL.md` — board + permisos view/edit/review
- [ ] `docs/modules/audit-log.md` — fila módulo `reportes_novedades`
- [ ] `docs/INDEX.md` — enlace módulo nuevo
- [ ] `docs/ARCHITECTURE.md` — ownership rutas/vistas/controllers GH
- [ ] `README.md` — solo si lista módulos GH explícitamente

## Archivos compartidos (`shared-files`)

| Archivo | Motivo |
| --- | --- |
| `config/access.php` | Board, tabs, system_permissions, admin groups |
| `config/audit.php` | Módulo `reportes_novedades` |
| `routes/areas/gestion_humana.php` | Rutas del tablero |
| `app/Services/Navigation/NavigationResolver.php` | Link sidebar |
| `app/Services/Navigation/SidebarVisibilityService.php` | Visibilidad board |
| `app/Models/User.php` | `defaultReportesNovedadesBoardUrl()` |
| `database/seeders/RoleAndPermissionSeeder.php` | Sync catálogo (indirecto) |
| `app/Services/GestionHumana/EmployeeTerminationFollowupService.php` | Hook ensure Retiros + soft-delete en revert |
| `docs/ACCESS_CONTROL.md` / `docs/INDEX.md` / `docs/ARCHITECTURE.md` | Docs compartidas al cierre |

Flag `shared-files: true` en `docs/TASKS.md` / Task Cards. Un solo agente a la vez sobre estos archivos.

## Criterios de aceptacion

1. Usuario con board (+ funcional) ve **Reportes-Novedades** en sidebar GH; sin permisos no lo ve (salvo bypass).
2. Cuatro pestañas con labels exactos; solo aparecen las que el usuario puede acceder (view∨edit∨review).
3. GH con `.edit` crea/edita/elimina filas en columnas GH; intento de enviar cols Nómina en update GH → ignorado o 422 (no persiste).
4. Nómina con `.review` edita solo cols Nómina; store/destroy y PATCH GH → 403.
5. Solo `.view`: listado + export + historial RO; sin mutaciones.
6. Lookup cédula prefilla nombre/cargo/destino/(fecha ingreso en Retiros).
7. Catálogos searchable-select limitados a valores fijos del brief.
8. DT server-side en las 4 hojas; filtros texto + fechas básicos.
9. Export Excel por hoja con `BaseExport` / `<x-export-excel>`; disponible a view y review.
10. Modal/panel Historial por pestaña muestra eventos audit de la hoja/fila; **no** hay pestaña Auditoría del tablero.
11. Desvincular (Ficha o Masivos) crea fila Retiros (prefill; `observacion_nomina` null); segunda llamada no duplica.
12. Revertir desvinculación soft-deletea la fila Retiros; desaparece del listado/export.
13. Sin Select2; sin excelHtml5; sin import xlsm; sin estados workflow.
14. Eventos audit visibles (admin global y/o historial pestaña) para mutaciones, review, export, auto-retiro/annul.
15. Tests listados abajo en verde.

## Tests minimos

| Area | Caso |
| --- | --- |
| Permisos board | Sin acceso → 403 index/tabs |
| edit⇒view | Solo `.edit` sin Spatie `.view` → puede ver DT |
| review⇒view | Solo `.review` → ve DT + export; no store |
| Ownership GH | PATCH update con `observacion_nomina` no persiste / 422 |
| Ownership Nómina | PATCH review con campo GH no persiste / 422 |
| Review destroy | `.review` DELETE → 403 |
| Catalogo | novedad inválida → 422 |
| Lookup | cédula ficha → JSON con nombre/cargo/destino |
| CRUD | store + update + destroy Vacaciones (o una hoja representativa + smoke otras) |
| Review | PATCH review persiste solo cols Nómina |
| Export | GET export 200 con view y con review; 403 sin ellos |
| Retiro auto | `ensureForClosedPeriod` → existe fila Retiros FK |
| Idempotencia | segundo ensure no duplica activa |
| Revert | `revert` → Retiros soft-deleted |
| Historial | endpoint historial 200 con eventos tras create |
| Bypass | `manage.users` accede y muta |

Correr: `php artisan test --compact` filtrando `ReportesNovedades` / retiros sync.

## Validacion local

1. `php artisan migrate` (sin fresh).
2. Asignar paquete GH y paquete Nómina a usuarios de prueba en Admin (subgroup Reportes-Novedades).
3. Probar Vacaciones CRUD + review + export + historial.
4. Smoke Incapacidades / Permisos.
5. Desvincular uno (Ficha o Masivos) → aparece en Retiros; revertir → desaparece.
6. `php artisan test --compact` (suite afectada).
7. `vendor/bin/pint --dirty --format agent` tras PHP.

## Riesgos y dependencias

| Riesgo | Mitigacion |
| --- | --- |
| Shared-files nav/access + hook Desvinculaciones | T1 monopoliza access/nav; T4 monopoliza `EmployeeTerminationFollowupService`; no editar en paralelo |
| Mapeo causal desvinculación → `motivo_retiro` catálogo | Si no hay match 1:1, dejar null y GH completa; no inventar sin tabla de mapeo aprobada |
| Destino = cliente ficha | Documentar en módulo; si ficha sin cliente, destino vacío editable |
| Soft-delete Retiros vs unique FK | Unique solo sobre no-deleted o validación en servicio; tests de re-desvincular mismo empleado más adelante (nuevo followup/periodo) |
| Volumen 4 hojas × DT/export | Servicios por hoja; evitar N+1; índices cédula/fechas |
| Hostinger timeout export grande | Filtros por fecha V1; documentar riesgo; sin cola |

## Dependencias de codigo existente

- `EmployeeTerminationFollowupService` (`ensureForClosedPeriod`, `revert`)
- Modelos Ficha / Profile / Entry / Period (lookup + prefill)
- `DesvinculacionesAccessService` / flujo Masivos + Ficha terminate (sin cambiar permisos)
- `SystemAuditService` + patrón wrappers GH
- `CommercialAccessService` (referencia implicaciones view/edit)
- `BaseExport`, `<x-export-excel>`, `<x-searchable-select>`
- Docs: `docs/modules/desvinculaciones.md`, `docs/modules/audit-log.md`, `docs/ACCESS_CONTROL.md`

## Slice sugerido para AgentSj (Task Cards)

| Task | Contenido | shared-files |
| --- | --- | --- |
| **T1** | `config/access.php` + audit + catálogos config; migraciones 4 tablas + modelos; `ReportesNovedadesAccessService`; `ReportesNovedadesAuditLogService`; nav sidebar + shell tabs vacíos + redirect; seeder sync | **Si** (access, audit, rutas shell, nav, User) |
| **T2** | Vacaciones + Permisos: DT, CRUD GH, review Nómina, export, historial modal, FormRequests, tests hoja | No (salvo rutas del grupo ya abierto en T1) |
| **T3** | Incapacidades completa (mismo patrón; más columnas) + tests | No |
| **T4** | Retiros CRUD/review/export/historial + `ReportesNovedadesRetiroSyncService` + hooks `EmployeeTerminationFollowupService` (create/revert) + tests auto/annul + cierre tests permisos transversales | **Parcial** (`EmployeeTerminationFollowupService`) |

Documentador tras Revisor.

## Aprobacion

- [x] Analista — vacíos cerrados (decisiones confirmadas chat Ask 2026-09-30; Analista skip en run log)
- [x] Arquitecto — brief final (`docs/briefs/FEAT-040.md`)
- [ ] Usuario — confirmacion explícita del brief (pendiente AgentSj / usuario)
