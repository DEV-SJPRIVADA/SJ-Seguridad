# Feature Brief — FEAT-039

> Brief final del Arquitecto (2026-09-28). Consolida borrador Analista + decisiones de negocio cerradas (chat / run log 2026-09-28) + supuestos S1–S6 formalizados. **No es borrador.** No reabrir la ley de negocio.

## Identificacion

| Campo | Valor |
| --- | --- |
| ID | FEAT-039 |
| Modulo / area | Gestion humana — tablero **Acreditaciones** (`acreditaciones`) — pestañas **Export Apo** + **Dashboard** (+ parámetros en **Catálogo**) |
| Titulo | Export Apo SuperVigilancia (.xls) + Dashboard Acreditaciones |
| Solicitante | Usuario / AgentSj (chat 2026-09-28) |
| Fecha | 2026-09-28 |

## Objetivo

Operativizar las pestañas **Export Apo** y **Dashboard** (hoy placeholders FEAT-036) para que Gestión Humana:

1. Genere el archivo **APO SuperVigilancia** (`.xls`, 1 hoja, headers A–X exactos) a partir de **Acreditados** candidatos a nueva acreditación / renovación, enriquecidos con **Ficha activa** y **Cursos** (CódigoCurso, NitEscuela, Nro), con **preview + validación de novedades** antes de descargar.
2. Consulte un **Dashboard** de lectura con KPIs de Acreditados, candidatos/novedad de export y últimas corridas Export Apo.
3. Edite **una fila** de parámetros Export Apo en **Catálogo** (seed inicial cerrado).

## Decisiones de negocio (ley — no reabrir)

| # | Tema | Decision |
| --- | --- | --- |
| 1 | Origen filas | Base: **Acreditados** (par cédula + `cargo_apo`) + **Ficha activa** (`employment_status = activo`). Usuario **selecciona manualmente** las filas a exportar. |
| 2 | Multicargos | **1 fila Excel por par** cédula+cargo. Misma cédula con 2 cargos candidatos → **2 filas**. |
| 3 | Universo candidatos | Estados: **`EN_PROCESO`**, **`POR_VENCER`**, **`DESACREDITADO`**. Excluir **`ACREDITADO`** “fresco” (fuera de ventana por vencer). |
| 4 | Headers Excel | Exactos A–X (ver § Columnas). **1 hoja**. Extensión **`.xls`**. |
| 5 | Cargo | Valor Excel `Cargo` = `acreditacion_cargos.cargo_acreditacion` del `cargo_apo` del acreditado (códigos 1,2,4,5,6…). |
| 6 | TipoDocumento | Siempre el valor de parámetros (seed **`1`**). |
| 7 | Identidad / fechas | Desde **Ficha**; Ficha incompleta → **bloqueo duro** + notifica en preview. |
| 8 | CodigoCurso | Desde catálogo tipos de curso (`curso_tipos.cursos`); match F\|R del mismo cargo; elegir el de **`fecha_expedicion` más reciente**. |
| 9 | NitEscuela + Nro | Snapshot del curso (`escuela_nit`, `numero_curso`) + validar escuela en catálogo. |
| 10 | Fechas Excel | Formato **`dd/mm/yyyy`**. |
| 11 | Valida / novedad | **Solo preview UI**; **no** columnas Valida/novedad en el `.xls`. |
| 12 | Política vigencia | Al exportar: **solo VIGENTE** **o** **VIGENTE + ACTUALIZAR**. |
| 13 | Modal novedades | Antes de generar: ¿incluir filas con novedad **blanda**? **Sí / No**. |
| 14 | Nombre archivo | `APO{Nit}{yyyymmdd}{seq3}.xls` — ej. `APO900576718620260928001`. Prefijo = Nit de parámetros. Seq **3 dígitos**; **persistir** corridas diarias. |
| 15 | Parámetros | **Una fila editable** en **Catálogo** (seed § Seed). |
| 16 | Dashboard | Mismo FEAT. Visible con **`acreditaciones.view`**. |
| 17 | Permisos | Preview / export / forzar novedades blandas / editar parámetros: **`acreditaciones.edit`**. Dashboard: **`acreditaciones.view`**. **Sin permiso nuevo.** **No** tocar `config/access.php`. |
| 18 | Referencia real | Cargo 1/2/4, CodigoCurso 1201/2201/3201; misma cédula puede salir en 2 filas. |

