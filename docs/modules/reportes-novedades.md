# Modulo MT-GH-04 Novedades

> Documentacion tecnica para IAs y desarrolladores. Ubicacion: `docs/modules/reportes-novedades.md`.
> Feature: FEAT-040. Observaciones **cerradas** 2026-11-04 ([`cierre-observaciones-gestion-humana-2026-11-04.md`](../informes/cierre-observaciones-gestion-humana-2026-11-04.md)).
> Clave interna: `reportes_novedades`. Label UI / sidebar: **MT-GH-04 Novedades**.

## Objetivo

Tablero de area **Gestion Humana** para registrar y exportar las cuatro hojas de novedades del Excel operativo MT-GH-04 (Vacaciones, Incapacidades, Retiros, Permisos), con canal de **review** de Nómina sobre columnas finales. Sin workflow de estados: edicion libre segun permisos. Los **Retiros** se alimentan automaticamente al crear un followup de desvinculacion y se anulan (soft-delete) al revertir.

## Alcance actual

- Tablero sidebar **MT-GH-04 Novedades** (`reportes_novedades`) en `gestion_humana`.
- Pestanas exactas: **Vacaciones**, **Incapacidades**, **Retiros**, **Permisos**.
- Permisos por hoja: `.view` / `.edit` / `.review` + board `view.board.gestion_humana.reportes_novedades`.
- CRUD GH (columnas GH) + review Nómina (columnas Nómina) + export Excel por hoja + historial audit por pestana (modal; sin pestana Auditoria global).
- Lookup cedula → Ficha (nombre, cargo, destino, fecha ingreso / tipo segun aplique).
- Catalogos fijos en `config/reportes_novedades.php` (sin pantallas de administracion V1).
- DataTables `serverSide: true`; `<x-searchable-select>`; `BaseExport` + `<x-export-excel>` (sin Select2 / sin `excelHtml5`).
- Hook Desvinculaciones: alta Retiros en `ensureForClosedPeriod`; soft-delete en `revert`.

**Fuera de alcance V1:** import `.xlsm`, dashboard KPI, correos, BD FERIADOS / Tabla1, workflow estados, pestana Auditoria del tablero, admin UI de catalogos, roles Spatie nuevos (`nomina`), backfill historico Excel.

## Rutas

Archivo: `routes/areas/gestion_humana.php`  
Prefijo: `/gestion-humana/reportes-novedades` · nombre `gestion-humana.reportes-novedades.`  
Middleware grupo area: `password.changed` (mas `auth` / `active` del grupo `web.php`).

| Metodo | URI | Nombre | Permiso / notas |
| --- | --- | --- | --- |
| GET | `/` | `index` | Redirect a primera pestana visible |
| POST | `/lookup` | `lookup` | Cedula → Ficha. Requiere `canEditAnySheet` |
| GET | `/{hoja}` | `{hoja}` | Vista. view∨edit∨review de la hoja |
| GET | `/{hoja}/datatable` | `{hoja}.datatable` | JSON server-side |
| GET | `/{hoja}/exportar` | `{hoja}.export` | Excel. view (implica edit/review) |
| GET | `/{hoja}/historial` | `{hoja}.historial` | JSON audit hoja/fila (`id` opcional) |
| POST | `/{hoja}` | `{hoja}.store` | create GH. edit |
| PATCH | `/{hoja}/{row}` | `{hoja}.update` | update GH. edit (no persiste cols Nómina) |
| PATCH | `/{hoja}/{row}/review` | `{hoja}.review` | cols Nómina. review |
| PATCH | `/permisos/{permiso}/marca-gh` | `permisos.marca-gh` | Toggle **Marca GH** desde el listado. **edit** (JSON) |
| DELETE | `/{hoja}/{row}` | `{hoja}.destroy` | edit (Retiros: soft-delete) |
| GET | `/vacaciones/{vacacion}/carta-word` | `vacaciones.carta-word` | Descarga .docx carta vacaciones (plantilla `cartas_vacaciones`). **edit** vacaciones |
| GET | `/incapacidades/{incapacidad}/adjuntos` | `incapacidades.attachments.index` | JSON de adjuntos. view∨edit∨review |
| POST | `/incapacidades/{incapacidad}/adjuntos` | `incapacidades.attachments.store` | Sube PDF/JPG (lote). **edit** |
| GET | `/incapacidades/{incapacidad}/adjuntos/{adjunto}` | `incapacidades.attachments.download` | Descarga. view∨edit∨review. 404 si el archivo no es de esa fila |
| DELETE | `/incapacidades/{incapacidad}/adjuntos/{adjunto}` | `incapacidades.attachments.destroy` | Borra archivo. **edit** |

