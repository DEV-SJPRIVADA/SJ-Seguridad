# Modulo Desvinculaciones

> Documentacion tecnica para IAs y desarrolladores. Ubicacion: `docs/modules/desvinculaciones.md`.
> Feature: FEAT-031. Review: `docs/reviews/FEAT-031.md` (Aprobado con observaciones).

## Objetivo

Tablero de area **Gestion Humana** para (1) desvincular varios empleados activos en lote (**Masivos**) con generacion de carta Word y ZIP, y (2) llevar el checklist post-retiro (**Seguimientos**) con autosave y OK TODO calculado. Complementa la desvinculacion individual de Ficha empleados; no la reemplaza.

## Alcance actual (V1)

- Tablero sidebar **Desvinculaciones** (`desvinculaciones`) en `gestion_humana`, pestanas **Masivos** / **Seguimientos**.
- Permisos propios (paquete de 4 keys); Masivos **no** exige `ficha_empleados.terminate`.
- Lookup por cedula solo si empleado **activo** + periodo abierto.
- Lote: continuar ante fallos; reporte `ok[]` / `failed[]`; ZIP de cartas exitosas via token one-shot.
- Tabla `employee_termination_followups` (1:1 con periodo cerrado); creacion tambien desde Ficha `terminate` y marca de carta desde `TerminationLetterController::generate`.
- Auditoria: lote (`bulk_termination`) + updates de seguimiento (`termination_followup` / `update`) + export Seguimientos (`export` / `seguimientos_excel`). Sin correo. Sin colores legacy.

**Fuera de V1 (original):** correo, colores/semaforos, split de perfiles Masivos-only vs Seguimientos-only, multi-plantilla por fila en Masivos, tope de filas, confirmacion previa del lote, regenerar carta embebida en Seguimientos, historial campo-a-campo de checks.

**Post-V1 (activo):** filtro rango **FECHA ENTREGADO NOMINA** + export Excel de Seguimientos (`BaseExport`); import histórico Artisan desde plantilla Excel `NOVEDADES` (sin cartas).

**Integracion FEAT-040 (MT-GH-04 Novedades):** al crear un followup (`ensureForClosedPeriod`, desde Ficha terminate o Masivos) se llama `ReportesNovedadesRetiroSyncService::ensureFromFollowup` (alta idempotente en hoja Retiros; `observacion_nomina` vacia). Al **revertir**, `annulFromFollowup` soft-deletea la fila Retiros vinculada **antes** de eliminar el followup. No exige permisos `reportes_novedades.*` al actor. Detalle: [`docs/modules/reportes-novedades.md`](reportes-novedades.md).

**Bloqueo por cruce con novedades:** antes de cerrar el vínculo (Ficha `terminate` o Masivos `processRow`), `TerminationNovedadesConflictService` busca solape del intervalo `[último día, fecha desvinculación]` (en Masivos ambas = fecha del lote) con filas de **Vacaciones / Incapacidades / Permisos** de la misma cédula. Si hay solape → ValidationException (no desvincula). Sin `fecha_fin` en incapacidad/permiso (o vacación legacy) se trata como vigente abierta y bloquea si el retiro cae en o después del inicio. **Super-admin** puede forzar con `force_novedades_conflict=1` (checkbox en modal Ficha y toolbar Masivos). En Masivos la fila conflictiva va a `failed` y el resto del lote continúa.

## Rutas

Archivo: `routes/areas/gestion_humana.php`  
Prefijo: `/gestion-humana/desvinculaciones` · nombre `gestion-humana.desvinculaciones.`  
Middleware grupo area: `password.changed` (mas `auth` / `active` del grupo `web.php`).

