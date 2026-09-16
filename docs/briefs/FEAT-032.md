# Feature Brief — FEAT-032

> Brief final del Arquitecto (2026-09-15). Consolida `docs/briefs/FEAT-032-analyst.md` + **Decisiones CERRADAS del usuario (2026-09-15)**. **No es borrador.**

## Identificacion

| Campo | Valor |
| --- | --- |
| ID | FEAT-032 |
| Modulo / area | Gestion humana — tablero **Cursos** (`cursos`) |
| Titulo | Tablero Cursos (listado, vigencia, CRUD, catálogo, plantilla e import/export Excel) |
| Solicitante | Usuario / AgentSj (chat 2026-09-15) |
| Fecha | 2026-09-15 |

## Objetivo

Dar a Gestion Humana un **tablero operativo** para controlar los **cursos** de personas (empleados y, si aplica, cédulas aún no en ficha): consultar vigencia automática (rojo/verde), gestionar estado de trámite, administrar un **catálogo** de tipos de curso, y operar con **CRUD manual** más **plantilla vacía + carga masiva** y **export Excel** del listado.

Hoy no existe board, permisos, tablas ni docs de Cursos. El módulo sigue el patrón de área única GH (Ficha empleados / Desvinculaciones / Archivo / Plantillas Word).

## Decisiones de negocio (ley — no reabrir)

| # | Tema | Decision |
| --- | --- | --- |
| 1 | Permisos | `view.board.gestion_humana.cursos` + `cursos.view` (listado) + `cursos.edit` (CRUD + import/plantilla + catálogo). Import y plantilla bajo `cursos.edit`. |
| 2 | Alcance V1 | **CRUD completo** (crear / editar / eliminar) **+ import** + plantilla vacía + **export Excel** del listado. |
| 3 | Catálogo | Tabla propia con columnas: TIPO CURSO, CARGO CURSO, FORMATO PARA CURSOS, CURSOS, CARGO ACREDIT. El campo TIPO CURSO del registro empleado-curso **referencia este catálogo**. |
| 4 | Cardinalidad / upsert | Un empleado **muchos** cursos. Clave de duplicado/upsert: **cédula + Nº curso**. |
| 5 | ESTADO | Opciones: vacío (`""`) + `SOLICITADO` + `ACTUALIZADO`. Sin flujo obligatorio entre estados. |
| 6 | Nombre | Precargar **NOMBRE COMPLETO** desde Ficha empleados por cédula. En **import**: nombre **siempre** desde Ficha (Excel ignorado); cédula **debe** existir en Ficha. |
| 7 | Excel | Plantilla vacía + carga masiva **y** export del listado. |
| 8 | Vigencia | Calculada en lectura; **no editable**; `ACTUALIZAR` (rojo) / `VIGENTE` (verde). Fórmula: ACTUALIZAR si `fecha_expedicion < (HOY + 30 días) − 365 días`; si no → VIGENTE. |
| 9 | Documento del curso | **Un archivo por registro** (cargar / reemplazar / descargar). Subida en tablero Cursos con `cursos.edit`; descarga con `cursos.view` o desde Ficha (ver abajo). |
| 10 | Ficha empleados | Botón para **consultar** los cursos del empleado (por cédula del perfil) y **descargar** el documento si ya está cargado. |

## Decisiones tecnicas del Arquitecto

