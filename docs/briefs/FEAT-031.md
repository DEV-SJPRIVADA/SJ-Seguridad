# Feature Brief — FEAT-031

> Brief final del Arquitecto (2026-09-14). Consolida `docs/briefs/FEAT-031-analyst.md` (incl. **Respuestas del usuario** 2026-09-14) + decisiones de negocio confirmadas. **No es borrador.**

## Identificacion

| Campo | Valor |
| --- | --- |
| ID | FEAT-031 |
| Modulo / area | Gestion humana — tablero **Desvinculaciones** (`desvinculaciones`) |
| Titulo | Tablero Desvinculaciones (Masivos + Seguimientos) |
| Solicitante | Usuario / AgentSj (chat 2026-09-14) |
| Fecha | 2026-09-14 |

## Objetivo

Dar a Gestion Humana un **tablero dedicado** junto a Ficha empleados para:

1. **Masivos** — procesar varias desvinculaciones en grilla (cédula → nombre si activo, fecha, plantilla Word, firma), cerrar vínculo como hoy en individual, registrar seguimiento, generar cartas y descargar **un ZIP** del lote.
2. **Seguimientos** — checklist post-retiro editable (autosave), con indicador de carta generada y **OK TODO** calculado.

Hoy solo existe desvinculación **individual** en Ficha (`terminate` + cartas por periodo) y **no** hay entidad de seguimiento. Este módulo materializa el Excel legacy dentro de la plataforma, sin correo y sin colores legacy en V1.

## Decisiones de negocio (ley — no reabrir)

| # | Tema | Decision |
| --- | --- | --- |
| 1 | Permisos | **Permisos nuevos** del tablero Desvinculaciones. Misma persona usa Masivos y Seguimientos en V1 (**paquete**; sin split de perfiles). |
| 2 | TIPO CARTA | Select de **plantillas Word de desvinculacion disponibles** (**una por fila**). |
| 3 | FECHA DESVINCULACION | Unifica ultimo dia / fecha retiro (UX). Causal, recontratable y observaciones: en Masivos **opcionales**; en Seguimientos **solo lectura**. |
| 4 | Fecha / tipo carta / firma | **Por fila**. |
| 5 | Fallos de lote | **Continuar**, terminar el lote y **reportar** fallos. |
| 6 | Fallo de carta | Dejar desvinculado **sin carta**, reportar; en Seguimientos campo **tiene carta generada si/no**. |
| 7 | ZIP / grilla | **Un ZIP** con cartas del lote; **limpiar** grilla Masivos al finalizar. |
| 8 | Modelo Seguimientos | Tabla **nueva**; crear al desvincular — **tambien desde desvinculacion individual en Ficha** (evitar huecos). |
| 9 | OK TODO | **Solo lectura**, automatico si los **8 checks** estan en verdadero. |
| 10 | Export Excel | **Fuera de V1**. |
| 11 | Colores legacy | **Sin colores**. |
| 12 | Tope / confirmacion | **Sin** tope de filas ni confirmacion previa. |
| 13 | Correo / auditoria | **Sin correo** V1; **si auditoria** (wrapper tipo ficha + `SystemAuditService`). |

## Decisiones tecnicas del Arquitecto

