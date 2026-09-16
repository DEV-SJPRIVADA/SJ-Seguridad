# Cursos — Guia de usuario

> Documentacion operativa para usuarios finales. Ubicacion: `docs/user/cursos.md`.

## Objetivo

Llevar el control de cursos de las personas (vigencia, estado de tramite y documento), con carga individual o masiva por Excel, y consulta desde la ficha del empleado.

## Alcance

Aplica al tablero **Cursos** en **Gestion Humana**, con dos pestanas:

- **Cursos** — listado, filtros, alta/edicion/eliminacion, documento, export Excel e import masivo.
- **Catalogo** — tipos de curso usados en el listado y en el Excel.

**En esta version:** el import **no** sube PDFs/imagenes; el documento se carga fila a fila. Desde **Ficha empleados** solo se consultan y descargan cursos (no se editan alla).

## Definiciones

| Termino | Significado |
| --- | --- |
| VIGENCIA | Calculada por el sistema a partir de la fecha de expedicion (VIGENTE / ACTUALIZAR). |
| No.CURSO | Numero actual del curso (unico por cedula). |
| No.CURSO ANTERIOR | Columna opcional del Excel de import para **renovar** un curso cuando cambia el numero (y/o fecha/tipo). |
| ESTADO | Tramite libre: vacio, SOLICITADO o ACTUALIZADO (independiente de la vigencia). |

## Responsabilidades

| Perfil | Puede |
| --- | --- |
| Consulta (`cursos.view` + board) | Ver listado, filtrar, exportar Excel, descargar documentos. |
| Edicion (`cursos.edit`) | Todo lo anterior + CRUD, catalogo, plantilla e import masivo, subir/quitar documento. |
| Ficha empleados (lectura) | Ver cursos del empleado y descargar documento si existe. |

## Desarrollo (uso diario)

### Importar masivos (renovaciones)

1. En **Cursos**, abra el icono de carga / plantilla masivos y descargue la **plantilla vacia**.
2. Complete datos desde la fila 3. La **cedula debe existir en Ficha**; el nombre del Excel se **ignora** (se toma de Ficha).
3. **Alta o correccion del mismo numero:** deje **No.CURSO ANTERIOR** vacio y llene **No.CURSO**.
4. **Renovacion** (cambia No.CURSO, fecha y a veces tipo, p. ej. Fundamentacion → Reentrenamiento):
   - En **No.CURSO ANTERIOR** ponga el numero que hoy tiene el registro vencido.
   - En **No.CURSO** ponga el numero nuevo.
   - El sistema busca cedula + numero anterior y **actualiza esa fila** (no crea otra).
   - Si no encuentra el anterior, la fila **falla** y **no** se crea duplicado.
   - Si el numero nuevo ya lo tiene otro curso de la misma persona, la fila **falla**.
5. Importe el archivo; revise el resumen y, si hay errores, el reporte Excel.

### Exportar

El icono Excel del listado exporta segun los filtros actuales (incluye vigencia). Esa exportacion **no** incluye la columna de renovacion (solo sirve en la plantilla de import).

## Control de cambios

| Ver | Fecha | Cambio |
| --- | --- | --- |
| 1.1 | 2026-09-16 | Plantilla import: No.CURSO ANTERIOR para renovar sin duplicar; cedula/nombre desde Ficha. |
| 1.0 | 2026-09-15 | FEAT-032: tablero Cursos y Catalogo. |