| Tema | Decision | Justificacion |
| --- | --- | --- |
| Modulo | Area GH, board key `cursos`, label **Cursos**; tabs `registros` → **Cursos** y `catalogo` → **Catálogo** | Mismo patrón Ficha (Empleados \| Catálogos) / Desvinculaciones (Masivos \| Seguimientos). |
| FK TIPO CURSO | Columna `curso_tipo_id` (FK → `curso_tipos.id`). UI e import resuelven por el texto **TIPO CURSO** del catálogo (match exacto trim, case-insensitive). | Evita drift de texto libre; permite enriquecer con cargo/formato/cursos/cargo_acredit sin denormalizar. Snapshot opcional: no; leer siempre del catálogo vía relación (si se renombra el tipo, listado refleja el valor actual). |
| Tablas | `curso_tipos` (catálogo) + `employee_cursos` (registros) | snake_case; prefijo `employee_` alineado a ficha/desvinculaciones para la entidad operativa. |
| Unicidad | Unique compuesto `(document_number, numero_curso)` | Upsert import y anti-duplicado UI. |
| Vigencia | **No persistir** columna `vigencia`; accessor / servicio de lectura + UI | Evita jobs y drift; recalcula siempre con `Carbon::today()`. Umbral = `today()->addDays(30)->subDays(365)` ≡ `today()->subDays(335)`. Comparación **estricta** `<`. |
| Fecha expedición | **Obligatoria** en create/update/import | Sin fecha no hay vigencia coherente; fila inválida → rechazo con mensaje. |
| Cédula | **Libre** (no exige existir en Ficha). Lookup best-effort a `employee_ficha_profiles.document_number` (cualquier estado empleo). | Usuario: precargar si existe; si no, nombre manual. |
| ESTADO vacío | `null` en BD; UI muestra opción vacía; default al crear/importar sin valor = vacío. Se puede volver a vacío. Independiente de vigencia. | Decision usuario + semántica libre. |
| Renovación | **Misma fila** (editar fecha / Nº curso) o **nueva fila** si cambia Nº curso; no historial archivado en V1. | Upsert por cédula+Nº; cambio de Nº = otra fila. |
| Eliminar | Soft-delete **no**; `DELETE` físico con `cursos.edit`. Al borrar registro, borrar archivo en disco si existe. | CRUD completo pedido; sin histórico V1. |
| Documento | Columnas en `employee_cursos`: `document_path`, `document_original_name`, `document_mime`, `document_size_bytes` (nullable). Disco `local`, dir `employee-cursos`. **1 archivo** por fila; reemplazo borra el anterior. | Patrón adjuntos compra / foto ítem. |
| Import Excel | **No** incluye binarios del certificado; el archivo se sube después en UI (o endpoint dedicado). | Excel no transporta PDF de forma fiable. |
| Ficha bridge | Desde ficha del empleado: botón → modal/listado de `employee_cursos` donde `document_number` = cédula del perfil; descarga si hay documento. | Pedido usuario 2026-09-15. |
| Import | Alta + actualización por clave `(cedula, numero_curso)`; **no** borra filas ausentes del archivo; reporte por fila `ok[]` / `failed[]`. | Patrón ficha / purchase-requests. |
| Plantilla | Fila 1 claves, fila 2 labels, datos desde fila 3. Clase dedicada tipo `CursosImportTemplateExport`. | `EmployeeFichaImportTemplateExport` / `PurchaseRequestItemsImportTemplateExport`. |
| Export listado | `App\Exports\BaseExport` + `<x-export-excel>`; incluir columna VIGENCIA calculada y filtros activos. | Estándar proyecto; no `excelHtml5`. |
| Selectores | `<x-searchable-select>` para TIPO CURSO y ESTADO (y filtros). **Prohibido Select2.** | Regla AGENTS.md. |
| UI registros | Listado (DataTables o tabla filtrable) + **formulario/modal** create/edit (no grilla Excel inline). | Suficiente para CRUD; alineado a Ficha/Archivo. |
| Catálogo UI | Pestaña **Catálogo**: CRUD de filas `curso_tipos` (misma autorización `cursos.edit`). | Usuario pidió catálogo administrable. |
| Colores vigencia | Badge/texto semántico rojo (`ACTUALIZAR`) / verde (`VIGENTE`); tokens CSS en `app.css` si hace falta (clases reutilizables, no hardcode suelto). | UX pedida; no colores legacy de Excel. |
| Access | `CursosAccessService` (board / view / edit + bypass `manage.users`). | Patrón Desvinculaciones / Ficha. |
| Audit | Wrapper `CursosAuditLogService` (`module=cursos`, `area=gestion_humana`) + `config/audit.php`. | SystemAuditService. |
| Rutas | Grupo nuevo en `routes/areas/gestion_humana.php`. | Ownership área GH. |
| Nav | Extender `NavigationResolver`, `SidebarVisibilityService`, `User::defaultCursosBoardUrl()`. | Igual que boards GH existentes. |
| Seed | `super-admin` recibe todos vía catálogo; rol `administrador` **no** recibe el paquete por defecto (asignación Manual en Admin). | Alineado a Desvinculaciones. |
| Migrate | Solo `php artisan migrate` incremental. **Prohibido** `migrate:fresh` / wipe. | Proteccion de datos. |
| Repository | **No** introducir. | Convención proyecto. |