| Metodo | URI | Nombre | Permiso / notas |
| --- | --- | --- | --- |
| GET | `/` | `index` | Redirect a Masivos. `desvinculaciones.view` |
| GET | `/masivos` | `masivos` | Vista Masivos. `desvinculaciones.view` |
| GET | `/masivos/plantillas` | `masivos.templates` | JSON plantillas. `desvinculaciones.masivos` |
| GET | `/masivos/firmas` | `masivos.signatories` | JSON catalogo `firmas`. `desvinculaciones.masivos` |
| POST | `/masivos/lookup` | `masivos.lookup` | Cedula → nombre si activo. `desvinculaciones.masivos` |
| POST | `/masivos/procesar` | `masivos.process` | Lote → JSON reporte + `download_token`. `desvinculaciones.masivos` |
| GET | `/masivos/descarga/{token}` | `masivos.download` | ZIP Content-Disposition; token one-shot. `desvinculaciones.masivos` |
| GET | `/seguimientos` | `seguimientos` | Vista Seguimientos. `desvinculaciones.view` |
| GET | `/seguimientos/datatable` | `seguimientos.datatable` | JSON filtrado (q, status, fecha_desde/hasta nomina). `desvinculaciones.view` |
| GET | `/seguimientos/exportar` | `seguimientos.export` | Excel filtrado (`BaseExport`). `desvinculaciones.view` |
| POST | `/seguimientos/importar-historico` | `seguimientos.import-historico` | Import histórico NOVEDADES (JSON; `dry_run` / carga). `desvinculaciones.seguimientos.edit` |
| PATCH | `/seguimientos/{followup}` | `seguimientos.update` | Autosave parcial. `desvinculaciones.seguimientos.edit` |
| POST | `/seguimientos/{followup}/revertir` | `seguimientos.revert` | Revertir desvinculacion (reactiva empleado). `desvinculaciones.seguimientos.edit` |

Hooks sin rutas nuevas (Ficha):

| Flujo | Ruta existente | Efecto followup |
| --- | --- | --- |
| Terminate individual | `POST .../ficha/terminate` | `EmployeeTerminationFollowupService::ensureForClosedPeriod` |
| Generar carta | `POST .../periodos/{period}/cartas/generar` | `markLetterGenerated` (`letter_generated=true`; crea followup si faltaba por legacy) |

## Permisos

| Permiso | Uso |
| --- | --- |
| `view.board.gestion_humana.desvinculaciones` | Ver tablero en sidebar GH |
| `desvinculaciones.view` | Acceder al tablero (UI Masivos + lectura Seguimientos) |
| `desvinculaciones.masivos` | Lookup, plantillas/firmas JSON, procesar lote, descargar ZIP |
| `desvinculaciones.seguimientos.edit` | PATCH checks / fecha entregado nomina; revertir desvinculacion |

**Paquete V1 (misma persona):** los 4 permisos juntos. Asignacion via Admin usuarios. `super-admin` recibe todos via catalogo. Rol `administrador` **no** recibe el paquete por defecto.

**Bypass:** `manage.users` en `DesvinculacionesAccessService` (view board, view, masivos, edit).

**Relacion con Ficha:**

| Accion | Permiso |
| --- | --- |
| Desvincular / cartas individuales | `ficha_empleados.terminate` (sin cambio) |
| Lote Masivos | `desvinculaciones.masivos` (**no** requiere terminate Ficha) |
| Side-effect followup desde terminate Ficha | Corre bajo `ficha_empleados.terminate` |

Config: `config/access.php` (`system_permissions`, `boards`, `board_canonical_areas`, `desvinculaciones_tabs`, `admin_permission_groups` → `gestion_humana`). `PermissionCatalog` rechaza board fuera de `gestion_humana`.

## Controladores y requests

