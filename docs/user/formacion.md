# Formación — Guia de usuario

> Documentacion operativa para usuarios finales. Ubicacion: `docs/user/formacion.md`.

## Objetivo

Consultar y mantener en la plataforma el registro de formaciones que antes se manejaba en Excel: ver indicadores por año, filtrar el listado, exportar y, cuando corresponda, recargar el conjunto completo de registros desde un archivo.

## Alcance

Aplica al tablero **Formación** en **Gestión Humana**, con pestañas:

- **Dashboard** — dos modos: **Por curso** (indicadores por registro: total, aprobados, reprobados, no realizadas) y **Por persona (ciclo)** (quién completó el set de cursos del mes o del año). Filtros de año, mes, estado y curso.
- **Formaciones** — listado paginado (carga por páginas), filtros (año, mes, categoría, curso, número de ID, nombre, estado), exportar a Excel. Con permiso de edición: descargar plantilla e importar un archivo (**todo** el dataset o **solo un mes**).

**En esta versión:**

- No hay alta, edición ni borrado de filas una a una: la única forma de cambiar los datos es el import (completo o por mes).
- Categoría y nombre de curso se toman tal cual del Excel (no hay catálogos internos).
- Este tablero es **independiente** del tablero **Cursos** (vigencias, Ficha empleados, etc.): son procesos distintos.
- El volumen puede ser muy alto (decenas de miles de filas); el listado se carga por páginas.

## Definiciones

| Término | Significado |
| --- | --- |
| Formación | Registro de una persona en un curso/formación (ID, nombre, fecha de inicio, curso, calificación, categoría). |
| Dashboard | Pantalla de indicadores: modo por curso (registro) o por persona (ciclo del mes/año); filtros de año, mes, estado y curso. |
| Formaciones | Pestaña del listado operativo con filtros y exportación. |
| Ciclo | Conjunto de cursos distintos del mes (o del año si no hay mes). Por persona se toma la mejor nota de cada curso. |
| Plantilla | Archivo Excel vacío con las columnas exactas que debe tener la carga. |
| Importar (todo) | Cargar un archivo que **borra todos** los registros actuales y deja solo los del archivo nuevo. |
| Importar (solo un mes) | Elige año y mes: **borra solo ese mes** e inserta el Excel. Todas las fechas del archivo deben ser de ese mes; si hay otra, se rechaza y se avisa. Ideal para cargar octubre sin tocar septiembre. |
| Número de ID | Identificador de la persona en el Excel de formaciones. |
| Fecha de inicio del curso | Fecha a partir de la cual el sistema calcula el mes y el año del registro. |

## Responsabilidades

| Perfil | Puede |
| --- | --- |
| Consulta (ver tablero + permiso de consulta) | Ver Dashboard y Formaciones; filtrar; exportar a Excel. No descarga plantilla ni importa. |
| Operativo (permiso de edición) | Todo lo anterior + descargar plantilla e importar (todo o un mes, previa confirmación). |
| Administración de usuarios | Asignar el tablero y los permisos de Formación a quienes correspondan (no vienen por defecto al rol usuario/administrador). |

## Desarrollo

### Entrar al tablero

1. En el menú de **Gestión Humana**, abra **Formación**.
2. Por defecto entra al **Dashboard**.
3. Use las pestañas superiores para cambiar entre **Dashboard** y **Formaciones**.

### Usar el Dashboard

1. Elija **año**, y si necesita afinar: **mes**. En modo **Por curso** también puede filtrar por **estado** y **curso**. El listado de cursos se limita al mes elegido (si el mes está vacío, aparecen todos los del año).
2. Alterne entre **Por curso** y **Por persona (ciclo)**.
3. En **Por curso**: revise total, aprobados, reprobados y no realizadas; gráficos por mes, estado y categorías.
4. En **Por persona (ciclo)**: el set son todos los cursos distintos del mes (o del año). Se cuenta la mejor nota por persona/curso. Si aprueba todos → Aprobado; si reprueba alguno → Reprobado; si hizo solo algunos → Incompleto; si no hizo ninguno → No realizado.
5. Use el icono X para limpiar mes/estado/curso (el año se conserva).
6. Clic en cualquier KPI abre **Formaciones** con el filtro equivalente (año/mes y estado, o personas del ciclo).