## Alcance

### Incluye

- Tablero sidebar **Cursos** en Gestion humana (hogar `gestion_humana`), pestañas **Cursos** \| **Catálogo**.
- Permisos nuevos + Admin UI (`admin_permission_groups`) + sync `PermissionCatalog` / `app:sync-permissions`.
- Listado de registros con filtros (cédula, nombre, tipo, vigencia, estado; filtro rápido “solo ACTUALIZAR”).
- CRUD registros: crear / editar / eliminar.
- **Documento del curso:** cargar / reemplazar / descargar (1 archivo por registro).
- Lookup cédula → nombre desde Ficha (si hay perfil); nombre editable si no hay match o para override operativo.
- Catálogo administrable (`curso_tipos`) con las 5 columnas pedidas.
- Vigencia calculada en lectura (rojo/verde).
- Plantilla vacía Excel + import masivo (upsert) + reporte por fila (**sin** archivo binario en el Excel).
- Export Excel del listado (`BaseExport` + `<x-export-excel>`).
- **Ficha empleados:** botón para consultar cursos del empleado y descargar documento si existe.
- Auditoría de create/update/delete/import/document upload (y cambios de catálogo).
- Tests PHPUnit (permisos, vigencia, upsert, lookup, CRUD, catálogo, import/export auth, documento, bridge Ficha).
- Docs: `docs/modules/cursos.md` + `docs/user/cursos.md` (+ INDEX / ACCESS_CONTROL / ARCHITECTURE ownership; nota en ficha-empleados). Documentador al cierre.

### Fuera de alcance

- Notificaciones / correo al pasar a ACTUALIZAR.
- Historial de renovaciones (versiones archivadas).
- Integración con proveedores de cursos o KPIs/indicadores/comercial.
- Soft-delete o “ocultar” registros.
- Exigir que la cédula exista en Ficha.
- Permiso separado `cursos.import` (queda bajo `cursos.edit`).
- Job nocturno que materialice vigencia en BD.
- Select2 / `excelHtml5` / Repository.
- Subir el documento **desde** la ficha (solo consulta + descarga en Ficha V1).
- Múltiples archivos por un mismo curso (solo 1).
- Incluir el PDF/imagen del curso dentro del Excel de import.

## Reglas de negocio

### Tablero y acceso

1. Etiquetas exactas: tablero **Cursos**; pestañas **Cursos** (registros) y **Catálogo**.
2. Sidebar: `view.board.gestion_humana.cursos` (hogar `gestion_humana`). Bypass: `manage.users`.
3. Entrar / listar / exportar: `cursos.view` (o bypass).
4. Crear / editar / eliminar registros, import, plantilla, CRUD catálogo: `cursos.edit` (o bypass).
5. Usuario solo con `cursos.view` (+ board): consulta y export; sin botones mutación ni import.
6. Catálogo visible en subnav solo si `cursos.edit` (lectura de tipos para selects del listado se resuelve vía API/opciones embebidas con `cursos.view` o `edit`).

### Registros (empleado-curso)

7. Columnas UI: CEDULA | NOMBRE COMPLETO | TIPO CURSO | FECHA EXPEDICION | No.CURSO | VIGENCIA (RO) | ESTADO | OBSERVACIONES | **DOCUMENTO** (indicador / acciones subir-descargar).
8. Un empleado (misma cédula) puede tener **varios** cursos (varias filas).
9. Unicidad: no dos filas con misma `(cedula, numero_curso)`.
10. TIPO CURSO obligatorio y debe existir en catálogo (`curso_tipo_id`).
11. FECHA EXPEDICION obligatoria (date).
12. No.CURSO obligatorio (string; normalizar trim; no vacío).
13. ESTADO: `null` / `SOLICITADO` / `ACTUALIZADO`; sin secuencia obligatoria; independiente de VIGENCIA.
14. OBSERVACIONES: texto libre nullable.
15. NOMBRE COMPLETO: al ingresar/cambiar cédula (blur/Enter o import), si existe `EmployeeFichaProfile` con ese `document_number` → precargar `full_name`; si no, conservar/pedir nombre manual. Nombre **editable** siempre en V1 (snapshot en `employee_cursos.full_name`, no FK obligatoria a ficha).
16. Eliminar: confirmación UI; borra la fila **y** el archivo en disco si existe.
17. Renovar curso: editar la misma fila (nueva fecha y/o datos) **o** crear otra fila si el Nº curso cambia.
17b. **Documento:** opcional; tipos permitidos PDF/JPG/PNG/WEBP (máx. 10 MB, alineado a adjuntos compra salvo Word); subir/reemplazar con `cursos.edit`; descargar con `cursos.view` (o bypass). Un solo archivo por registro.