| Tema | Decision | Justificacion |
| --- | --- | --- |
| Modulo | Area GH, board key `desvinculaciones`, tabs `masivos` / `seguimientos` | Mismo patron que Ficha / Archivo / Plantillas Word. |
| Permisos Masivos vs Ficha | Masivos autoriza con `desvinculaciones.masivos` (**no** exige `ficha_empleados.terminate`). Individual Ficha **se mantiene** con `ficha_empleados.terminate`. | Usuario pidio permisos nuevos; no mezclar gate del tablero con el de Ficha. |
| Fecha unica Masivos | Campo UI **FECHA DESVINCULACION** → al cerrar periodo escribir **el mismo valor** en `last_work_day` y `termination_date`. | Unifica UX; el esquema de periodo sigue con ambas columnas. |
| Causal / rehire / notas Masivos | Opcionales. Causal vacia → `null`. Recontratable omitido → `null` (no forzar `false`). Notas vacias → `null`. | Distinto del Form Request individual (sigue required). Ajustar llamada a `closeActivePeriod` desde Masivos para no caste ar `is_rehireable` a false si viene ausente. |
| Plantilla + firma | **Requeridas** por fila en Masivos (intento de carta siempre). | Columna TIPO CARTA / FIRMA del flujo; fallo de render → desvinculado + `letter_generated=false` + reporte. |
| Plantilla unica | Un `template_id` por fila (no multi-plantilla en Masivos V1). | Decision usuario; multi queda en modal individual de Ficha. |
| Orquestador | Servicio `BulkTerminationService` (nombre final a criterio Feature): por fila close + sync perfil + crear seguimiento + generate letter; acumular docx; ZIP final. | Reutiliza `EmployeeFichaEmploymentPeriodService` + `TerminationLetterPackGeneratorService`. **No** Repository. |
| Fallos | Por fila: try/catch o resultado tipado; **no** abortar el lote; respuesta JSON con `ok[]` / `failed[]` + descarga ZIP si hay al menos 1 carta. | Decision usuario. Transaccion **por empleado** (no una sola TX del lote). |
| Seguimientos | Tabla `employee_termination_followups` (1:1 con periodo cerrado). | Snapshot de identificacion + checks; FK a periodo y ficha entry. |
| Carta si/no | Columna `letter_generated` boolean; true si pack Masivos o generate Ficha persiste path. | Fuente operativa en Seguimientos; al generar/descargar desde Ficha actualizar followup del periodo. |
| OK TODO | **No persistir**; accessor / calculo en API y UI. | Evita drift con los 8 checks. |
| Autosave | `PATCH` parcial por seguimiento (campos enviados); debounce cliente ~300–500 ms. | Patron grilla editable; un request por cambio estabilizado. |
| Lookup cedula | Solo empleado **activo** (`employment_status = activo` + periodo abierto) en ficha. | Decision / supuesto confirmado. |
| Regenerar carta V1 | Desde flujo existente Ficha (periodo cerrado + `ficha_empleados.terminate`). Seguimientos solo muestra flag + enlace opcional a ficha/periodo. | Evita duplicar UI de cartas en V1. |
| Nav | Extender `NavigationResolver`, `SidebarVisibilityService`, `User::defaultDesvinculacionesBoardUrl()`, `DesvinculacionesAccessService`. | Mismo tratamiento especial que `ficha_empleados` / `plantillas_word`. |
| Audit | Wrapper `DesvinculacionesAuditLogService` (`module=desvinculaciones`, `area=gestion_humana`) + entrada en `config/audit.php`. | Patron FEAT-025/026 ficha. |
| Rutas | Grupo nuevo en `routes/areas/gestion_humana.php` (mismo archivo GH). | Ownership area GH; no `routes/web.php` salvo require ya existente. |
| Export Excel | No en V1. | Decision usuario. |
| Slice sugerido | **2–3 Task Cards** verticales (ver plan AgentSj). Shared-files en T1. | Board + BD + Masivos + Seguimientos + hook Ficha es demasiado para un solo PR sin plan. |
| Migrate | Solo `php artisan migrate` incremental. **Prohibido** `migrate:fresh` / wipe. | Proteccion de datos. |

## Alcance

### Incluye

- Tablero sidebar **Desvinculaciones** en Gestion humana (junto a Ficha empleados), con pestañas **Masivos** y **Seguimientos**.
- Permisos nuevos + `PermissionCatalog` / Admin UI / seeder (`super-admin` recibe todos; paquete documentado para asignar en Admin a usuarios GH).
- Grilla Masivos: filas editables, lookup cedula → nombre, selects plantilla/firma, campos opcionales causal/recontratable/observaciones, boton **Desvincular**, reporte de fallos, ZIP, limpieza de grilla.
- Tabla y UI Seguimientos: checks, fecha entregado nomina, OK TODO calculado, carta generada si/no, lectura de causal/rehire/notas/cargo; autosave; filtros simples.
- Creacion automatica de seguimiento al desvincular en **Masivos** y en **Ficha individual** (`FichaEmpleadosController::terminate`).
- Actualizacion de `letter_generated` cuando Ficha genera carta del periodo.
- Auditoria de lote Masivos y de cambios relevantes en Seguimientos.
- Tests PHPUnit (permisos, lookup, lote parcial, followup desde individual, OK TODO, autosave).
- Documentacion nueva: `docs/modules/desvinculaciones.md` + `docs/user/desvinculaciones.md` (+ INDEX si aplica). Documentador al cierre.

### Fuera de alcance