### Consultar y filtrar formaciones

1. Vaya a la pestaña **Formaciones**.
2. Use los filtros disponibles: año, mes, categoría, curso, número de ID, nombre y estado. Al cambiar año o mes, el desplegable de **curso** se actualiza solo con los cursos de ese periodo (igual que en el Dashboard).
3. Si llegó desde un KPI de ciclo, verá un aviso con el filtro de ciclo activo (puede quitarlo sin perder año/mes).
4. El listado se actualiza por páginas; no se cargan todas las filas de una vez. La columna **Estado** se calcula sola y se muestra con color: calificación mayor a 7.5 → Aprobado (verde); con nota y ≤ 7.5 → Reprobado (rojo); sin calificación → No realizada (gris). Puede filtrar por ese estado.
5. Para descargar lo filtrado a Excel, use el botón de exportar.

### Descargar plantilla e importar (solo edición)

1. En **Formaciones**, abra la opción de carga / plantilla.
2. Elija el **tipo de carga**:
   - **Solo un mes** — indique año y mes (ej. octubre). Se borran solo esos registros y se carga el Excel. Todas las fechas del archivo deben ser de ese mes; si hay otra, se **rechaza** y se muestra el aviso (no se cambia nada).
   - **Reemplazar todo** — borra **todos** los registros y deja solo los del archivo.
3. Descargue la **plantilla** y complete las columnas con los mismos encabezados (número de ID, nombre completo, fecha de inicio del curso, nombre completo del curso, calificación, nombre de la categoría).
4. La fecha puede ir en formato de texto en español (por ejemplo *jueves, 4 de junio de 2026, 00:00*), como fecha Excel o como año-mes-día.
5. Marque la casilla de **confirmación** e importe. Si las columnas no coinciden o hay filas inválidas, **no** se borran datos previos.
6. Si el archivo es muy grande, la operación puede tardar varios minutos; evite usar el listado al mismo tiempo hasta que termine.
7. Tras un import exitoso, el listado y el Dashboard reflejan el nuevo conjunto de datos.

**Importante:** un archivo solo con encabezados o sin filas válidas **no** borra los datos actuales; el sistema rechaza la importación.

## Control de cambios

| Version | Fecha | Autor | Descripcion del cambio |
| --- | --- | --- | --- |
| 1.9 | 2026-10-08 | Agent | Import: opción **solo un mes** (borra ese año/mes; rechaza Excel con filas de otro mes). |
| 1.8 | 2026-10-02 | Agent | Clic en un KPI del Dashboard abre Formaciones ya filtrado (estado o ciclo). |
| 1.7 | 2026-10-02 | Agent | Dashboard: modo Por persona (ciclo). Formaciones: el filtro Curso se limita al año/mes como en el Dashboard. |
| 1.6 | 2026-10-02 | Agent | Dashboard: el filtro Curso lista solo cursos del mes seleccionado (o todos si el mes está vacío). |
| 1.5 | 2026-10-02 | Agent | Dashboard: KPIs por estado, filtros mes/estado/curso y gráfico de estado. |
| 1.4 | 2026-10-02 | Agent | Estado con colores (verde Aprobado, rojo Reprobado, gris No realizada) y filtro por estado en Formaciones. |
| 1.3 | 2026-10-02 | Agent | Listado Formaciones: columna **Estado** (Aprobado si calificación &gt; 7.5, Reprobado si no, No realizada si está vacía). |
| 1.2 | 2026-10-02 | Agent | Si el Excel se rechazaba con “verifica el formulario”, ahora se acepta por extensión (.xlsx/.xls/.csv) y se muestra el error concreto. |
| 1.1 | 2026-10-02 | Agent | Import masivo más estable en archivos grandes; archivo sin filas válidas ya no vacía el tablero. |
| 1.0 | 2026-10-01 | Documentador | Version inicial FEAT-041: tablero Formación (Dashboard + Formaciones, export e import con reemplazo total). |