### Ficha empleados (consulta)

17c. En la ficha del empleado (vista editar/detalle donde aplique), botón **Cursos** (o equivalente) visible con `ficha_empleados.view`.
17d. Abre modal/panel con listado de cursos de esa cédula: tipo, fecha, Nº, vigencia, estado, y acción **Descargar** solo si hay documento.
17e. Desde Ficha **no** se crea/edita/sube en V1 (solo consulta + descarga). Si el usuario no tiene `cursos.view` pero sí ficha view: puede **consultar y descargar** los cursos de ese empleado (puente de lectura autorizado por ficha).

### Vigencia (calculada)

18. `umbral = (fecha_de_hoy + 30 días) − 365 días` (= hoy − 335 días), timezone app.
19. Si `fecha_expedicion < umbral` → **ACTUALIZAR** (estilo rojo).
20. En caso contrario (incluye igualdad `fecha_expedicion == umbral`) → **VIGENTE** (estilo verde).
21. No se guarda en BD; no es editable; aparece en listado, detalle, export e import-reporte no la escribe como columna de entrada.

### Catálogo

22. Columnas: `tipo_curso`, `cargo_curso`, `formato_para_cursos`, `cursos`, `cargo_acredit` (labels UI en mayúsculas operativas).
23. `tipo_curso` obligatorio y **único** (case-insensitive) para match de import/UI.
24. Resto de columnas: string nullable (o required según Feature al validar Excel legacy; default nullable en V1).
25. No eliminar un tipo referenciado por `employee_cursos` (bloquear delete con mensaje); opcional V1: permitir solo si `count` hijos = 0.
26. Editar `tipo_curso` actualiza el label visible en registros vía FK (sin denormalizar).

### Import / plantilla / export

27. Plantilla vacía: claves fila 1, labels fila 2, sin datos (o 0 filas de ejemplo).
28. Claves: `cedula`, `nombre_completo` (ignorado; nombre desde Ficha), `tipo_curso`, `fecha_expedicion`, `numero_curso_anterior` (opcional, renovacion), `numero_curso`, `estado`, `observaciones`.
29. Import: por cada fila de datos (desde fila 3) validar; continuar ante fallos; respuesta con resumen + detalle; **no** eliminar registros no presentes en el archivo.
30. Match:
    - Sin `numero_curso_anterior`: upsert por `cedula` + `numero_curso` → update o insert.
    - Con `numero_curso_anterior`: buscar `cedula` + anterior; si no hay match → **fail** (no crear); si hay match → update in-place (puede cambiar `numero_curso`, fecha, tipo). Si el nuevo numero choca con otra fila de la misma cedula → fail.
31. `tipo_curso` en Excel debe resolver a un `curso_tipos` existente; si no → fail fila.
32. `estado` vacío o ausente → null; valores inválidos → fail fila.
33. Cedula debe existir en Ficha; **nombre siempre desde Ficha** (Excel ignorado).
34. Export listado: columnas visibles + VIGENCIA calculada; respetar filtros; **sin** columna `numero_curso_anterior`. `BaseExport`.

### Auditoria

35. Eventos sugeridos: `employee_curso` create/update/delete; `curso_tipo` create/update/delete; `import` (action `process`, metadata: totales ok/fail, sin volcar filas completas si son muchas).
36. Sin correo.

## Permisos (`config/access.php`)

### Keys concretas

