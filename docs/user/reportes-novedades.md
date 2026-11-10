# MT-GH-04 Novedades — Guia de usuario

> Documentacion operativa para usuarios finales. Ubicacion: `docs/user/reportes-novedades.md`.
> Feature: FEAT-040. Tablero UI: **MT-GH-04 Novedades**.

## Objetivo

Reemplazar el Excel de novedades de Gestion Humana (vacaciones, incapacidades, retiros y permisos/licencias) por un tablero en la plataforma: Gestion Humana registra y corrige los datos de cada hoja; Nómina completa su revision en columnas propias; ambos pueden exportar a Excel. Los retiros por desvinculacion en la plataforma se cargan solos en la hoja Retiros.

## Alcance

Aplica al tablero **MT-GH-04 Novedades** en el area **Gestion Humana**, con cuatro pestanas:

- **Vacaciones** — registrar disfrute o compensacion, dias, fechas (inicio, fin, reintegro), periodos y firma para la carta Word; Nómina deja su observacion.
- **Incapacidades** — tipo, dias, fechas de control y devolucion; **días entrega** se calcula solo (hoy − inicio, o envío final − inicio); Nómina solo completa su observación. Cada fila tiene **Adjuntos** (PDF o JPG) para consultar, previsualizar y descargar; quien edita puede agregarlos o quitarlos.
- **Retiros** — altas manuales o automaticas al desvincular; motivo y fechas; Nómina observa.
- **Permisos** – licencias, sanciones, permisos remunerados y demas novedades de la hoja de permisos/licencia. La columna **Marca GH** es un check que se guarda al hacer clic.

En cada pestana puede filtrar, exportar a Excel y consultar el **historial** de cambios de esa hoja o de una fila.

## Definiciones

| Termino | Significado |
| --- | --- |
| MT-GH-04 Novedades | Tablero de Gestion Humana equivalente a las cuatro hojas del Excel de novedades (matriz MT-GH-04). |
| Pestana / hoja | Una de las cuatro vistas: Vacaciones, Incapacidades, Retiros o Permisos. |
| Columnas GH | Datos que carga y corrige Gestion Humana (cedula, nombre, fechas, tipo de novedad, observaciones GH, etc.). |
| Columnas Nómina | Campos al final de cada fila que solo Nómina puede editar (observacion Nómina). En incapacidades, **días entrega** es calculado y no se edita. |
| Review | Accion de Nómina al completar sus columnas, sin modificar lo cargado por GH. |
| Historial | Lista de cambios registrados (quien, cuando, que hizo) de la hoja o de una fila. |
| Lookup cedula | Al escribir la cedula, el sistema intenta traer nombre, cargo y destino desde Ficha empleados. |

## Responsabilidades

| Rol | Que hace |
| --- | --- |
| Gestion Humana | Crea, edita y elimina filas en las columnas GH de la(s) hoja(s) asignada(s). Filtra, exporta y consulta historial. |
| Nómina | Ve las hojas asignadas, completa solo las columnas de review, filtra, exporta y consulta historial. No crea ni borra filas ni cambia datos GH. |
| Solo consulta | Con permiso de ver: filtra, exporta, ve historial y consulta, previsualiza y descarga adjuntos de incapacidades; no edita ni sube archivos. |
| Administrador de usuarios | Asigna permisos de tablero y por hoja (ver / editar / revisar) en Admin. |

## Desarrollo

### Asignar acceso

1. En **Admin → Usuarios**, edite el usuario.
2. En **Gestion humana**, marque **Ver tablero MT-GH-04 Novedades** si debe verlo en el menu.
3. En el subgrupo **MT-GH-04 Novedades**:
   - **Ver**: listar, filtrar, exportar, historial y, en Incapacidades, consultar, previsualizar y descargar adjuntos.
   - **Editar**: crear, corregir y eliminar filas (columnas GH). En Incapacidades también adjunta y elimina archivos.
   - **Revisar (Nomina)**: editar solo columnas Nómina (tambien permite ver, exportar, previsualizar y descargar adjuntos de incapacidades). No sube ni borra archivos.
4. Tipico: una persona de GH con Ver+Editar en una o varias hojas; usuarios de Nómina con Revisar en las cuatro.

### Usar el tablero