- Export Excel de Seguimientos.
- Colores / semaforos del Excel legacy.
- Correo / notificaciones al desvincular.
- Split de perfiles (solo Masivos vs solo Seguimientos) en V1 — permisos existen separados para el futuro, pero el paquete se asigna junto.
- Multi-plantilla por fila en Masivos.
- Tope de filas o confirmacion SweetAlert previa al lote.
- Anular / revertir desvinculacion.
- Backfill masivo de seguimientos para periodos **ya cerrados** historicos (opcional post-V1).
- Reimpresion / generacion de cartas embebida en pestaña Seguimientos (usar Ficha).
- Importar Excel legacy de seguimientos.
- Cambiar validacion obligatoria del terminate **individual** en Ficha (sigue required causal/fechas/rehire).
- Integraciones a requisiciones / comercial / indicadores.
- Historial campo-a-campo de quien marco cada check (basta valor actual + audit event).

## Reglas de negocio

### Tablero y acceso

1. Etiquetas exactas: tablero **Desvinculaciones**; pestañas **Masivos** / **Seguimientos**.
2. Visibilidad sidebar: `view.board.gestion_humana.desvinculaciones` (hogar `gestion_humana`). Bypass total: `manage.users` / super-admin (mismo patron Ficha).
3. Entrar al tablero: `desvinculaciones.view` (o bypass). Ambas pestañas visibles al mismo perfil en V1.
4. Ejecutar lote Masivos: `desvinculaciones.masivos`.
5. Editar checks / fecha nomina en Seguimientos: `desvinculaciones.seguimientos.edit`.
6. Lectura de Seguimientos: con `desvinculaciones.view` (sin edit → solo lectura).

### Masivos

7. Columnas base de grilla: CEDULA | NOMBRE (solo lectura tras lookup) | FECHA DESVINCULACION | TIPO CARTA | FIRMA | Causal (opc.) | Recontratable (opc.) | Observaciones (opc.).
8. Agregar filas: boton **+ fila** (y N filas vacias iniciales razonables, p. ej. 5); permitir **borrar** fila antes de ejecutar. Sin pegar Excel como requisito V1.
9. Lookup cedula al salir del campo (blur) o Enter: si activo → nombre; si no existe / inactivo / sin periodo abierto → mensaje claro y sin nombre (no procesable).
10. Misma cedula **duplicada** en el lote: validacion previa → error de esa fila / rechazo de duplicados (no procesar dos veces).
11. Fecha, plantilla (`template_id`) y firma (`signatory_id` del catalogo `firmas`) **por fila** y **requeridos** para procesar la fila.
12. Al **Desvincular** (etiqueta del boton, aunque sea lote): sin confirmacion previa; sin tope de filas.
13. Por empleado exitoso: cerrar periodo activo + sync perfil desvinculado + crear `employee_termination_followups` + intentar carta (1 plantilla) + marcar `letter_generated`.
14. Si cierra vinculo pero falla carta: dejar desvinculado, `letter_generated=false`, incluir en reporte de fallos (tipo `letter`), continuar lote.
15. Si falla antes/durante cierre (no activo, validacion, etc.): no crear seguimiento; reportar fallo; continuar.
16. Al terminar: respuesta con resumen exitosos/fallidos; si hay ≥1 carta → descargar **un ZIP** (`desvinculaciones_{Ymd_His}.zip`) con los docx del lote (nombre interno por cedula/plantilla); **limpiar** grilla Masivos (no navegar automaticamente a Seguimientos).
17. Plantillas listadas: tipo documento code `desvinculacion` (`config/employee_ficha.word_document_type_codes`), con archivo en disco (mismo filtro que `TerminationLetterController::templates`).

### Seguimientos