| Permiso | Rol(es) | Descripcion |
| --- | --- | --- |
| `view.board.gestion_humana.cursos` | `super-admin` (todos); asignar en Admin | Ver tablero **Cursos** en sidebar GH |
| `cursos.view` | Paquete consulta / completo | Ver listado, filtros, export Excel |
| `cursos.edit` | Paquete completo | CRUD registros + plantilla + import + CRUD catálogo |

**Paquetes recomendados (documentar en Admin / doc usuario):**

| Perfil | Permisos |
| --- | --- |
| Solo consulta | board + `cursos.view` |
| Operativo GH | board + `cursos.view` + `cursos.edit` |

No crear roles nuevos. Bypass runtime: `manage.users` en `CursosAccessService`.

### Cambios en `config/access.php`

- `system_permissions`: agregar `cursos.view`, `cursos.edit`.
- `boards`: `'cursos' => 'Cursos'`.
- `board_canonical_areas`: `cursos` → `home => gestion_humana`, `base_area_tab => false`.
- `cursos_tabs`: `registros => Cursos`, `catalogo => Catálogo`.
- `admin_permission_groups` → `other_areas.gestion_humana`:
  - boards: agregar `view.board.gestion_humana.cursos`
  - subgroup `cursos`: `cursos.view`, `cursos.edit`
- Generación `view.board.*` vía `PermissionCatalog` (verificar board solo en GH).

### Seeders

- `RoleAndPermissionSeeder` / `app:sync-permissions`: crea permisos; `super-admin` recibe todos.
- Rol `administrador`: **no** incluir paquete Cursos por defecto (asignación manual).
- Tras deploy: `php artisan app:sync-permissions` + re-login.

## Rutas

Archivo: `routes/areas/gestion_humana.php` (grupo nuevo; **no** editar `routes/web.php` salvo require ya existente).

Prefijo: `/gestion-humana/cursos` · nombre `gestion-humana.cursos.`

| Metodo | URI | Nombre | Permiso / notas |
| --- | --- | --- | --- |
| GET | `/` | `index` | Redirect a registros. `cursos.view` |
| GET | `/registros` | `registros` | Vista listado. `cursos.view` |
| GET | `/registros/datatable` | `registros.datatable` | JSON filtrado + vigencia. `cursos.view` |
| GET | `/registros/lookup` | `registros.lookup` | Query `cedula` → nombre si perfil ficha. `cursos.edit` (o view+edit según Feature; preferir edit) |
| POST | `/registros` | `registros.store` | Crear. `cursos.edit` |
| GET | `/registros/{employeeCurso}` | `registros.show` | Opcional JSON/detalle. `cursos.view` |
| PATCH | `/registros/{employeeCurso}` | `registros.update` | Editar. `cursos.edit` |
| DELETE | `/registros/{employeeCurso}` | `registros.destroy` | Eliminar. `cursos.edit` |
| GET | `/registros/exportar` | `registros.export` | Excel listado. `cursos.view` |
| GET | `/registros/plantilla-importacion` | `registros.import-template` | Plantilla vacía. `cursos.edit` |
| POST | `/registros/importar` | `registros.import` | Carga masiva. `cursos.edit` |
| GET | `/registros/importar/reporte/{token}` | `registros.import-report` | Reporte fallos (si aplica patrón ficha). `cursos.edit` |
| GET | `/registros/{employeeCurso}/documento` | `registros.document.download` | Descargar. `cursos.view` |
| POST | `/registros/{employeeCurso}/documento` | `registros.document.upload` | Subir/reemplazar. `cursos.edit` |
| DELETE | `/registros/{employeeCurso}/documento` | `registros.document.destroy` | Quitar archivo. `cursos.edit` |
| GET | `/catalogo` | `catalogo` | Vista catálogo. `cursos.edit` |
| GET | `/catalogo/datatable` | `catalogo.datatable` | JSON. `cursos.edit` |
| POST | `/catalogo` | `catalogo.store` | Crear tipo. `cursos.edit` |
| PATCH | `/catalogo/{cursoTipo}` | `catalogo.update` | Editar tipo. `cursos.edit` |
| DELETE | `/catalogo/{cursoTipo}` | `catalogo.destroy` | Eliminar si sin hijos. `cursos.edit` |
| GET | `/catalogo/opciones` | `catalogo.options` | JSON para searchable-select (id + label). `cursos.view` |

Middleware grupo: `auth`, `active`, `password.changed` (igual resto GH).

