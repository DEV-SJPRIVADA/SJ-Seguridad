# Modulo Archivo (Gestion humana)

## Objetivo

Gestionar la **ubicacion documental fisica** de empleados ya en ficha: campos **estantes** y **cajas** en `employee_ficha_profiles`, operados por el perfil encargado de archivo.

## Alcance V1

- Tablero **Archivo** en sidebar de Gestion Humana (`view.board.gestion_humana.archivo`).
- Listado de empleados **en ficha** con columnas estantes/cajas.
- Edicion individual de ubicacion (`archivo.manage`).
- Export Excel desde **Ficha empleados → Empleados** con el mismo formato que «Exportar datos para actualizar» **mas** columnas `estantes` y `cajas`.
- **Fuera de alcance:** import masivo de empleados (nomina) no lee ni escribe estos campos.

## Importacion masiva de archivo

- Servicio: `EmployeeArchiveImportService`
- Formato: mismo Excel que export **Exportar archivo** (fila 1 claves, fila 2 etiquetas, datos desde fila 3)
- Por fila: identifica empleado por **cedula** (debe estar **en ficha**)
- Solo actualiza `archive_shelf` / `archive_box`; demas columnas se ignoran
- Filas sin cedula: ignoradas; sin estantes ni cajas: omitidas
- Reporte de filas fallidas: mismo mecanismo que otros imports (`employee_archive`)

## Modelo de datos

Columnas nuevas en `employee_ficha_profiles`:

| Columna | Tipo | Notas |
| --- | --- | --- |
| `archive_shelf` | `string(100)` nullable | Estante fisica |
| `archive_box` | `string(100)` nullable | Caja / contenedor |

Migracion: `2026_08_06_120740_add_archive_fields_to_employee_ficha_profiles_table.php`.

## Permisos (`config/access.php`)

| Permiso | Descripcion |
| --- | --- |
| `view.board.gestion_humana.archivo` | Ver tablero Archivo en sidebar GH |
| `archivo.view` | Ver listado de ubicaciones |
| `archivo.manage` | Editar estantes y cajas |

Servicio: `App\Services\Access\ArchivoAccessService`.

Export archivo en Ficha empleados: usuarios con `archivo.view` **o** permisos de ver Ficha empleados (`ficha_empleados.view` / `manage`).

## Pestañas Archivo

| Pestaña | Ruta | Descripcion |
| --- | --- | --- |
| Historias Laborales | `/gestion-humana/archivo/historias-laborales` | Listado de empleados en ficha, consulta multiple, import/export |
| Historial de consultas | `/gestion-humana/archivo/historial-consultas` | Registro detallado por cedula de cada consulta |

`/gestion-humana/archivo` redirige a **Historias Laborales**.

Subnav: `resources/views/areas/gestion_humana/archivo/partials/subnav.blade.php` (trait `HasArchivoTabs`).

## Consulta multiple

- Modal **Consulta multiple** en pestaña Historias Laborales (`archivo.view` o `archivo.manage`).
- Campos: cedulas (textarea), motivos (checkboxes), **Entregada a** (texto).
- Al consultar: registra cabecera en `employee_archive_consultations` y **un registro por cedula** en `employee_archive_consultation_items`.
- Filtra Historias Laborales por consulta activa (`?consultation={id}`).

Modelos: `EmployeeArchiveConsultation`, `EmployeeArchiveConsultationItem`.

Servicio parser: `App\Services\GestionHumana\EmployeeArchiveConsultationParser`.

Migraciones:
- `2026_08_06_151631_create_employee_archive_consultations_table.php`
- `2026_08_06_154044_add_delivered_to_employee_archive_consultations_table.php`
- `2026_08_06_154045_create_employee_archive_consultation_items_table.php`

## Historial de consultas

Columnas por fila (item):

| Columna | Origen |
| --- | --- |
| Fecha | `created_at` del item |
| Concepto | Motivos seleccionados (texto) |
| Cedula / Nombre / Estante / Caja | Snapshot al registrar |
| Entregada a | Campo del modal |
| Recibida | Checkbox editable (`received`) |
| Observacion | Texto editable |
| Semana | Numero de semana del mes (`week_of_month`) |
| Mes | Nombre del mes (`month_label`) |

Filtros: busqueda, mes, semana. PATCH por fila para `received` y `observation`.

## Rutas

`routes/areas/gestion_humana.php`:

| Metodo | URI | Nombre | Permiso |
| --- | --- | --- | --- |
| GET | `/gestion-humana/archivo` | `gestion-humana.archivo.index` | `archivo.view` (redirect) |
| GET | `/gestion-humana/archivo/historias-laborales` | `gestion-humana.archivo.labor-histories.index` | `archivo.view` |
| GET | `/gestion-humana/archivo/historias-laborales/datatable` | `gestion-humana.archivo.labor-histories.datatable` | `archivo.view` |
| GET | `/gestion-humana/archivo/historial-consultas` | `gestion-humana.archivo.consultation-history.index` | `archivo.view` |
| POST | `/gestion-humana/archivo/consultar` | `gestion-humana.archivo.consult` | `archivo.view` |
| PATCH | `/gestion-humana/archivo/historial-consultas/{item}` | `gestion-humana.archivo.consultation-history.update` | `archivo.view` |
| PATCH | `/gestion-humana/archivo/{fichaEntry}` | `gestion-humana.archivo.update` | `archivo.manage` |
| POST | `/gestion-humana/archivo/importar` | `gestion-humana.archivo.import` | `archivo.manage` |
| GET | `/gestion-humana/archivo/importar/reporte/{token}` | `gestion-humana.archivo.import-report` | `archivo.manage` |
| GET | `/gestion-humana/ficha-empleados/empleados/exportar-archivo` | `gestion-humana.ficha-empleados.employees.export-archive-template` | export archive (ver arriba) |

## Export Excel archivo

- Clase: `App\Exports\EmployeeFichaArchiveTemplateExport`
- Mapper: `EmployeeFichaImportRowMapper::mapRowWithArchive()`
- Columnas: `config('employee_ficha.import_columns')` + `config('employee_ficha.archive_export_extra_columns')` (`estantes`, `cajas`)
- Mismos filtros que export masivos: solo **En ficha**; sin rango de fechas solo **activos**; con `fecha_desde`/`fecha_hasta` filtra por ingreso.

## Controlador

`App\Http\Controllers\GestionHumana\ArchivoController` — `laborHistories()` pinta layout, filtros y tabla vacia. El listado llega por AJAX (`laborHistoriesDatatable()` + `EmployeeArchiveLaborHistoryDatatableService`): paginacion, busqueda y orden en MySQL (mismo patron que Ficha empleados). Edicion inline de `archive_shelf` / `archive_box` via PATCH por fila (HTML de inputs/formulario en el JSON). Si el empleado no tiene perfil persistido, se crea uno minimo al guardar.

## Tests

`tests/Feature/EmployeeArchiveTest.php`

## Referencias

- Modulo relacionado: [`ficha-empleados.md`](ficha-empleados.md)
- Guia usuario: [`../user/archivo.md`](../user/archivo.md)