1. En Gestion Humana abra el tablero **MT-GH-04 Novedades**.
2. Elija la pestana de la hoja que le corresponde.
3. Use **Filtros**:
   - Por defecto ve la **quincena actual** del **mes actual** (selector mes tipo `YYYY-MM` + Quincena #1/#2).
   - El filtro aplica a la **fecha de inicio** (en Retiros, a la **fecha de retiro**).
   - Si elige **Inicio/Retiro desde** o **hasta** (basta uno), el rango de fechas prima y se vacían mes y quincena.
   - Al cambiar mes o quincena se vacían los datepickers de rango.
   - **Limpiar** restaura mes y quincena actuales.
   - En **Incapacidades** también puede filtrar por **Extemporánea** (Sí, No o Todas). El export a Excel respeta ese filtro.
   - Con permiso, use también **Exportar** o **Historial**. En el Historial de la hoja puede filtrar por cédula/usuario, tipo de acción y rango de fechas.
4. Con **Editar**: cree o corrija filas. En el alta, escriba la **cédula** y pulse buscar (o Enter / salga del campo): se precargan nombre, cargo y destino desde Ficha. En **Vacaciones**, al completar **días** y **fecha inicio** se sugieren **fecha fin** y **fecha reintegro** (sin domingos ni festivos; reintegro = dia siguiente a fin); complete **periodos** y **firma** como en Cartas Vacaciones. En acciones, use **Descargar carta Word** para generar el .docx del registro (requiere plantilla activa en Plantillas Word).
5. En **Permisos**, con **Editar**, marque o desmarque el check de la columna **Marca GH** directamente en el listado: el cambio se guarda al instante. Con Revisar o solo consulta, el check se ve deshabilitado. Con **Revisar**: abra la fila y complete solo los campos de Nómina (el resto se ve en solo lectura). Cuando la **Observación Nómina** ya tiene texto, el campo y la celda del listado se muestran en **verde**.
6. En **Incapacidades**, el icono de clip abre **Adjuntos**. Ahí se ven el nombre, la fecha, quién lo subió y el tamaño. El icono de ojo abre la **vista previa** del PDF o JPG; el de descarga guarda el archivo. Con **Editar** puede agregar varios PDF o JPG (de una vez o en otro momento) y eliminar uno. Al eliminar la incapacidad se borran también sus archivos. Nómina y quien solo consulta pueden ver, previsualizar y descargar, no subir ni borrar.
7. En cualquier pestana, si una **cédula** aparece en más de una fila del listado filtrado, el valor de la columna **Cédula** se muestra en **rojo** para que detecte los duplicados de inmediato. La marca se calcula sobre todos los registros del filtro (no solo la página visible) y no se incluye en el export a Excel.
8. Puede **ordenar** el listado haciendo clic en el encabezado de cualquier columna (Cédula, Nombre, Cargo, Destino, Novedad, Días, Inicio, Fin, Observaciones, Obs. Nómina, etc.). Un clic ordena ascendente y otro descendente. La columna **Acciones** no se ordena y, en **Incapacidades**, **Días entrega** tampoco (es un cálculo). En **Incapacidades**, **Extemporánea** (Sí/No) aparece después de Días entrega y sí se puede ordenar. Al entrar sin ordenar, se muestra primero la fecha más reciente. El orden es independiente de los filtros y el export a Excel conserva el orden por defecto.

### Retiros automaticos

Cuando se desvincula un empleado (desde Ficha o desde Desvinculaciones masivas), aparece una fila en **Retiros**. Si se revierte la desvinculacion, esa fila deja de mostrarse. Nómina completa despues su observacion.

## Control de cambios

| Version | Fecha | Autor | Descripcion del cambio |
| --- | --- | --- | --- |
| 1.14 | 2026-11-10 | Agent | Incapacidades: filtro **Extemporánea** (Sí / No / Todas) en Filtros; aplica al listado y al Excel. |
| 1.13 | 2026-11-10 | Agent | Incapacidades: columna **Extemporánea** (Sí/No) en el listado, después de Días entrega. |
| 1.12 | 2026-11-10 | Agent | Incapacidades / Adjuntos: icono de **vista previa** (ojo) a la izquierda de Descargar; abre PDF o JPG en overlay. |
| 1.11 | 2026-11-10 | Agent | Las cuatro hojas permiten **ordenar** el listado por cualquier columna visible (orden server-side con lista blanca); Acciones y Días entrega no son ordenables. |
| 1.10 | 2026-11-09 | Agent | En las cuatro hojas, la cédula se muestra en **rojo** cuando está duplicada dentro del listado filtrado. |
| 1.9 | 2026-11-09 | Agent | Permisos: columna **Marca GH** como check editable en el listado; se guarda al instante (solo permiso Editar). |
| 1.8 | 2026-11-06 | Agent | Incapacidades: adjuntos PDF/JPG por fila (subir y eliminar con Editar; ver y descargar con Ver o Nómina). |
| 1.7 | 2026-11-04 | Agent | Vacaciones: fecha reintegro, periodos y firma en alta/edición; descarga carta Word por fila (permiso editar). |
| 1.6 | 2026-11-03 | Agent | Vacaciones: la fecha fin se sugiere al ingresar días y fecha inicio (sin domingos ni festivos); el campo sigue editable. |
| 1.5 | 2026-10-05 | Agent | Vacaciones: columna **Fecha fin** (tras Inicio) en formulario, listado y export. |
| 1.4 | 2026-10-01 | Agent | Historial de hoja (toolbar): filtros por cédula/usuario, acción y fechas. |
| 1.3 | 2026-09-30 | Agent | Filtros mes (YYYY-MM) + quincena; rango fechas opcional con precedencia; limpiar = periodo actual. Obs. Nómina en verde cuando ya tiene valor (listado + modal). |
| 1.2 | 2026-09-30 | Agent | Modales +: secciones Empleado/Novedad; lookup cédula precarga Ficha (blur/Enter/botón buscar). |
| 1.1 | 2026-09-30 | AgentSj | Label UI del tablero: MT-GH-04 Novedades (antes Reportes-Novedades). |
| 1.0 | 2026-09-30 | Documentador | Version inicial FEAT-040. |