**Ficha empleados** (rutas en el mismo archivo GH o grupo ficha existente):

| Metodo | URI (bajo ficha empleados) | Nombre sugerido | Permiso |
| --- | --- | --- | --- |
| GET | `.../empleados/{entry}/cursos` o `.../ficha/{profile}/cursos` | `gestion-humana.ficha-empleados.employees.cursos` | `ficha_empleados.view` — JSON/HTML modal: cursos de la cédula del perfil |
| GET | `.../empleados/cursos/{employeeCurso}/documento` | `gestion-humana.ficha-empleados.employees.cursos.document` | `ficha_empleados.view` — descarga si el curso pertenece a la cédula del empleado en contexto |

Autorizar siempre que `employee_curso.document_number` coincida con la cédula del perfil/entry abierto (evitar IDOR).

## Base de datos

Solo migración(es) **nuevas**. Prohibido `migrate:fresh`.

### Tabla `curso_tipos` (catálogo)

| Columna | Tipo | Notas |
| --- | --- | --- |
| `id` | bigint PK | |
| `tipo_curso` | string(150) | TIPO CURSO; unique (normalizar comparación CI en app) |
| `cargo_curso` | string(150) nullable | CARGO CURSO |
| `formato_para_cursos` | string(150) nullable | FORMATO PARA CURSOS |
| `cursos` | string(255) nullable | CURSOS (texto descriptivo del catálogo) |
| `cargo_acredit` | string(150) nullable | CARGO ACREDIT |
| `is_active` | boolean default true | Opcional V1 para ocultar en selects sin borrar |
| `created_by` | FK users nullable | `nullOnDelete` |
| `created_at` / `updated_at` | timestamps | |

Indices: unique `tipo_curso`; index `is_active`.

### Tabla `employee_cursos` (registros)

| Columna | Tipo | Notas |
| --- | --- | --- |
| `id` | bigint PK | |
| `document_number` | string(50) | CEDULA |
| `full_name` | string(255) | NOMBRE COMPLETO (snapshot editable) |
| `curso_tipo_id` | FK → `curso_tipos.id` | TIPO CURSO; `restrictOnDelete` |
| `fecha_expedicion` | date | FECHA EXPEDICION |
| `numero_curso` | string(100) | No.CURSO |
| `estado` | string(20) nullable | null \| `SOLICITADO` \| `ACTUALIZADO` |
| `observaciones` | text nullable | |
| `document_path` | string(500) nullable | Ruta disco local |
| `document_original_name` | string(255) nullable | Nombre original |
| `document_mime` | string(127) nullable | |
| `document_size_bytes` | unsignedInteger nullable | |
| `employee_ficha_profile_id` | FK → `employee_ficha_profiles.id` nullable | Opcional: set si lookup encontró perfil; `nullOnDelete` |
| `created_by` / `updated_by` | FK users nullable | `nullOnDelete` |
| `created_at` / `updated_at` | timestamps | |

Indices: **unique** `(document_number, numero_curso)`; index `(fecha_expedicion)`; index `(estado)`; index `(curso_tipo_id)`.

**No** columna `vigencia` en BD.

## Capas a implementar

- [ ] Migración(es) — `curso_tipos`, `employee_cursos`
- [ ] Modelo(s) — `CursoTipo`, `EmployeeCurso` (+ factories); relación/opcional en `EmployeeFichaProfile`
- [ ] Access — `CursosAccessService` + trait tabs si aplica (`HasCursosTabs`)
- [ ] Audit — `CursosAuditLogService` + `config/audit.php` módulo `cursos`
- [ ] Servicios — vigencia; import; **document storage**; lookup ficha; datatable; **listado cursos por cédula para Ficha**
- [ ] Controlador(es) — `CursosController` / `CursosCatalogController` + endpoints documento; hook en `FichaEmpleadosController` (list/download)
- [ ] Form Request(s) — store/update registro, catálogo, import, **upload documento**
- [ ] Export — `CursosExport` + `CursosImportTemplateExport`
- [ ] Config — `config/cursos.php` (import columns, mimes/max KB documento)
- [ ] Vista(s) Blade — shell + registros + catálogo + **UI documento**; partial/modal en **ficha empleados**
- [ ] JavaScript — lookup, import UX, upload documento, modal ficha
- [ ] Nav — NavigationResolver, SidebarVisibilityService, User
- [ ] Shared — access, audit, rutas GH, **vistas/controller Ficha**, posible app.css
- [ ] Tests PHPUnit
- [ ] Docs técnica/usuario (+ nota ficha)

