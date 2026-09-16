# Modulo Cursos

> Documentacion tecnica para IAs y desarrolladores. Ubicacion: `docs/modules/cursos.md`.
> Feature: FEAT-032.

## Objetivo

Tablero de area **Gestion Humana** para controlar cursos por persona (vigencia automatica, estado de tramite, documento adjunto, catalogo de tipos, plantilla/import Excel y consulta desde Ficha empleados).

## Alcance actual

- Pestanas **Cursos** (registros) y **Catalogo** (tipos).
- Permisos: `view.board.gestion_humana.cursos`, `cursos.view`, `cursos.edit` (bypass `manage.users`).
- Unicidad de registro: `(document_number, numero_curso)`.
- Vigencia calculada (no persistida): ventana ~335 dias desde `fecha_expedicion`.
- Documento: 1 archivo por registro (PDF/JPG/PNG/WEBP); no viaja en Excel.
- Bridge Ficha: listar/descargar cursos del empleado (sin mutar desde Ficha).
- Import masivo: ver seccion Import abajo. Export listado respeta filtros (sin columna de renovacion).

## Rutas

Archivo: `routes/areas/gestion_humana.php`  
Prefijo: `/gestion-humana/cursos` · nombre `gestion-humana.cursos.`

| Metodo | URI | Nombre | Notas |
| --- | --- | --- | --- |
| GET | `/` | `index` | Redirect a registros |
| GET | `/registros` | `registros` | Listado + filtros |
| GET | `/registros/exportar` | `registros.export` | Excel filtrado (`BaseExport`) |
| GET | `/registros/plantilla-importacion` | `registros.import-template` | Plantilla vacia. `cursos.edit` |
| POST | `/registros/importar` | `registros.import` | Carga masiva. `cursos.edit` |
| GET | `/registros/importar/reporte/{token}` | `registros.import-report` | Fallos (token ~1 h) |
| CRUD + documento | `/registros/...` | store/update/destroy/upload/download | Segun permiso |
| GET | `/catalogo` | `catalogo` | Tipos de curso |
| Bridge Ficha | rutas ficha `.../cursos` | list/download | Lectura por cedula del perfil |

## Import masivo

Servicio: `App\Services\GestionHumana\EmployeeCursoImportService`  
Columnas: `config/cursos.php` → `import.columns` (fila 1 claves, fila 2 labels, datos desde fila 3).

| Clave | Obligatorio valor | Notas |
| --- | --- | --- |
| `cedula` | Si | Debe existir en `employee_ficha_profiles` |
| `nombre_completo` | Header si; valor ignorado | Nombre siempre desde Ficha |
| `tipo_curso` | Si | Match catalogo case-insensitive |
| `fecha_expedicion` | Si | Fecha valida |
| `numero_curso_anterior` | No (opcional) | Clave de renovacion |
| `numero_curso` | Si | Numero nuevo / vigente |
| `estado` | No | Vacio → `null`; solo `SOLICITADO` / `ACTUALIZADO` |
| `observaciones` | No | |

### Reglas de match

1. **`numero_curso_anterior` vacio** → upsert por `(cedula, numero_curso)`: existe → update; no existe → insert.
2. **`numero_curso_anterior` con valor** → solo renovacion/update:
   - Buscar `(cedula, numero_curso_anterior)`.
   - Si **no** hay match → **falla la fila** (mensaje: no se encontro el curso a renovar; **no crea** registro nuevo).
   - Si hay match → actualiza in-place (tipo, fecha, estado, observaciones, `numero_curso` al nuevo).
   - Si `numero_curso_anterior` === `numero_curso` → update normal del mismo numero.
   - Si el nuevo `numero_curso` ya pertenece a **otra** fila de la misma cedula → **falla** (no duplicar / no pisar).
3. Updates **no** tocan `document_*`.
4. No borra filas ausentes del Excel.
5. Continua ante errores de fila; reporte + token.

## Archivos clave

- Controllers: `CursosController`, `CursosCatalogController`
- Models: `EmployeeCurso`, `CursoTipo`
- Services: `EmployeeCursoImportService`, `EmployeeCursoListService`, `EmployeeCursoDocumentService`, `CursosAccessService`, `CursosAuditLogService`
- Config: `config/cursos.php`, permisos en `config/access.php`, audit en `config/audit.php`
- Vistas: `resources/views/areas/gestion_humana/cursos/`
- Tests: `tests/Feature/GestionHumana/Cursos*.php`

## Control de cambios

| Ver | Fecha | Cambio |
| --- | --- | --- |
| 1.1 | 2026-09-16 | Import: columna `numero_curso_anterior` para renovar (cambia No.CURSO / fecha / tipo sin duplicar). Nombre siempre desde Ficha; cedula obligatoria en Ficha. |
| 1.0 | 2026-09-15 | FEAT-032: tablero, catalogo, documento, import/export, bridge Ficha. |