18. Un registro por periodo cerrado desvinculado (unique `employee_ficha_employment_period_id`).
19. Columna **No** = `id` tecnico; orden por defecto **desc** (mas reciente primero).
20. **TIPO DESVINCULACION** = causal del periodo (`termination_cause_name` / code); solo lectura.
21. **FECHA DE REGISTRO** = `registered_at` (datetime del alta del seguimiento); solo lectura.
22. **FECHA DESVINCULACION** = snapshot / periodo (`last_work_day` o `termination_date`; en Masivos coinciden); solo lectura.
23. Cargo / cedula / nombre: snapshot al crear (no dependen del perfil actual si luego cambia).
24. Causal, recontratable, observaciones: solo lectura (desde periodo o snapshot).
25. Ocho checks boolean (default false): Orden examenes, Enviado, Control roll, Retiro ARL, Retiro cesantias, Recibido, Paz y salvo, Reporte noved. Labels UI en mayusculas estilo operativo.
26. **OK TODO** = AND de los 8; solo lectura; no editable ni forzado.
27. **Fecha entregado nomina** editable (nullable date).
28. **Tiene carta generada** = `letter_generated` (si/no); solo lectura en UI.
29. Autosave al editar checks / fecha nomina.
30. Sin eliminar / ocultar seguimiento en V1.
31. Filtros V1: busqueda texto (cedula/nombre) + chips o select: todos / incompletos (OK TODO falso) / OK TODO / sin carta. **No** filtros tipo Excel por encabezado ni colores.

### Ficha individual (impacto cruzado)

32. Tras `terminate` exitoso: crear followup del periodo cerrado si no existe (`letter_generated=false` salvo que ya hubiera path de carta — no aplica en el mismo request).
33. Tras `TerminationLetterController::generate` exitoso: set `letter_generated=true` en el followup del periodo (crear followup si faltara por datos legacy).
34. Formulario terminate individual y permisos `ficha_empleados.*`: **sin cambio de reglas** (campos required actuales).

### Auditoria

35. Eventos sugeridos: `bulk_termination` (action `process`, metadata: totales, cedulas ok/fail, template_ids, sin paths largos); `termination_followup` (action `create` / `update` con before/after de checks y fecha nomina); reutilizar eventos ficha existentes en terminate individual / letter pack.
36. Sin correo.

## Permisos (`config/access.php`)

### Keys concretas

| Permiso | Rol(es) | Descripcion |
| --- | --- | --- |
| `view.board.gestion_humana.desvinculaciones` | `super-admin` (todos); asignar en Admin al paquete GH | Ver tablero **Desvinculaciones** en sidebar GH |
| `desvinculaciones.view` | Paquete V1 | Acceder al tablero (Masivos lectura de UI + Seguimientos lectura) |
| `desvinculaciones.masivos` | Paquete V1 | Ejecutar desvinculaciones masivas (lookup, procesar lote, ZIP) |
| `desvinculaciones.seguimientos.edit` | Paquete V1 | Editar checks y fecha entregado nomina |

**Paquete V1 (misma persona):** los 4 permisos anteriores juntos. No crear roles nuevos; asignacion via Admin usuarios (como Ficha/Archivo).

### Relacion con Ficha

| Accion | Permiso |
| --- | --- |
| Desvincular individual + cartas en Ficha | `ficha_empleados.terminate` (sin cambio) |
| Ver/gestionar ficha | `ficha_empleados.view` / `manage` (sin cambio) |
| Lote Masivos | `desvinculaciones.masivos` (**no** requiere terminate de Ficha) |
| Side-effect crear followup desde Ficha terminate | Corre bajo el flujo ya autorizado por `ficha_empleados.terminate` |

### Cambios en `config/access.php`

- `system_permissions`: agregar `desvinculaciones.view`, `desvinculaciones.masivos`, `desvinculaciones.seguimientos.edit`.
- `boards`: `'desvinculaciones' => 'Desvinculaciones'`.
- `board_canonical_areas`: `desvinculaciones` → `home => gestion_humana`, `base_area_tab => false`.
- `desvinculaciones_tabs`: `masivos => Masivos`, `seguimientos => Seguimientos`.
- `admin_permission_groups` → `other_areas.gestion_humana`: board `view.board.gestion_humana.desvinculaciones` + subgroup `desvinculaciones` con los 3 permisos funcionales.
- Generacion automatica de `view.board.*` via catalogo existente (verificar `PermissionCatalog`).

### Seeders

- `RoleAndPermissionSeeder`: `PermissionCatalog::sync()` ya crea permisos; `super-admin` recibe todos. **No** meter el paquete completo en `administrador` por defecto (salvo decision posterior); documentar asignacion manual en Admin.
- Bypass runtime: `manage.users` en `DesvinculacionesAccessService`.

## Rutas

Archivo: `routes/areas/gestion_humana.php` (grupo nuevo; **no** editar `routes/web.php` salvo que el require del area ya este).

Prefijo: `/gestion-humana/desvinculaciones` · nombre `gestion-humana.desvinculaciones.`