## Componentes reutilizables

- `<x-searchable-select>` — TIPO CURSO, ESTADO, filtros.
- `<x-export-excel>` + `App\Exports\BaseExport`.
- Patrón plantilla import: `EmployeeFichaImportTemplateExport` / `PurchaseRequestItemsImportTemplateExport` (fila 1 keys, fila 2 labels).
- Patrón reporte import token (Ficha) si el volumen de errores lo justifica.
- Estilo nav pills / `module-tab` / `module-subnav` (branding GH).
- Wrapper audit tipo `DesvinculacionesAuditLogService` / `EmployeeFichaAuditLogService`.
- Lookup lectura: `EmployeeFichaProfile::where('document_number', …)`.
- **No** Select2. **No** Repository. **No** `excelHtml5`.

## Documentacion a actualizar

- [ ] `docs/modules/cursos.md` (**crear** — Documentador)
- [ ] `docs/user/cursos.md` (**crear** — Documentador; Objetivo, Alcance, Definiciones, Responsabilidades, Desarrollo, Control de cambios)
- [ ] `docs/ACCESS_CONTROL.md` — board + permisos
- [ ] `docs/ARCHITECTURE.md` — fila ownership cursos GH
- [ ] `docs/INDEX.md` — enlace módulo
- [ ] `README.md` — solo si lista módulos GH explícitamente

## Archivos compartidos (`shared-files`)

| Archivo | Motivo |
| --- | --- |
| `config/access.php` | Board, tabs, system_permissions, admin groups |
| `config/audit.php` | Módulo `cursos` |
| `routes/areas/gestion_humana.php` | Rutas del tablero |
| `app/Services/Navigation/NavigationResolver.php` | Link sidebar |
| `app/Services/Navigation/SidebarVisibilityService.php` | Visibilidad board |
| `app/Models/User.php` | `defaultCursosBoardUrl()` |
| `database/seeders/RoleAndPermissionSeeder.php` | Sync catálogo (indirecto) |
| `resources/css/app.css` | Posible: badges vigencia rojo/verde |
| Vistas / controller Ficha empleados | Botón + modal consulta cursos + descarga |

Flag `shared-files: true` en `docs/TASKS.md` / Task Cards. Un solo agente a la vez sobre estos archivos.

## Criterios de aceptacion

1. Usuario con board + `cursos.view` ve **Cursos** en sidebar GH y el listado; sin board no lo ve (salvo `manage.users`).
2. Solo `cursos.view`: puede filtrar y exportar; **403** en create/update/delete/import/plantilla/catálogo mutación.
3. Con `cursos.edit`: CRUD registros y catálogo; descarga plantilla e importa.
4. Pestañas **Cursos** y **Catálogo** con estilo `module-tab`; Catálogo exige edit.
5. Crear registro con cédula existente en Ficha precarga nombre; cédula desconocida permite nombre manual.
6. No se pueden guardar dos registros con misma cédula + Nº curso (validación 422).
7. TIPO CURSO solo valores del catálogo (`searchable-select`); import con tipo inexistente falla esa fila.
8. VIGENCIA: con `fecha_expedicion` anterior al umbral (hoy−335) muestra ACTUALIZAR rojo; en el borde `== umbral` y posteriores → VIGENTE verde; campo no editable.
9. ESTADO acepta vacío, SOLICITADO, ACTUALIZADO sin secuencia.
10. Eliminar registro quita la fila **y** el archivo asociado.
11. Import upsert: misma cédula+Nº actualiza; nueva combina inserta; errores parciales no abortan el lote; no borra ausentes; **no** sube PDF por Excel.
12. Plantilla vacía descargable con keys/labels en filas 1–2.
13. Export Excel del listado incluye VIGENCIA calculada y respeta filtros.
14. No se puede borrar un `curso_tipo` referenciado.
15. Con `cursos.edit` se puede subir/reemplazar/quitar documento; con `cursos.view` descargar desde el tablero.
16. Desde Ficha empleados (`ficha_empleados.view`): botón abre listado de cursos de esa cédula; Descargar solo si hay archivo; sin IDOR a otra cédula.
17. Eventos de auditoría visibles para CRUD, import y documento.
18. Tests mínimos en verde (tabla abajo).
19. Sin `migrate:fresh`; migraciones aditivas aplicadas con `migrate`.