## Decisiones tecnicas del Arquitecto

| Tema | Decision | Justificacion |
| --- | --- | --- |
| **S1 CodigoCurso** | Valor Excel = `trim((string) curso_tipos.cursos)` del tipo del curso match. Si null/vacío → **novedad blanda** (motivo claro). **No** inventar códigos ni migrar datos Cursos en esta feature. | Campo ya existe (`curso_tipos.cursos`); UI Catálogo Cursos lo edita como «CURSOS». Ej. real 1201/2201/3201 viven ahí cuando estén cargados. |
| **S2 Match F\|R** | Ver § Match curso. Primario: `cargo_acredit` (normalizado) ≡ `cargo_apo` del acreditado **y** el `tipo_curso` es familia **F** o **R**. Fallback si `cargo_acredit` vacío: `tipo_curso` normalizado ≡ `F.{cargo_apo}` o `R.{cargo_apo}`. Entre candidatos, max `fecha_expedicion` (empate → max `id`). **No** mezclar cargos. | Aprovecha `cargo_acredit` (FEAT-032) + ley F\|R del usuario; fallback cubre tipos sin `cargo_acredit` poblado. |
| **S3 Ficha** | Obligatorios sin novedad/bloqueo: `document_number`, `first_name`, `first_surname`, `birth_date`, `sex`, `hire_date`. `second_name` / `second_surname` pueden ir vacíos en Excel. **Genero** = `sex` tal cual (`M`/`F`). | Ley 7; alinear a columnas Ficha reales. |
| **S4 Novedad** | Ver § Clasificación novedades. **Blanda** = modal Sí puede incluir; **dura** (ficha incompleta) = **nunca** se exporta aunque modal = Sí (celdas vacías no se emiten). | Confirma regla 18 del Analista; evita APO inválido ante SuperVigilancia. |
| **S5 Tab Export Apo** | Visible y operable **solo** con `acreditaciones.edit` (mismo patrón Catálogo / Validaciones). Actualizar `AcreditacionesAccessService::visibleTabsFor` para filtrar también `export_apo` si `!canEdit`. Rutas `export-apo*` → `canEdit`. Dashboard permanece con `view`. | Usuario solo view no ve pestaña vacía/confusa. |
| **S6 Writer `.xls`** | Clase dedicada `App\Exports\AcreditacionExportApoXlsExport` con PhpSpreadsheet `Writer\Xls`. **No** usar `BaseExport` (emite `.xlsx`). **Prohibido** `excelHtml5`. Descarga streaming / response file. | Stack ya tiene PhpSpreadsheet; ley exige `.xls`. |
| Parámetros BD | Tabla **fila única** `acreditacion_export_apo_settings` (columnas tipadas = keys del seed). Seeder idempotente asegura exactamente 1 fila. | «Una fila editable»; Form Request tipado; sin key-value genérico v1. |
| Corridas BD | Tabla `acreditacion_export_apo_runs`: `export_date` + `seq` + user + opciones + conteos + `file_name`. Unique `(export_date, seq)`. Seq reinicia por día calendario `America/Bogota`. | Persistencia ley 14; concurrencia con unique + transacción. |
| Nombre archivo | `APO` + `settings.nit` + `Ymd` (Bogotá) + `str_pad(seq, 3, '0')` + `.xls`. | Alinea prefijo al Nit editable; seed → `APO9005767186…`. |
| Cargo Excel | Resolver `cargo_apo` → `cargo_acreditacion`: filas **activas** del catálogo con ese `cargo_apo`; desempate `sort_order ASC`, `id ASC`. Si entre activas hay **valores distintos** de `cargo_acreditacion` → novedad blanda «cargo ambiguo». Si ninguna activa → novedad blanda. | Multicatalogo manager→mismo APO (seed VIGILANTE/ESCOLTA/SUPERVISOR). |
| Vigencia curso | Reusar `EmployeeCurso::computeVigencia()` / accessor. Política `VIGENTE`: solo vigencia `VIGENTE`. Política `VIGENTE_ACTUALIZAR`: `VIGENTE` ∪ `ACTUALIZAR`. `VENCIDO` nunca es match limpio → novedad blanda si no hay otro match válido. | FEAT-032 ya calcula vigencia. |
| Escuela | `NitEscuela` = snapshot `employee_cursos.escuela_nit`. Válida si existe fila en `curso_escuelas` con nit normalizado igual (trim) **o** `curso_escuela_id` apunta a escuela activa. Fallo → novedad blanda. | Ley 9. |
| Fechas celdas | Formatear como string `dd/mm/yyyy` (no serial Excel ambiguo). Timezone app. | Ejemplo real usuario. |
| Controlador | Extender `AcreditacionesController` con `dashboard*`, `exportApo*`, `catalogoExportApoParams*` (vertical slice). | Consistencia FEAT-036–038. |
| Services | `AcreditacionExportApoCandidateService` (universo + DT), `AcreditacionExportApoRowResolver` (ficha/cargo/curso/novedades), `AcreditacionExportApoPreviewService`, `AcreditacionExportApoGenerateService` (seq + writer + run), `AcreditacionDashboardService` (KPIs). Reusar `AcreditacionCargoMatchNormalizer` donde aplique. | Sin Repository. |
| Form Requests | Preview / Generate / Update params (authorize = `canEdit`). Metrics dashboard authorize = `canView`. | Validación centralizada. |
| Dashboard KPIs | Conteos Acreditados por `estado`; conteo **candidatos** (mismo universo SQL); conteo **con novedad potencial** vía Resolver con política default `VIGENTE` (chunk; sin persistir); tabla **últimas 20** corridas desde `acreditacion_export_apo_runs`. Endpoint JSON `dashboard.metrics` opcional (patrón Cursos). **Sin** ApexCharts obligatorio en v1. | Enfoque barato; corridas ya en BD. |
| Audit | Auditar `export_apo_generate` (resumen: file_name, conteos, opciones) y `export_apo_settings` update vía `AcreditacionesAuditLogService`. **No** auditar GET/preview/DT/metrics. | Mutaciones + descarga con efecto de negocio. |
| Config | Claves en `config/acreditaciones.php` → `export_apo` (headers A–X, políticas, seq pad, dashboard last_n) y labels dashboard si hace falta. | Sin tocar `access.php`. |
| Permisos access.php | **No modificar** `config/access.php`. Reusar `acreditaciones.view` / `acreditaciones.edit` + board existente. | Ley 17; flag TASKS ya parcial sin access.php. |
| CSS | Reusar chrome/filtros/icon-btn; `app.css` solo estilos mínimos justificados. | shared-files posible menor. |
| Repository / Select2 / excelHtml5 / migrate:fresh | **Prohibidos.** | AGENTS.md. |