| Metodo | URI | Nombre | Permiso / notas |
| --- | --- | --- | --- |
| GET | `/` | `index` | Redirect a Masivos (o vista con tab activa). `desvinculaciones.view` |
| GET | `/masivos` | `masivos` | Vista Masivos. `desvinculaciones.view` |
| GET | `/masivos/plantillas` | `masivos.templates` | JSON plantillas desvinculacion. `desvinculaciones.masivos` |
| GET | `/masivos/firmas` | `masivos.signatories` | JSON catalogo `firmas`. `desvinculaciones.masivos` |
| POST | `/masivos/lookup` | `masivos.lookup` | Body: `document_number` → nombre / id entry si activo. `desvinculaciones.masivos` |
| POST | `/masivos/procesar` | `masivos.process` | Lote; JSON reporte + file ZIP (o URL temporal de descarga). `desvinculaciones.masivos` |
| GET | `/seguimientos` | `seguimientos` | Vista Seguimientos. `desvinculaciones.view` |
| GET | `/seguimientos/datatable` | `seguimientos.datatable` | Datos filtrados. `desvinculaciones.view` |
| PATCH | `/seguimientos/{followup}` | `seguimientos.update` | Autosave parcial. `desvinculaciones.seguimientos.edit` |

Middleware grupo: `auth`, `active`, `password.changed` (igual que resto GH).

**Rutas Ficha existentes** (`ficha.terminate`, `period.letters.*`): se mantienen; se engancha creacion/actualizacion de followup en controlador/servicio (sin rutas nuevas obligatorias).

## Base de datos

Solo migracion(es) **nuevas**. Prohibido `migrate:fresh`.

### Tabla `employee_termination_followups` (crear)

| Columna | Tipo | Notas |
| --- | --- | --- |
| `id` | bigint PK | Columna **No** en UI |
| `personal_requisition_ficha_entry_id` | FK → `personal_requisition_ficha_entries.id` | `constrained`, `cascadeOnDelete` |
| `employee_ficha_employment_period_id` | FK → `employee_ficha_employment_periods.id` | `constrained`, `cascadeOnDelete`; **unique** |
| `document_number` | string(50) | Snapshot cedula |
| `full_name` | string(255) | Snapshot nombre |
| `position_name` | string(150) nullable | Snapshot cargo del periodo |
| `termination_cause_code` | string(50) nullable | Snapshot |
| `termination_cause_name` | string(150) nullable | Snapshot (TIPO DESVINCULACION) |
| `is_rehireable` | boolean nullable | Snapshot |
| `termination_notes` | text nullable | Snapshot observaciones |
| `termination_date` | date nullable | FECHA DESVINCULACION (valor unificado Masivos) |
| `registered_at` | timestamp | FECHA DE REGISTRO (now al crear) |
| `letter_generated` | boolean default false | Tiene carta generada |
| `check_orden_examenes` | boolean default false | |
| `check_enviado` | boolean default false | |
| `check_control_roll` | boolean default false | |
| `check_retiro_arl` | boolean default false | |
| `check_retiro_cesantias` | boolean default false | |
| `check_recibido` | boolean default false | |
| `check_paz_y_salvo` | boolean default false | |
| `check_reporte_noved` | boolean default false | |
| `payroll_delivered_at` | date nullable | Fecha entregado nomina |
| `created_by` | FK users nullable | `nullOnDelete` |
| `created_at` / `updated_at` | timestamps | |

Indices: unique periodo; index `(document_number)`; index `(letter_generated)`; index `(registered_at)`.

**No** columna `ok_todo` en BD.

### Sin alter de periodos

No cambiar columnas de `employee_ficha_employment_periods` en V1 (ya tiene carta path/type y campos de cierre).

## Capas a implementar

- [ ] Migracion(es) — tabla `employee_termination_followups`
- [ ] Modelo(s) — `EmployeeTerminationFollowup` (+ relaciones en Period / FichaEntry)
- [ ] Access — `DesvinculacionesAccessService`
- [ ] Audit — `DesvinculacionesAuditLogService` + `config/audit.php`
- [ ] Servicios — factory/creacion followup; `BulkTerminationService` (orquestador); ajuste puntual `closeActivePeriod` / caller para `is_rehireable` nullable en Masivos; hook post-terminate y post-letter en Ficha
- [ ] Controlador(es) — `DesvinculacionesController` (o split Masivos/Seguimientos si el Feature lo prefiere, mismo modulo)
- [ ] Form Request(s) — lookup, process batch, update followup
- [ ] Vista(s) Blade — layout tablero + subnav tabs + Masivos + Seguimientos
- [ ] JavaScript — grilla Masivos (Alpine/Vite entry si aplica) + autosave Seguimientos
- [ ] Nav — `NavigationResolver`, `SidebarVisibilityService`, helper URL en `User`
- [ ] Shared config — `config/access.php`, seeders/catalogo permisos
- [ ] Export Excel — **no** V1
- [ ] Tests PHPUnit
- [ ] Docs tecnicas/usuario (Documentador)

