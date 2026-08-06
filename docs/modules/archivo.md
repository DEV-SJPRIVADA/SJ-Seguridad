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

## Consulta multiple

- Modal **Consulta multiple** en tablero Archivo (`archivo.view` o `archivo.manage`).
- Izquierda: textarea con varias cedulas (linea, coma o punto y coma).
- Derecha: checkboxes de motivo (`config('employee_ficha.archive_consultation_types')`).
- Al consultar: registra en `employee_archive_consultations` y filtra el listado por esas cedulas (`?consultation={id}`).
- Historial: ultimas 10 consultas en el tablero; banner con detalle de consulta activa.

Modelo: `App\Models\EmployeeArchiveConsultation`.

Servicio parser: `App\Services\GestionHumana\EmployeeArchiveConsultationParser`.

Migracion: `2026_08_06_151631_create_employee_archive_consultations_table.php`.

## Rutas

`routes/areas/gestion_humana.php`:

| Metodo | URI | Nombre | Permiso |
| --- | --- | --- | --- |
| GET | `/gestion-humana/archivo` | `gestion-humana.archivo.index` | `archivo.view` |
| POST | `/gestion-humana/archivo/consultar` | `gestion-humana.archivo.consult` | `archivo.view` |
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

`App\Http\Controllers\GestionHumana\ArchivoController` — listado con edicion inline de `archive_shelf` / `archive_box` (PATCH por fila). Si el empleado no tiene perfil persistido, se crea uno minimo al guardar.

## Tests

`tests/Feature/EmployeeArchiveTest.php`

## Referencias

- Modulo relacionado: [`ficha-empleados.md`](ficha-empleados.md)
- Guia usuario: [`../user/archivo.md`](../user/archivo.md)