### Supuestos S1–S6 (formalizados)

| # | Formalizacion Arquitecto | Riesgo residual |
| --- | --- | --- |
| S1 | `CodigoCurso` ← `curso_tipos.cursos`. Vacío → novedad blanda; GH debe cargar códigos APO en Catálogo Cursos. | Datos legacy sin código. |
| S2 | Match: (`cargo_acredit` ≡ `cargo_apo` **y** tipo F\|R) **o** fallback `tipo_curso` ∈ {`F.{apo}`, `R.{apo}`}; max `fecha_expedicion`. | Tipos mal nombrados / `cargo_acredit` incorrecto. |
| S3 | Campos ficha listados arriba; Genero = `sex`. | Si SuperVigilancia exige otro código de género → ajuste post-prueba (fuera de v1 salvo hallazgo). |
| S4 | Blanda vs dura (§ Clasificación); modal Sí **no** levanta bloqueo duro. | — |
| S5 | Tab + rutas Export Apo = edit; Dashboard = view. | Cambio vs placeholder actual (ruta export-apo hoy view) — intencional. |
| S6 | Writer Xls dedicado; no BaseExport. | Probar apertura en Excel/LibreOffice del `.xls` generado. |

### Match curso (algoritmo)

Entrada: `document_number`, `cargo_apo`, política vigencia (`VIGENTE` \| `VIGENTE_ACTUALIZAR`).

1. Cargar cursos de la cédula con relación `cursoTipo` (activo preferido; si tipo inactivo igual se evalúa pero documentar como posible novedad si se usa).
2. Filtrar tipos **familia F\|R**:
   - Normalizar strings con la misma función que `AcreditacionCargoMatchNormalizer` (trim, colapsar espacios, `mb_strtoupper`, strip diacríticos).
   - Familia F\|R si `tipo_curso` normalizado hace match `/^[FR]\./` **o** (fallback estricto) igual a `F.{cargo_apo_norm}` / `R.{cargo_apo_norm}`.