## Componentes reutilizables

- `<x-searchable-select>` para TIPO CARTA, FIRMA y Causal (catalogo `termination_cause`).
- `EmployeeFichaEmploymentPeriodService::closeActivePeriod` + `syncProfileAfterTermination`.
- `TerminationLetterPackGeneratorService::generate` (array de un `template_id`).
- Patron audit wrapper ficha (`EmployeeFichaAuditLogService`).
- Estilo nav pills / `module-tab` / `module-subnav` (branding GH).
- **No** `BaseExport` / `<x-export-excel>` en V1.
- **No** Select2.
- **No** Repository.

## Documentacion a actualizar

- [ ] `docs/modules/desvinculaciones.md` (**crear** — Documentador)
- [ ] `docs/user/desvinculaciones.md` (**crear** — Documentador; secciones Objetivo, Alcance, Definiciones, Responsabilidades, Desarrollo, Control de cambios)
- [ ] `docs/modules/ficha-empleados.md` — nota: terminate individual crea seguimiento; letter generate actualiza `letter_generated`
- [ ] `docs/user/ficha-empleados.md` — menciones breves si aplica
- [ ] `docs/ACCESS_CONTROL.md` — board + permisos nuevos
- [ ] `docs/INDEX.md` — enlace modulo nuevo
- [ ] `docs/ARCHITECTURE.md` — fila ownership `desvinculaciones` (rutas/vistas/controllers GH)
- [ ] `README.md` — solo si lista modulos GH de forma explicita

## Archivos compartidos (`shared-files`)

| Archivo | Motivo |
| --- | --- |
| `config/access.php` | Board, tabs, system_permissions, admin groups |
| `config/audit.php` | Modulo `desvinculaciones` |
| `routes/areas/gestion_humana.php` | Rutas del tablero |
| `app/Services/Navigation/NavigationResolver.php` | Link sidebar |
| `app/Services/Navigation/SidebarVisibilityService.php` | Visibilidad board |
| `app/Models/User.php` | `defaultDesvinculacionesBoardUrl()` |
| `database/seeders/RoleAndPermissionSeeder.php` | Sync catalogo (indirecto) |
| `app/Http/Controllers/GestionHumana/FichaEmpleadosController.php` | Hook create followup en `terminate` |
| `app/Http/Controllers/GestionHumana/TerminationLetterController.php` | Hook `letter_generated` |

Flag `shared-files: true` en `docs/TASKS.md` / Task Cards. Un solo agente a la vez sobre estos archivos.

## Criterios de aceptacion

1. Usuario con paquete de permisos ve **Desvinculaciones** en sidebar GH junto a Ficha; sin board permission no lo ve (salvo bypass `manage.users`).
2. Pestañas **Masivos** y **Seguimientos** visibles; labels exactos.
3. Lookup cedula: activo → nombre; inactivo/inexistente → mensaje y no procesable.
4. Lote con N filas: procesa todas; fallos no detienen el resto; UI reporta exitos y fallos.
5. Empleado desvinculado en lote aparece en Seguimientos con snapshots correctos.
6. FECHA DESVINCULACION unica en Masivos persiste igual en `last_work_day` y `termination_date` del periodo.
7. Causal/rehire/notas opcionales en Masivos; visibles solo lectura en Seguimientos.
8. Carta OK → `letter_generated=true` y archivo en periodo; carta fail → desvinculado + flag false + reporte.
9. ZIP unico con cartas exitosas del lote; grilla Masivos limpia al finalizar.
10. Desvincular desde Ficha individual crea fila en Seguimientos (sin hueco).
11. Generar carta desde Ficha marca `letter_generated=true` en el followup.
12. OK TODO solo lectura y true solo si los 8 checks son true.
13. Autosave de checks / fecha nomina con `desvinculaciones.seguimientos.edit`; sin permiso → 403.
14. Masivos **no** exige `ficha_empleados.terminate`; Ficha individual **sigue** exigiendolo.
15. Sin colores legacy; sin export Excel; sin correo.
16. Eventos de auditoria visibles en log admin para lote y updates de seguimiento.
17. Tests listados abajo en verde.