| Clase | Responsabilidad |
| --- | --- |
| `App\Http\Controllers\GestionHumana\DesvinculacionesController` | Vistas, lookup, process, downloadZip, datatable, export Excel Seguimientos, PATCH seguimiento, POST revertir |
| `LookupBulkTerminationRequest` | Auth `canMasivos`; `document_number` |
| `ProcessBulkTerminationRequest` | Auth `canMasivos`; filas min 1; fecha/plantilla/firma required; causal/rehire/notas opcionales; rechazo duplicados de cedula en el lote (422 antes de procesar). `template_id` = `exists:termination_letter_document_templates,id` (**sin** scope tipo `desvinculacion` ni archivo en disco — la UI si filtra; obs. review #3) |
| `UpdateTerminationFollowupRequest` | Auth `canEditSeguimientos`; payload parcial solo checks + `payroll_delivered_at` |
| `RevertTerminationFollowupRequest` | Auth `canEditSeguimientos`; `reason` required min 5 |
| `FichaEmpleadosController::terminate` | Tras cierre exitoso → `ensureForClosedPeriod` |
| `TerminationLetterController::generate` | Tras generate exitoso → `markLetterGenerated` |

## Vistas

| Vista | Descripcion |
| --- | --- |
| `areas/gestion_humana/desvinculaciones/masivos.blade.php` | Grilla Alpine: iconos superior derecha (multi-cedula / +fila), filas con trash, Desvincular/limpiar/ZIP icon-only; lookup blur/Enter; modal pegar hasta 500; reporte |
| `areas/gestion_humana/desvinculaciones/seguimientos.blade.php` | DataTables **server-side** (`.js-desvinculaciones-seguimientos-datatable`) + filtros Alpine (q, status, campo fecha + rango) + buscador nativo DT (`lf`); altura de `.req-manage-table-scroll` al viewport; export Excel, checks/fecha nómina con autosave (eventos delegados), OK TODO RO, icono revertir + modal motivo |
| `areas/gestion_humana/desvinculaciones/partials/subnav.blade.php` | Pestanas `module-tab` Masivos / Seguimientos |
| `areas/gestion_humana/desvinculaciones/partials/alpine-searchable-select.blade.php` | Select searchable **inline** para filas `x-for` (replica markup/CSS/Alpine de `<x-searchable-select>`; **no** Select2). Aceptable en grilla dinamica; no usa el Blade component (obs. review #4) |

## Modelos y tablas

### `employee_termination_followups`

Migracion: `2026_09_14_103500_create_employee_termination_followups_table.php` (solo `migrate` incremental; **prohibido** fresh/wipe).

| Columna | Notas |
| --- | --- |
| `id` | Columna UI **No** |
| `personal_requisition_ficha_entry_id` | FK ficha entry, `cascadeOnDelete` |
| `employee_ficha_employment_period_id` | FK periodo, **unique**, `cascadeOnDelete` |
| Snapshots | `document_number`, `full_name`, `position_name`, causal code/name, `is_rehireable`, `termination_notes`, `termination_date` |
| `registered_at` | FECHA DE REGISTRO al crear |
| `letter_generated` | default false |
| 8 checks | `check_orden_examenes`, `check_enviado`, `check_control_roll`, `check_retiro_arl`, `check_retiro_cesantias`, `check_recibido`, `check_paz_y_salvo`, `check_reporte_noved` (default false) |
| `payroll_delivered_at` | Fecha entregado nomina (nullable date) |
| `created_by` | FK users `nullOnDelete` |
| timestamps | |

**No** existe columna `ok_todo` en BD.

Modelo: `App\Models\EmployeeTerminationFollowup`

- Constantes `CHECK_FIELDS` / `CHECK_LABELS` (labels UI en mayusculas).
- Accessor `ok_todo` / `isOkTodo()` = AND de los 8 checks (columna UI; no exige fecha nómina).
- Scopes: `search`, `okTodo`, `incompletos`, `sinCarta`, `statusFilter` (`todos` \| `incompletos` \| `ok_todo` \| `sin_carta`), `dateFieldBetween` / `payrollDeliveredBetween`.
  - **`ok_todo` (filtro):** 8 checks true **y** `payroll_delivered_at` no nulo.
  - **`incompletos` (filtro):** algún check en false **o** sin `payroll_delivered_at` (default al cargar la vista / datatable sin `status`).
  - **Rango fechas:** columna elegida con `fecha_campo` (`DATE_FILTER_FIELDS`; default `termination_date` / FECHA DESVINCULACION).
- Relaciones: `fichaEntry`, `employmentPeriod`, `creator`.
- Relaciones inversas: `EmployeeFichaEmploymentPeriod::terminationFollowup()`, `PersonalRequisitionFichaEntry::terminationFollowups()`.
- Factory: `EmployeeTerminationFollowupFactory`.

## Servicios / access / audit

| Clase | Rol |
| --- | --- |
| `DesvinculacionesAccessService` | Board / view / masivos / edit + bypass `manage.users`; `visibleTabsFor` |
| `BulkTerminationService` | Lookup activo; `process` por fila (TX close+sync+followup; carta fuera de TX); ZIP lote; audit `bulk_termination` |
| `EmployeeTerminationFollowupService` | `ensureForClosedPeriod` (+ sync Retiros FEAT-040), `markLetterGenerated`, `updatePartial`, `revert` (+ annul Retiros + audit) |
| `EmployeeFichaEmploymentPeriodService::reopenClosedPeriod` | Reactiva periodo cerrado y sincroniza perfil a activo |
| `TerminationFollowupDatatableService` | Query + format row (incluye `ok_todo`, labels carta) |
| `DesvinculacionesAuditLogService` | Wrapper `module=desvinculaciones`, `area=gestion_humana` |
| `EmployeeFichaEmploymentPeriodService` | `closeActivePeriod` / `syncProfileAfterTermination` (reutilizado; Masivos puede pasar `is_rehireable` null) |
| `TerminationLetterPackGeneratorService` | Genera 1 plantilla por fila Masivos |

Config audit: `config/audit.php` → modulo `desvinculaciones`.

### Contrato HTTP ZIP (real)

1. `POST masivos/procesar` → JSON `{ ok, failed, summary, download_token, download_url }` (token null si 0 cartas).
2. Token en Cache (`desvinculaciones.bulk_zip.{uuid}`), payload `{ path, name, user_id }`, TTL **900 s**.
3. `GET masivos/descarga/{token}`: `Cache::pull` (un solo uso), exige `user_id` del actor + `canMasivos`, `download` + `deleteFileAfterSend`.
4. UI Masivos: limpia grilla y navega a `download_url`. Un segundo click al mismo token → 404 (obs. review #5: one-shot documentado; boton «Descargar ZIP» reutilizando URL falla).

Nombre ZIP tipico: `desvinculaciones_{Ymd_His}.zip`.

### Auditoria (comportamiento real vs brief)

| Evento | Cuando |
| --- | --- |
| `bulk_termination` / `process` | Fin de lote Masivos (totales, cedulas ok/fail, template_ids) |
| `termination_followup` / `update` | PATCH autosave (before/after checks o fecha nomina) |
| `termination_followup` / `revert` | Reversion con motivo obligatorio; metadata de cedula/periodo; carta eliminada del disco |
| Alta followup desde Ficha / Masivos | **No** emite `termination_followup`/`create` en modulo desvinculaciones (obs. review #1). Alta cubierta por audit de Ficha (`terminate`) + evento de lote |
| `import_historico` / `dry_run` \| `import` | Modal Seguimientos o comando Artisan; metadata de conteos |

## Reglas de negocio

### Acceso / nav

1. Labels exactos: tablero **Desvinculaciones**; pestanas **Masivos** / **Seguimientos**.
2. Sidebar: `view.board.gestion_humana.desvinculaciones` (o bypass). Entrar: `desvinculaciones.view`.
3. Nav: `NavigationResolver`, `SidebarVisibilityService`, `User::defaultDesvinculacionesBoardUrl()`.

### Masivos

4. Columnas: CEDULA | NOMBRE (RO tras lookup) | FECHA DESVINCULACION | TIPO CARTA | FIRMA | Causal / Recontratable / Observaciones (opc.).
5. Lookup: activo + periodo abierto → nombre; si no → mensaje y no procesable.
5b. **Multi-cedula (UI):** icono clipboard abre modal en el mismo `x-data`; pegar hasta **500** (linea/coma/;). Lookup secuencial al endpoint existente; llena filas vacias / agrega filas; omite duplicadas ya en grilla y no encontradas; resumen bajo toolbar (agregadas / duplicadas / no procesables).
6. Fecha unica UI → mismo valor en `last_work_day` y `termination_date` del periodo.
7. Causal vacia → null; rehire omitido → null (no forzar false); notas vacias → null.
8. Plantilla + firma **requeridas** por fila; 1 `template_id` (tipo UI: `desvinculacion` con archivo en disco).
9. Duplicados de cedula en el lote → 422 previo (no procesa).
10. Sin confirmacion previa; sin tope de filas en proceso (tope 500 solo en pegado multi-cedula).
11. Por fila exitosa: close + sync desvinculado + followup + intento carta; fallo carta → desvinculado + `letter_generated=false` + fallo tipo `letter`; fallo cierre → sin followup + fallo tipo `termination`; lote continua.
12. Al terminar: reporte; si ≥1 carta → token ZIP; UI limpia grilla (no navega a Seguimientos).

### Seguimientos

13. Un registro por periodo cerrado (unique).
14. Orden default **desc** por id / mas reciente.
15. Causal, rehire, notas, cargo, cedula, nombre, fechas, carta: solo lectura (snapshots / periodo).
16. 8 checks editables + `payroll_delivered_at`; autosave PATCH debounce ~400 ms.
17. OK TODO solo lectura, calculado.
18. Filtros: `q` (cedula/nombre) + status `incompletos` / `ok_todo` / `sin_carta` (sin chip Todos; default incompletos) + `rehireable` (`1`/`0`, vacío = todos) + selector `fecha_campo` + rango Desde/Hasta. Al usar rango de fechas se desactiva el chip de estado (`status=` vacío = sin filtro de estado); si el usuario vuelve a activar un chip, se combina con el rango. Limpiar restaura incompletos + campo fecha default + rangos/búsqueda/recontratable vacíos.
19. **Export Excel** del listado filtrado (`desvinculaciones.view`): columnas No, CEDULA, NOMBRE Y APELLIDOS, CARGO, TIPO DESVINCULACION, FECHA DE REGISTRO, FECHA DESVINCULACION, 8 checks, OK TODO, OBSERVACIONES. Checks/OK TODO como Si/No. Audit `export` / `seguimientos_excel`.
20. **Revertir** por fila (permiso edit): modal con motivo; anula fila Retiros en MT-GH-04 Novedades (si existe), reabre periodo, perfil activo, elimina followup, borra carta en disco. Regenerar carta (si aplica): en **Ficha** con `ficha_empleados.terminate`.

## JavaScript / assets

- Alpine embebido en vistas Masivos / Seguimientos (sin entry Vite dedicado obligatorio).
- Estilos: utilidades en `resources/css/app.css` (prefijo desvinculaciones si aplica).
- Seguimientos UI: panel de filtros Alpine + toolbar (conteo/export); DataTables con `length` + buscador nativo (`search.value`, fallback `q`); tabla con `req-manage-page` / `req-manage-shell` y scroll viewport (mismo patrón que Cursos registros / Ficha).
- Selectores Masivos: partial Alpine searchable (ver vistas).

## Export Excel

Seguimientos: `App\Exports\BaseExport` via `GET .../seguimientos/exportar`. Respeta los mismos filtros del datatable. No usar `excelHtml5`.

## Validacion local

1. `php artisan migrate` (sin fresh).
2. Asignar paquete de 4 permisos a usuario GH en Admin.
3. Masivos: 2–3 cedulas activas + 1 invalida → reporte + ZIP + filas en Seguimientos.
4. Terminate individual Ficha → aparece seguimiento; Generar carta → `letter_generated` Si.
5. Editar checks → OK TODO; refresh conserva.
6. `php artisan test --compact` — `DesvinculacionesBoardAccessTest`, `DesvinculacionesMasivosTest`, `DesvinculacionesSeguimientosTest` (28 tests en review).

## Riesgos y pendientes (incl. observaciones del review)

| Riesgo / obs. | Estado documentado |
| --- | --- |
| Lotes grandes / timeout PHP-Hostinger | Sin tope V1; sync; cola post-V1 si hace falta |
| Audit `create` followup ausente (obs. #1) | Comportamiento real; alta via Ficha audit + bulk |
| ZIP one-shot (obs. #5) | Token `pull`; segundo GET falla — documentado |
| `template_id` sin scope tipo/archivo (obs. #3) | UI filtra; validacion backend laxa |
| Select Alpine inline vs `<x-searchable-select>` (obs. #4) | Aceptable; no Select2 |
| Test IDOR ZIP / process solo `manage.users` (obs. #2, #6) | Codigo protege user_id; tests opcionales post-V1 |
| Usuario Masivos sin `ficha_empleados.terminate` | No puede regenerar carta en Ficha; flag puede quedar No |
| Sin plantillas tipo desvinculacion con archivo | No se pueden completar filas Masivos |
| Periodos cerrados pre-FEAT-031 | Cubiertos por `desvinculaciones:import-historico` (ver sección Import histórico) |

## Import histórico (Artisan)

Comando: `php artisan desvinculaciones:import-historico {path} [--dry-run] [--limit=] [--user=]`

| Pieza | Detalle |
| --- | --- |
| Servicio | `App\Services\GestionHumana\HistoricalTerminationImportService` |
| Fuente | Hoja **NOVEDADES** del `.xlsx`/`.xlsm` (plantilla operativa desvinculaciones) |
| Corte | Solo filas con `FECHA DE RET` (o `FECHA DE CREACION`) ≥ **2025-05-01** |
| Match ficha | `EmployeeFichaProfile.document_number` o `PersonalRequisitionFichaEntry.hired_document` |
| Escritura | Periodo **cerrado** + followup (`letter_generated=false`) + `ReportesNovedadesRetiroSyncService::ensureFromFollowup` |
| Idempotencia | Si ya hay followup misma entry + `termination_date` → **actualiza** checks / causal / notas / nómina |
| Reingresos | Si `hire_date` (perfil o periodo activo) es **posterior** a la fecha de retiro Excel → crea histórico **sin** cerrar el vínculo vigente ni poner perfil `desvinculado` (`created_keep_activo`) |
| Causales | Alias Excel → código catálogo Ficha (`RENUNCIA`, `FIN_CONTRATO`, `PERIODO_PRUEBA`, `SIN_JUSTA_CAUSA`, `CON_JUSTA_CAUSA`, `FALLECIMIENTO`, `LEGALIZACION_INASISTENCIAS`) + `PayrollCatalogItem::upsertPair` |
| Checks H–O | Mapeo a los 8 booleanos del followup (`TRUE`/`SI`/`1`/`X`) |
| Dry-run | Cuenta métricas sin escribir BD |

**No** genera cartas Word. Carga real solo tras OK explícito del operador (usar `--dry-run` primero).

**UI (recomendado):** en Seguimientos, icono Importar histórico (permiso `desvinculaciones.seguimientos.edit`) → modal Simular / Cargar. Endpoint JSON `seguimientos.import-historico`; audit `import_historico` / `dry_run|import`.

**CLI (alternativa):** `desvinculaciones:import-historico`. Procedimiento: [`docs/PROCEDURES.md`](../PROCEDURES.md). Produccion actual: **servidor Linux propio**.

Tests: `tests/Feature/GestionHumana/HistoricalTerminationImportTest.php`.

## Referencias

- Feature Brief: [`docs/briefs/FEAT-031.md`](../briefs/FEAT-031.md)
- Review: [`docs/reviews/FEAT-031.md`](../reviews/FEAT-031.md)
- Doc usuario: [`docs/user/desvinculaciones.md`](../user/desvinculaciones.md)
- Ficha empleados: [`docs/modules/ficha-empleados.md`](ficha-empleados.md)
- MT-GH-04 Novedades (Retiros auto): [`docs/modules/reportes-novedades.md`](reportes-novedades.md)
- Plantillas Word: [`docs/modules/plantillas-word.md`](plantillas-word.md)
- Guia documentacion: [`docs/DOCUMENTATION.md`](../DOCUMENTATION.md)