3. Filtrar mismo cargo:
   - Si `cargo_acredit` del tipo no vacío: `norm(cargo_acredit) === norm(cargo_apo)`.
   - Else: `norm(tipo_curso)` ∈ {`F.{apo}`, `R.{apo}`}.
4. Aplicar política de vigencia sobre `computeVigencia()` del curso.
5. Si conjunto vacío → sin curso válido (novedad blanda).
6. Elegir max `fecha_expedicion`, empate max `id`.
7. `CodigoCurso` = `cursos` del tipo; vacío → novedad blanda adicional.
8. Validar escuela (§ Decisiones).

**Ejemplo ley:** export fila ESCOLTA → solo F.ESCOLTA / R.ESCOLTA (o `cargo_acredit=ESCOLTA` + tipo F\|R); **no** usar curso de SUPERVISOR.

### Clasificacion novedades

| Codigo motivo (interno) | Tipo | Modal Sí incluye? |
| --- | --- | --- |
| `ficha_incompleta` | **Dura** | **No** (siempre omitir) |
| `sin_ficha_activa` | Dura / fuera de universo | No seleccionable en DT |
| `sin_curso_match` | Blanda | Sí |
| `curso_vencido_sin_alterna` | Blanda | Sí |
| `codigo_curso_vacio` | Blanda | Sí |
| `escuela_no_catalogo` | Blanda | Sí |
| `cargo_acreditacion_no_resoluble` | Blanda | Sí |
| `cargo_acreditacion_ambiguo` | Blanda | Sí |

Preview: columnas de negocio + `valida` (bool) + `motivo` (texto). Export limpio: solo filas `valida=true`. Con modal Sí: limpia + blandas; **nunca** duras.

## Alcance

### Incluye

- Sustituir placeholders de **Export Apo** y **Dashboard**.
- Migraciones aditivas multi-driver: settings (1 fila) + runs; seeder parámetros.
- Listado candidatos (universo ley 3 + Ficha activa) DataTables **server-side**; selección manual; preview Valida/motivo.
- Opciones: política vigencia + modal incluir novedades blandas Sí/No.
- Generación `.xls` SuperVigilancia (headers A–X, 1 hoja, fechas `dd/mm/yyyy`, nombre con seq diario persistido).
- Parámetros editables en Catálogo (sección/fila única).
- Dashboard lectura: KPIs + candidatos/novedad + últimas corridas.
- Visibilidad tab Export Apo solo edit; Dashboard view.
- Tests PHPUnit (permisos, universo, multicargos, match curso, bloqueo ficha, seq/nombre, fechas, dashboard).
- Docs módulo + usuario al Documentador.

### Fuera de alcance

- Validaciones / Reporte Diario / CRUD Acreditados (ya FEAT-037/038/036).
- Bridge que escriba Acreditados desde el export o desde Cursos.
- Poblar/migrar masivamente `curso_tipos.cursos` (solo documentar que deben existir códigos APO).
- Notificaciones correo.
- Permiso nuevo / cambios en `config/access.php`.
- Auto-export sin selección; export de todo el universo sin preview.
- Soft-delete / versionado de archivos en disco (solo metadata run + descarga en el momento).
- ApexCharts obligatorios en Dashboard Acreditaciones.
- Select2 / `excelHtml5` / Repository / `migrate:fresh`.

## Reglas de negocio

### Acceso

1. **Dashboard** (shell + metrics): `acreditaciones.view` (o edit / bypass `manage.users`).
2. **Export Apo** (tab, shell, DT, preview, generar, modal): `acreditaciones.edit` (o bypass).
3. **Editar parámetros** (Catálogo): `acreditaciones.edit`.
4. Usuario solo `view`: ve Dashboard operativo; **no** ve Export Apo ni edita parámetros.

### Universo y seleccion

5. Candidatos = `acreditacion_acreditados` con `estado IN ('EN_PROCESO','POR_VENCER','DESACREDITADO')` cuya cédula tenga Ficha `employment_status = activo`.
6. Una fila de listado/preview/export por par cédula + `cargo_apo` (PK lógica del acreditado).
7. Export solo de IDs/filas **seleccionadas** (mínimo 1).
8. Sin Ficha activa → fuera del universo (no seleccionable).

### Enriquecimiento Excel