## Tests minimos

| Area | Caso |
| --- | --- |
| Permisos | Sin `cursos.view` → 403 listado; sin `cursos.edit` → 403 store/import/catalogo/upload |
| Bypass | `manage.users` accede y muta |
| Vigencia | Fecha vieja → ACTUALIZAR; fecha reciente / igual umbral → VIGENTE |
| Unicidad | Duplicado cédula+Nº → 422 |
| Lookup | Perfil ficha → nombre; sin perfil → null/empty |
| CRUD | store/update/destroy OK; destroy borra archivo |
| Documento | upload + download + replace; mime inválido 422 |
| Catálogo | store; destroy bloqueado si tiene hijos |
| Import | 1 insert + 1 update + 1 fail tipo → reporte coherente |
| Export | Usuario view descarga 200 xlsx |
| Ficha bridge | Con ficha.view lista cursos del empleado; download OK; curso de otra cédula 403/404 |
| Board | Sin `view.board…cursos` no aparece en sidebar (test nav si existe patrón) |

Correr: `php artisan test --compact` filtrando tests de cursos.

## Validacion local

1. `php artisan migrate` (sin fresh).
2. `php artisan app:sync-permissions` + asignar board + view/edit a usuario GH de prueba.
3. Crear 2–3 tipos en Catálogo.
4. Alta manual + lookup cédula real de ficha + cédula inventada.
5. Verificar semáforo vigencia con fechas controladas.
6. Plantilla → llenar → import (éxito + error de tipo) → export listado.
7. `php artisan test --compact` (suite afectada).
8. `vendor/bin/pint --dirty` tras PHP.

## Riesgos y dependencias

| Riesgo | Mitigacion |
| --- | --- |
| Catálogo vacío al go-live | Seed vacío OK; documentar carga inicial de tipos antes del import masivo |
| Match `tipo_curso` CI vs unique MySQL | Unique collation + normalización trim/upper en validación |
| Nombres divergentes Ficha vs Excel | Regla explícita: import prioriza Excel; UI precarga pero editable |
| Shared-files nav/access | Coordinar Task Cards; no editar en paralelo |
| Umbral “hoy” en tests | Congelar Carbon / viajar en el tiempo en PHPUnit |
| Import grande / timeout Hostinger | Documentar límite práctico; reporte parcial; cola fuera de V1 si QA falla |
| FK `employee_ficha_profile_id` huérfana | `nullOnDelete`; registro de curso permanece |

## Dependencias de codigo existente

- `EmployeeFichaProfile` (`document_number`, `full_name`) — solo lectura
- `BaseExport`, `<x-export-excel>`
- Patrón import template GH / compras
- `NavigationResolver` / `SidebarVisibilityService` / boards GH
- `SystemAuditService` + wrappers de módulo
- Docs patrón: `docs/modules/desvinculaciones.md`, `docs/modules/ficha-empleados.md`

## Slice sugerido para AgentSj (plan)

| Task | Contenido | shared-files |
| --- | --- | --- |
| T1 | Permisos, audit, migraciones (incl. columnas documento), modelos, AccessService, nav, shell tabs, catálogo CRUD | Sí |
| T2 | Listado registros + vigencia + lookup + CRUD UI + **documento upload/download** + export | CSS vigencia; rutas GH |
| T3 | Plantilla + import upsert + tests import | Rutas GH |
| T4 | Botón + modal consulta/descarga cursos en **Ficha empleados** | Ficha controller/vistas |

Documentador tras Revisor.

## Aprobacion

- [x] Analista — vacíos cerrados (decisiones usuario 2026-09-15 + documento/ficha 2026-09-15)
- [x] Arquitecto — brief actualizado (`docs/briefs/FEAT-032.md`)
- [ ] Usuario — confirmacion del brief/plan actualizado (AgentSj solicita «apruebo» antes de implementar)