Hojas: `vacaciones`, `incapacidades`, `retiros`, `permisos`. Parametros de ruta: `{vacacion}`, `{incapacidad}`, `{retiro}`, `{permiso}`.

**Hooks sin rutas nuevas** (Desvinculaciones / Ficha):

| Flujo | Punto | Efecto Retiros |
| --- | --- | --- |
| Create followup | `EmployeeTerminationFollowupService::ensureForClosedPeriod` | `ReportesNovedadesRetiroSyncService::ensureFromFollowup` (idempotente) |
| Revertir | `EmployeeTerminationFollowupService::revert` | `annulFromFollowup` (soft-delete + audit) **antes** de borrar followup |

Side-effect Retiros auto/annul corre bajo el flujo ya autorizado de Desvinculaciones/Ficha (**no** exige `reportes_novedades.retiros.edit`).

## Permisos

| Permiso | Uso |
| --- | --- |
| `view.board.gestion_humana.reportes_novedades` | Ver tablero en sidebar GH |
| `reportes_novedades.{hoja}.view` | Ver / filtrar / export / historial RO |
| `reportes_novedades.{hoja}.edit` | CRUD columnas GH |
| `reportes_novedades.{hoja}.review` | Editar columnas Nómina (+ ver/export via implicacion) |

Hojas: `vacaciones` \| `incapacidades` \| `retiros` \| `permisos` (12 keys funcionales + board).

### Implicaciones (`ReportesNovedadesAccessService`)

| Otorgado | Efecto runtime |
| --- | --- |
| `.edit` | Puede `.view` (aunque Spatie no tenga view) |
| `.review` | Puede `.view` + export; **no** implica `.edit` |
| `.edit` | **No** escribe cols Nómina |
| `.review` | **No** create/delete ni muta cols GH |

### Paquetes tipicos (asignacion manual Admin)

| Perfil | Permisos |
| --- | --- |
| GH operativo | board + 4× (`view`+`edit`) |
| Nómina | board + 4× `.review` |
| Solo consulta hoja | `.view` (+ board recomendado) |

**Sidebar:** visible con board **o** cualquier `.view`/`.edit`/`.review` de hoja (patron Gestion Clientes). Bypass: `manage.users`.

**Seed:** `super-admin` todos; `administrador` / `usuario` **sin** paquete por defecto.

Config: `config/access.php` (`system_permissions`, `boards`, `board_canonical_areas`, `reportes_novedades_tabs`, Admin subgroup **MT-GH-04 Novedades** bajo Gestion humana). `PermissionCatalog` acepta board solo en `gestion_humana`.

## Controladores y requests

| Clase | Responsabilidad |
| --- | --- |
| `ReportesNovedadesController` | Index redirect + lookup Ficha |
| `ReportesNovedadesVacacionesController` | Vista / DT / CRUD / review / export / historial Vacaciones; pasa `holidayDates` para sugerir fecha fin |
| `ReportesNovedadesIncapacidadesController` | Idem Incapacidades |
| `ReportesNovedadesRetirosController` | Idem Retiros (destroy soft) |
| `ReportesNovedadesPermisosController` | Idem Permisos |
| `LookupFichaRequest` | Auth edit any sheet; `document_number` |
| `Store*Request` / `Update*Request` | Validacion cols GH + catalogos `Rule::in` |
| `Review*Request` | Solo cols Nómina por hoja |