9. Parámetros de empresa se copian iguales en todas las filas desde la fila única de settings.
10. Identidad: `NoDocumento` = cédula; nombres/apellidos/fechas/género desde Ficha (§ S3).
11. `Cargo` = `cargo_acreditacion` resuelto (§ Decisiones).
12. Curso: match § Match; política vigencia del request.
13. Fechas en celdas: `dd/mm/yyyy`.

### Preview, novedades y descarga

14. Preview muestra Valida + motivo (no van al xls).
15. Default: solo filas válidas. Modal Sí incluye **blandas**; **nunca** duras.
16. Tras export OK: en transacción DB obtener `max(seq)+1` del `export_date` (Bogotá), insert run, stream descarga con `file_name` canónico. Unique evita choque concurrente → reintentar o 409 claro.

### Dashboard

17. Sin mutaciones. KPIs + últimas corridas (§ Decisiones).

### Datos

18. Solo migraciones **aditivas** multi-driver (sqlite + MySQL). Prohibido `migrate:fresh` / wipe / TRUNCATE masivo sin OK usuario.

## Columnas Excel SuperVigilancia (headers exactos, orden A–X)

| Col | Header | Origen |
| --- | --- | --- |
| A | Nit | Settings |
| B | RazonSocial | Settings |
| C | TipoDocumento | Settings (seed 1) |
| D | NoDocumento | Ficha / acreditado cédula |
| E | Nombre1 | Ficha `first_name` |
| F | Nombre2 | Ficha `second_name` (puede vacío) |
| G | Apellido1 | Ficha `first_surname` |
| H | Apellido2 | Ficha `second_surname` (puede vacío) |
| I | FechaNacimiento | Ficha `birth_date` → `dd/mm/yyyy` |
| J | Genero | Ficha `sex` |
| K | Cargo | Catálogo → `cargo_acreditacion` |
| L | Fechavinculacion | Ficha `hire_date` → `dd/mm/yyyy` |
| M | CodigoCurso | Tipo curso match → `cursos` (S1) |
| N | NitEscuela | Curso + validación catálogo |
| O | Nro | Curso `numero_curso` |
| P | TipoEstablecimiento | Settings |
| Q | TelefonoR | Settings |
| R | DireccionR | Settings |
| S | DireccionP | Settings |
| T | Departamento | Settings |
| U | Ciudad | Settings |
| V | EducacionBM | Settings |
| W | EducacionS | Settings |
| X | Discapacidad | Settings |

## Seed parametros Export Apo

| Clave (columna) | Valor |
| --- | --- |
| Nit | 9005767186 |
| RazonSocial | SJ SEGURIDAD PRIVADA LTDA |
| TipoDocumento | 1 |
| TipoEstablecimiento | Principal |
| TelefonoR | 3043413064 |
| DireccionR | MANZANA 8 CASA 27 |
| DireccionP | AV 4N26N 39 |
| Departamento | ValledelCauca |
| Ciudad | CALI |
| EducacionBM | 11 |
| EducacionS | Ninguna |
| Discapacidad | Ninguna |

UI: sección en **Catálogo** Acreditaciones (1 fila; PATCH; no CRUD multi-fila).

## Permisos (`config/access.php`)

| Permiso | Rol(es) | Descripcion (uso FEAT-039) |
| --- | --- | --- |
| `view.board.gestion_humana.acreditaciones` | Asignación manual | Ver tablero |
| `acreditaciones.view` | Idem | Dashboard (lectura) |
| `acreditaciones.edit` | Idem | Export Apo completo + editar parámetros |
| `manage.users` | Admin | Bypass runtime (existente) |

**Sin permiso nuevo. No editar `config/access.php`.** Documentar en docs que Export Apo = edit y Dashboard = view (cambio vs placeholder FEAT-036 que exponía Export Apo a view).

## Rutas

Archivo: `routes/areas/gestion_humana.php`  
Prefijo: `/gestion-humana/acreditaciones` · nombre `gestion-humana.acreditaciones.`  
Middleware grupo: `auth`, `active` (+ `password.changed` según patrón existente).