## Tests minimos

| Area | Caso |
| --- | --- |
| Permisos | Sin `desvinculaciones.view` → 403 tablero; sin `masivos` → 403 process; sin `seguimientos.edit` → 403 PATCH |
| Bypass | `manage.users` accede y procesa |
| Lookup | Activo OK; desvinculado / inexistente error |
| Lote parcial | 1 OK + 1 fail (p. ej. ya inactivo) → OK crea followup; fail en reporte; HTTP no 500 |
| Carta fail | Mock/force error generate tras close → followup con `letter_generated=false` |
| ZIP | 2 cartas OK → respuesta archivo zip |
| Individual | `terminate` Ficha → existe followup del periodo |
| Letter Ficha | generate → followup `letter_generated=true` |
| OK TODO | 7 checks true → false; 8 true → true |
| Autosave | PATCH un check persiste |
| Duplicado cedula | Lote con misma cedula dos veces → validacion |

Correr: `php artisan test --compact` filtrando tests de desvinculaciones / followup.

## Validacion local

1. `php artisan migrate` (sin fresh).
2. Asignar paquete de permisos a usuario de prueba en Admin.
3. Probar Masivos (2–3 cedulas activas, una invalida) → reporte + ZIP + Seguimientos.
4. Desvincular uno desde Ficha → aparece en Seguimientos; generar carta → flag si.
5. Editar checks → OK TODO; refresh conserva valores.
6. `php artisan test --compact` (suite afectada).
7. `vendor/bin/pint --dirty` tras PHP.

## Riesgos y dependencias

| Riesgo | Mitigacion |
| --- | --- |
| Lotes grandes / timeout PHP | Sin tope V1; documentar riesgo Hostinger; Feature puede stream ZIP y procesar sync; si timeout en QA, proponer cola post-V1 |
| `is_rehireable` casteado a false hoy en servicio | Caller Masivos debe pasar null explicito o ajustar servicio sin romper individual |
| Huecos historicos (periodos cerrados antes de FEAT-031) | Fuera V1; comando backfill opcional despues |
| Shared-files nav/access | Coordinar Task Cards; no editar en paralelo |
| Usuario con Masivos pero sin `ficha_empleados.terminate` | No puede regenerar carta en Ficha; flag queda false hasta que alguien con terminate genere — documentar en doc usuario |
| Dependencia plantillas Word tipo `desvinculacion` | Si no hay plantillas con archivo, Masivos no puede completar filas (validacion clara) |
| ZIP + JSON reporte | Definir contrato HTTP (p. ej. `multipart` no; preferir JSON con `download_token` + GET descarga, o `Content-Disposition` solo cuando todo OK y fallos en flash — Feature elige uno y lo documenta en modulo) |

## Dependencias de codigo existente

- `FichaEmpleadosController::terminate`
- `EmployeeFichaEmploymentPeriodService`
- `TerminationLetterController` / `TerminationLetterPackGeneratorService`
- Plantillas `TerminationLetterDocumentTemplate` tipo `desvinculacion`
- Catalogos `termination_cause`, `firmas`
- Docs: `docs/modules/ficha-empleados.md`, `docs/user/ficha-empleados.md`, `docs/user/plantillas-word.md`

## Slice sugerido para AgentSj (plan)

| Task | Contenido | shared-files |
| --- | --- | --- |
| T1 | Permisos, audit config, migracion, modelo, AccessService, nav sidebar, shell tabs vacios | Si |
| T2 | Masivos (lookup, process, ZIP, audit lote) + hooks Ficha terminate/letter | Parcial (controllers Ficha/Letter) |
| T3 | Seguimientos UI + datatable + autosave + tests cierre + ajuste docs INDEX/ACCESS | No (salvo docs compartidos) |

Documentador tras Revisor.

## Aprobacion

- [x] Analista — vacios cerrados (respuestas usuario 2026-09-14 en `FEAT-031-analyst.md`)
- [x] Arquitecto — brief final (`docs/briefs/FEAT-031.md`)
- [x] Usuario — confirmacion (2026-09-14: «apruebo»)