Namespace requests: `App\Http\Requests\GestionHumana\ReportesNovedades\`.

## Vistas

| Vista | Descripcion |
| --- | --- |
| `areas/gestion_humana/reportes_novedades/vacaciones.blade.php` | Listado DT + filtros + modal Nuevo (`rn-novedad-modal`: header con acento, body scroll, footer fijo; fecha fin sugerida por días hábiles) |
| `.../incapacidades.blade.php` | Idem |
| `.../retiros.blade.php` | Idem |
| `.../permisos.blade.php` | Idem |
| `.../partials/subnav.blade.php` | Pestanas module-tab |
| `.../partials/*-form-fields.blade.php` | Campos por hoja en secciones numeradas (Empleado / Novedad / …) |
| `.../partials/employee-lookup-fields.blade.php` | Lookup cedula compartido (paso 1) |
| `.../partials/period-filters.blade.php` | Filtros compartidos: mes (`type=month` YYYY-MM) + quincena + rango fechas + limpiar |
| `.../partials/historial-modal.blade.php` | Modal Historial compartido (timeline de auditoría) |

## Modelos y tablas

| Modelo | Tabla | Notas |
| --- | --- | --- |
| `ReportesNovedadesVacacion` | `reportes_novedades_vacaciones` | Col Nómina: `observacion_nomina`. Campos fecha: `fecha_inicio` + `fecha_fin` (obligatorios en formulario; backfill legacy = inicio + días − 1). |
| `ReportesNovedadesIncapacidad` | `reportes_novedades_incapacidades` | Col Nómina: `observacion_nomina`. `dias_entrega` **calculado** (no editable): si hay `fecha_envio_final` → envío−inicio; si no → hoy−inicio (`diasEntregaCalculados()`). Al borrar la fila, `deleting` elimina sus adjuntos (archivo en disco) antes del cascade SQL. |
| `ReportesNovedadesIncapacidadAdjunto` | `reportes_novedades_incapacidad_adjuntos` | PDF/JPG privados (`config reportes_novedades.incapacidades_adjuntos`, disco `local`). `uploaded_by` nullable. El nombre original no se pasa a mayúsculas (es nombre de archivo). |
| `ReportesNovedadesRetiro` | `reportes_novedades_retiros` | SoftDeletes; FK `employee_termination_followup_id` unique; col Nómina `observacion_nomina` |
| `ReportesNovedadesPermiso` | `reportes_novedades_permisos` | Col Nómina: `observacion_nomina`; `marca_gh` boolean |

Convenciones: `created_by` / `updated_by` (FK users `nullOnDelete`), timestamps, indices en `document_number` y fechas relevantes. Migraciones aditivas multi-driver (MySQL + sqlite).

### Ownership de columnas (resumen)

- **GH:** datos de empleado, catalogos, fechas, dias, observaciones GH, flags operativos.
- **Nómina:** `observacion_nomina` (todas). En Incapacidades, `dias_entrega` es calculado (no review).
- Update GH **no** incluye cols Nómina en fillable del request; review **solo** cols Nómina.

## Servicios / access / audit

| Clase | Rol |
| --- | --- |
| `ReportesNovedadesAccessService` | Board / view / edit / review / export + bypass `manage.users`; `visibleTabsFor` |
| `ReportesNovedadesAuditLogService` | Wrapper `module=reportes_novedades`, `area=gestion_humana` |
| `ReportesNovedadesFichaLookupService` | Cedula → prefill |
| `ReportesNovedadesHistorialService` | Lectura `audit_logs` filtrada por modulo + hoja/fila |
| `ReportesNovedades*DatatableService` | Query + format row por hoja (tope length 100) |
| `ReportesNovedadesIncapacidadAdjuntoService` | Guarda PDF/JPG de una incapacidad en disco privado con nombre UUID |
| `ReportesNovedadesPeriodFilter` | Resuelve mes/quincena (default actuales) vs `fecha_desde`/`fecha_hasta` (priman si hay al menos uno); aplica a `fecha_inicio` o `fecha_retiro` |
| `ReportesNovedadesRetiroSyncService` | `ensureFromFollowup` / `annulFromFollowup` |

Config: `config/reportes_novedades.php` (catalogos + `retiros_novedad_default` = `RETIRO`). Audit: `config/audit.php` → modulo `reportes_novedades`.

Nav: `NavigationResolver`, `SidebarVisibilityService`, `User::defaultReportesNovedadesBoardUrl()`.

### Lookup destino (cliente)

- Path principal (entry + requisition): `destino` = `clientName()` del entry de Ficha.
- Fallback sin entry (solo perfil): `destino` = `work_center_name` del perfil (obs. review #3; brief define destino = cliente).
- Si cedula no existe: alta manual permitida con mensaje (campos editables vacios). Lookup exige `canEditAnySheet`.

### Auditoria (eventos tipicos)

| Evento / action | Cuando |
| --- | --- |
| `{sheet}_novedad` / `create`\|`update`\|`delete`\|`review` | Mutaciones CRUD/review |
| `incapacidades_adjunto` / `create`\|`delete` | Alta o borrado de adjuntos de una incapacidad |
| `export` / `{sheet}_excel` | Export Excel |
| `retiro_auto` / `create`\|`annul` | Hook desvinculacion |

Historial UI: endpoint por pestana; lectura con view∨edit∨review de esa hoja. El modal de **historial de hoja** (toolbar) admite filtros `q` (cédula/usuario), `action` y `date_from`/`date_to`. El historial por fila no muestra filtros.

## Reglas de negocio

1. Labels exactos: tablero **MT-GH-04 Novedades**; pestanas **Vacaciones** / **Incapacidades** / **Retiros** / **Permisos**.
2. Sin estados de flujo; fila editable de inmediato segun permisos.
3. Catalogos fijos MT-GH-04 (ver `config/reportes_novedades.php`).
4. Export disponible a quien pueda ver la hoja; exporta **todas** las columnas (GH + Nómina); escritura sigue segregada.
5. Retiros auto: un followup → como maximo una fila activa (no soft-deleted); `observacion_nomina` null hasta review; motivo mapeado desde causal si hay match, si no null.
6. Alta manual Retiros permitida (sin FK followup).
7. Filas soft-deleted Retiros **no** aparecen en DT/export por defecto.
8. Destroy Retiros (GH): soft-delete (alinea con annul auto).
9. Filtros de listado/export (todas las hojas):
   - Por defecto: **mes** actual (`YYYY-MM`) + **quincena** actual (#1 = 01–15, #2 = 16–fin de mes).
   - Columna filtrada: `fecha_inicio` (Vacaciones / Incapacidades / Permisos) o `fecha_retiro` (Retiros).
   - Si hay `fecha_desde` **o** `fecha_hasta`, el rango prima y se vacían mes/quincena en la UI.
   - Al cambiar mes/quincena se vacían los datepickers de rango.
   - Limpiar vuelve a la URL sin query → mes + quincena actuales.
10. `observacion_nomina` con valor: celda del listado en pill verde (`status-pill--success`); input del modal con fondo verde (`.rn-novedad-form__input--nomina-filled`).
11. Vacaciones (alta/edición GH): al ingresar **días** y **fecha inicio**, el modal sugiere `fecha_fin` = inicio + N días hábiles (sin domingos ni festivos CO) y `fecha_reintegro` = día siguiente a fin; el usuario puede editar ambas. También persisten **períodos** y **firma** (`signatory_id`, catálogo `firmas`) para regenerar la carta Word; no se muestran en el listado.
12. Vacaciones: acción **Descargar carta Word** por fila (solo `reportes_novedades.vacaciones.edit`); usa `ClienteInternoCartasVacacionesGeneratorService` + plantilla activa `cartas_vacaciones`.
13. Permisos: la columna **Marca GH** del listado es un checkbox que persiste al instante vía `PATCH /permisos/{permiso}/marca-gh` (JSON). Solo `edit`; para `review`/`view` se muestra deshabilitado. Genera evento de auditoría `update` con `metadata.field = marca_gh`.
14. Incapacidades: acción **Adjuntos** en cada fila. Quien ve la hoja (ver, editar o revisar) consulta y descarga. Solo **edit** sube (PDF o JPG, en lote o en varios envíos; máx. 10 por envío y 10 MB c/u) o elimina un archivo. Borrar la incapacidad borra sus archivos. No van al Excel.
15. Incapacidades: en el modal (alta/edición GH), al ingresar **días** y **fecha inicio**, `calcFechaFin()` (Alpine) recalcula automáticamente `form.fecha_fin` = inicio + (N − 1) días corridos (incluye el día de inicio); el usuario puede editar la fecha fin manualmente.
16. Cédula duplicada: en el listado filtrado de cada una de las cuatro hojas, si la **cédula** aparece en más de una fila del mismo filtro, el valor de la columna Cédula se muestra en **rojo** (clase `.rn-cedula-duplicada`). La detección se calcula sobre el conjunto completo filtrado (todas las páginas), no solo la página visible. No afecta el export a Excel.

## JavaScript / assets

- Alpine + DataTables server-side embebidos en vistas Blade (patron GH).
- Vacaciones: `syncFechaFin()` (Alpine) recalcula `form.fecha_fin` y `form.fecha_reintegro` desde `dias_novedad` + `fecha_inicio` usando `holidaySet` (festivos CO enviados por el controller).
- Incapacidades: `calcFechaFin()` (Alpine) recalcula `form.fecha_fin` = `fecha_inicio` + (días − 1) días corridos al cambiar `form.dias` o `form.fecha_inicio` (`x-on:input`).
- Selectores catalogo: `<x-searchable-select>`.
- Botones icon-only: `.req-manage-filters__icon-btn` / `.cursos-catalogo-page__icon-btn`.
- Subnav: `.module-tab` / branding GH.

## Export Excel

Clases `App\Exports\ReportesNovedades{Vacaciones|Incapacidades|Retiros|Permisos}Export` extendiendo `BaseExport`. Boton `<x-export-excel>`. No `excelHtml5`.

## Validacion local

1. `php artisan migrate` (sin fresh).
2. Asignar paquete GH y/o Nómina en Admin (subgroup MT-GH-04 Novedades).
3. Vacaciones: CRUD + review + export + historial; sugerencia de fecha fin por días hábiles; smoke otras hojas.
4. Desvincular (Ficha o Masivos) → fila en Retiros; revertir → desaparece.
5. `php artisan test --compact --filter=ReportesNovedades` (+ unit `ReportesNovedadesPeriodFilter`).
6. `vendor/bin/pint --dirty --format agent` tras PHP.

## Riesgos y pendientes (incl. observaciones del review)

| Riesgo / obs. | Estado documentado |
| --- | --- |
| Unique `employee_termination_followup_id` incluye soft-deleted (obs. #1) | Si GH soft-deletea un retiro auto y `ensure` vuelve a correr con el mismo followup vivo, el create puede chocar. Follow-up: restore/`withTrashed` o unique parcial |
| Sin test Feature dedicado de lookup (obs. #2) | Servicio + request cableados; test post-cierre opcional |
| Fallback destino = `work_center_name` (obs. #3) | Path entry usa cliente; documentado arriba |
| Cobertura Incapacidades menos granular (obs. #4) | Smoke V1 OK |
| Export grande Hostinger | Filtros fecha V1; sin cola |
| Mapeo causal → `motivo_retiro` | Si no match 1:1, null; GH completa |

## Referencias

- Feature Brief: [`docs/briefs/FEAT-040.md`](../briefs/FEAT-040.md)
- Plan: [`docs/briefs/FEAT-040-plan.md`](../briefs/FEAT-040-plan.md)
- Review: [`docs/reviews/FEAT-040.md`](../reviews/FEAT-040.md)
- Doc usuario: [`docs/user/reportes-novedades.md`](../user/reportes-novedades.md)
- Desvinculaciones: [`docs/modules/desvinculaciones.md`](desvinculaciones.md)
- Ficha empleados: [`docs/modules/ficha-empleados.md`](ficha-empleados.md)
- Audit: [`docs/modules/audit-log.md`](audit-log.md)
- Acceso: [`docs/ACCESS_CONTROL.md`](../ACCESS_CONTROL.md)
- Guia documentacion: [`docs/DOCUMENTATION.md`](../DOCUMENTATION.md)