| Metodo | URI | Nombre | Permiso | Notas |
| --- | --- | --- | --- | --- |
| GET | `/dashboard` | `dashboard` | `view` | **Reemplaza** placeholder; KPIs + últimas corridas |
| GET | `/dashboard/metrics` | `dashboard.metrics` | `view` | JSON KPIs (opcional pero recomendado; patrón Cursos) |
| GET | `/export-apo` | `export-apo` | **`edit`** | Shell candidatos + opciones; **cambia** gate vs placeholder view |
| GET | `/export-apo/datatable` | `export-apo.datatable` | `edit` | DT server-side candidatos |
| POST | `/export-apo/preview` | `export-apo.preview` | `edit` | Body: ids + `vigencia_policy` → Valida/motivo |
| POST | `/export-apo/generar` | `export-apo.generate` | `edit` | Body: ids + policy + `include_novedades` → `.xls` + run |
| PATCH | `/catalogo/export-apo-params` | `catalogo.export-apo-params.update` | `edit` | Actualiza fila única settings |

**No** editar `routes/web.php`. Sustituir métodos placeholder `dashboard()` / `exportApo()`.

### Middleware de permiso

- Controller: `canView` en `dashboard*`; `canEdit` en todos `exportApo*` y update params.
- `AcreditacionesAccessService::visibleTabsFor`: ocultar `export_apo` (además de `catalogo`, `validaciones`) si `!canEdit`.

## Base de datos

Migraciones **aditivas**, multi-driver (evitar `enum()` / `change()` MySQL-only).

### `acreditacion_export_apo_settings` (create)

| Columna | Tipo | Notas |
| --- | --- | --- |
| `id` | bigint PK | |
| `nit` | string(20) | Seed `9005767186` |
| `razon_social` | string(255) | |
| `tipo_documento` | string(10) | Seed `1` |
| `tipo_establecimiento` | string(50) | |
| `telefono_r` | string(30) | |
| `direccion_r` | string(255) | |
| `direccion_p` | string(255) | |
| `departamento` | string(100) | |
| `ciudad` | string(100) | |
| `educacion_bm` | string(50) | |
| `educacion_s` | string(50) | |
| `discapacidad` | string(50) | |
| `updated_by` | FK users nullable | `nullOnDelete` |
| `timestamps` | | |

App + seeder: **exactamente 1 fila** (upsert / firstOrCreate id=1 o singleton). No key-value v1.

### `acreditacion_export_apo_runs` (create)

| Columna | Tipo | Notas |
| --- | --- | --- |
| `id` | bigint PK | |
| `export_date` | date | Día calendario Bogotá |
| `seq` | unsignedSmallInteger | 1…999 |
| `file_name` | string(80) | Sin path; ej. `APO900576718620260928001.xls` |
| `user_id` | FK users | `nullOnDelete` nullable defensivo |
| `vigencia_policy` | string(30) | `VIGENTE` \| `VIGENTE_ACTUALIZAR` |
| `include_novedades` | boolean | |
| `rows_selected` | unsignedInteger | |
| `rows_ok` | unsignedInteger | válidas |
| `rows_novedad` | unsignedInteger | blandas detectadas en selección |
| `rows_blocked` | unsignedInteger | duras omitidas |
| `rows_exported` | unsignedInteger | filas escritas en xls |
| `created_at` | timestamp | (updated_at opcional; create-only OK) |

Indexes: **unique** `(export_date, seq)`; index `export_date`; index `user_id`.

### Lectura (sin alter)

`acreditacion_acreditados`, `acreditacion_cargos`, `employee_ficha_profiles`, `employee_cursos`, `curso_tipos`, `curso_escuelas`, `users`.

## Capas a implementar

- [ ] Migracion(es) — settings + runs (aditivas, multi-driver)
- [ ] Modelo(s) — `AcreditacionExportApoSetting`, `AcreditacionExportApoRun` (+ factories)
- [ ] Seeder — `AcreditacionExportApoSettingSeeder` (idempotente)
- [ ] Controlador(es) — `dashboard*`, `exportApo*`, update params en `AcreditacionesController`
- [ ] Form Request(s) — preview, generate, update params
- [ ] Vista(s) Blade — `dashboard.blade.php`, `export-apo.blade.php`; sección params en `catalogo.blade.php`; dejar de usar placeholder para estas tabs
- [ ] JavaScript — DT server-side, selección, preview, modal Sí/No, política vigencia
- [ ] Services — Candidate / RowResolver / Preview / Generate / Dashboard
- [ ] Export — `AcreditacionExportApoXlsExport` (`Writer\Xls`)
- [ ] Access — ocultar tab `export_apo` sin edit
- [ ] Config — `config/acreditaciones.php` claves `export_apo` / dashboard
- [ ] Audit — generate + settings update
- [ ] Tests Feature
- [ ] `config/access.php` — **no**

## Componentes reutilizables

| Componente | Uso |
| --- | --- |
| DataTables `serverSide: true` | Candidatos Export Apo (tope length 100; sin `-1`) |
| `<x-searchable-select>` | Política vigencia u otros selects; **prohibido Select2** |
| `.module-tab` / subnav | Tabs; Export Apo solo edit |
| `.req-manage-filters__icon-btn` / `.cursos-catalogo-page__icon-btn` | Chrome / acciones |
| `AcreditacionesAccessService` / `HasAcreditacionesTabs` | Acceso + tabs |
| `AcreditacionCargoMatchNormalizer` | Normalizar cargo_apo / cargo_acredit / tipo_curso |
| PhpSpreadsheet `Writer\Xls` | Archivo SuperVigilancia |
| Dashboard Cursos | Referencia UX KPIs (sin obligación charts) |
| `AcreditacionesAuditLogService` | Generate + update params |
| `EmployeeCurso::computeVigencia` | Política vigencia |

## Documentacion a actualizar

- [ ] `docs/modules/acreditaciones.md` — Export Apo + Dashboard operativos; tablas; rutas; quitar placeholders
- [ ] `docs/user/acreditaciones.md` — preview, novedades, vigencia, descarga, parámetros, dashboard
- [ ] `docs/modules/cursos.md` — nota: campo `cursos` = CódigoCurso APO; `cargo_acredit` usado en match Export Apo
- [ ] `docs/INDEX.md` — si lista placeholders
- [ ] `docs/ACCESS_CONTROL.md` — Export Apo = edit; Dashboard = view
- [ ] `README.md` — solo si menciona placeholders

## Archivos compartidos (`shared-files`)

| Archivo | ¿Se toca? | Notas |
| --- | --- | --- |
| `routes/areas/gestion_humana.php` | **Sí** | Rutas dashboard.metrics + export-apo* + catalogo params; gate export-apo → edit |
| `config/access.php` | **No** | Sin permiso nuevo |
| `routes/web.php` | **No** | |
| Layouts globales | **No** | |
| Seeder módulo | **Sí** | `AcreditacionExportApoSettingSeeder` (módulo; no RolePermission) |
| `resources/css/app.css` | Posible | Solo estilos mínimos |
| Módulo Cursos | **Lectura** modelos/tablas; **no** mutar Cursos (salvo doc) |

Flag en `docs/TASKS.md`: `shared-files: routes/areas/gestion_humana.php` (y `app.css` si aplica). **No** `access.php`.

## Criterios de aceptacion

1. Usuario solo `view` ve **Dashboard** operativo (no «Próximamente») y **no** ve pestaña Export Apo; GET `/export-apo` sin edit → 403.
2. Con `edit`, Export Apo lista candidatos `EN_PROCESO` / `POR_VENCER` / `DESACREDITADO` con Ficha activa; excluye `ACREDITADO` fresco.
3. Multicargo: 2 cargos candidatos → 2 filas seleccionables / exportables.
4. Preview muestra Valida + motivo; xls **sin** esas columnas; headers A–X exactos; 1 hoja; extensión `.xls`.
5. Fechas en celdas `dd/mm/yyyy`.
6. `Cargo` = `cargo_acreditacion`; `TipoDocumento` = valor settings (seed 1).
7. Match curso F\|R del cargo (§ Match); más reciente por `fecha_expedicion`; no mezclar cargos (caso ESCOLTA vs SUPERVISOR).
8. Política vigencia: solo VIGENTE vs VIGENTE+ACTUALIZAR según request.
9. Modal Sí incluye solo novedades **blandas**; ficha incompleta **nunca** exporta.
10. Nombre `APO{nit}{yyyymmdd}{seq3}.xls`; seq diario persistido e incrementa; unique por día.
11. Parámetros seed visibles/editables en Catálogo (una fila).
12. Dashboard: KPIs Acreditados por estado + candidatos + novedad potencial + últimas corridas.
13. Sin cambios en `config/access.php`; sin `migrate:fresh`.
14. Tests Feature: permisos view/edit, universo, multicargo, match, ficha dura, seq, smoke dashboard/export.
15. DataTables server-side en candidatos; sin Select2; Writer Xls dedicado (no BaseExport / no excelHtml5).

## Validacion local

1. `php artisan migrate` (sin fresh) + seed settings.
2. Verificar en Catálogo Cursos que tipos F\|R tengan `cursos` (1201/2201/3201) y `cargo_acredit` alineado a APO.
3. Seleccionar 1–2 cargos de una cédula real → preview → export `.xls` → contrastar con archivo de referencia.
4. Probar modal novedades Sí/No y ambas políticas de vigencia; ficha incompleta no sale aunque Sí.
5. Usuario solo view vs edit (tabs + 403).
6. Dos exports el mismo día → seq `001`, `002`.
7. `php artisan test --compact` (filtros Export Apo / Dashboard Acreditaciones).
8. `vendor/bin/pint --dirty --format agent` tras PHP.

## Riesgos y dependencias

| Riesgo | Mitigacion |
| --- | --- |
| `curso_tipos.cursos` vacío en datos reales | S1; novedad blanda; doc usuario: cargar CódigoCurso APO en Catálogo Cursos |
| `cargo_acredit` vacío / desalineado | Fallback `F.{apo}`/`R.{apo}`; tests; doc |
| Writer Xls vs Excel moderno | Probar apertura; PhpSpreadsheet Writer\Xls; clase dedicada |
| Seq concurrente mismo día | Unique `(export_date,seq)` + transacción |
| Volumen candidatos | DT server-side; Resolver por ids seleccionados en preview/generate (no todo el universo en memoria al exportar) |
| Ambigüedad cargo_acreditacion | Novedad blanda si múltiples códigos activos para mismo `cargo_apo` |
| shared-files `gestion_humana.php` | Un Feature a la vez; AgentSj coordina |
| Cambio gate Export Apo view→edit | Intencional (S5); documentar en ACCESS_CONTROL |

### Dependencias

- FEAT-036 (shell, Acreditados, Catálogo cargos).
- FEAT-032 (Cursos: tipos, escuelas, vigencia).
- Ficha empleados (identidad, hire_date, sex).
- PhpSpreadsheet (`Writer\Xls`).

## Task Cards sugeridas (vertical slices — plan AgentSj)

> Un Agente Feature por slice; orden recomendado. Marcar `shared-files` en la TC que toque `gestion_humana.php`.

| ID sugerido | Slice | Entrega |
| --- | --- | --- |
| **FEAT-039-T1** | Settings + seed + runs + Catálogo params | Migraciones multi-driver settings/runs; modelos/factories; seeder; PATCH params + UI sección Catálogo; config `export_apo`; audit update params |
| **FEAT-039-T2** | Resolve / preview / candidatos | Tab Export Apo solo edit (`AccessService` + rutas shell/DT/preview); CandidateService + DT; RowResolver (S1–S4 match/ficha/novedades); Preview endpoint + UI selección/Valida/motivo + selector vigencia |
| **FEAT-039-T3** | Generar `.xls` + runs + modal | GenerateService + `AcreditacionExportApoXlsExport` (Writer Xls); seq diario; modal Sí/No; descarga; audit generate; tests seq/headers/fechas |
| **FEAT-039-T4** | Dashboard + tests integrados + polish | Vista/metrics Dashboard; KPIs + últimas corridas; Feature tests criterios restantes; pint; ajustes UX |

Documentador (post-Revisor): `docs/modules/acreditaciones.md` + `docs/user/acreditaciones.md` (+ cursos.md nota CodigoCurso).

## Aprobacion

- [x] Analista — vacíos de negocio cerrados (decisiones usuario 2026-09-28); supuestos S1–S6 documentados; **0 preguntas abiertas**
- [x] Arquitecto — brief final (S1–S6, rutas, esquema settings/runs, bloqueo duro ficha, Task Cards T1–T4)
- [ ] Usuario — confirmación opcional (negocio ya cerrado)
- [ ] AgentSj — plan `docs/briefs/FEAT-039-plan.md` + Task Cards en `docs/TASKS.md`

---

## Instruccion a AgentSj

1. **No** re-preguntar ley de negocio.
2. Generar plan de orquestación + Task Cards **FEAT-039-T1…T4** según este brief.
3. `shared-files`: `routes/areas/gestion_humana.php` (+ `app.css` si hace falta). **No** `config/access.php`.
4. Secuencia: Feature T1→T4 → Revisor → Documentador.
5. Actualizar `docs/TASKS.md` y `docs/runs/FEAT-039-run-log.md`.

### Blocker

**NO** — diseño cerrado; riesgos de datos (CodigoCurso vacío) mitigados con novedad, no bloquean implementación.
