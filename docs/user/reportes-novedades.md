# MT-GH-04 Novedades — Guia de usuario

> Documentacion operativa para usuarios finales. Ubicacion: `docs/user/reportes-novedades.md`.
> Feature: FEAT-040. Tablero UI: **MT-GH-04 Novedades**.

## Objetivo

Reemplazar el Excel de novedades de Gestion Humana (vacaciones, incapacidades, retiros y permisos/licencias) por un tablero en la plataforma: Gestion Humana registra y corrige los datos de cada hoja; Nómina completa su revision en columnas propias; ambos pueden exportar a Excel. Los retiros por desvinculacion en la plataforma se cargan solos en la hoja Retiros.

## Alcance

Aplica al tablero **MT-GH-04 Novedades** en el area **Gestion Humana**, con cuatro pestanas:

- **Vacaciones** — registrar disfrute o compensacion, dias, fecha de inicio y **fecha fin**; Nómina deja su observacion.
- **Incapacidades** — tipo, dias, fechas de control y devolucion; **días entrega** se calcula solo (hoy − inicio, o envío final − inicio); Nómina solo completa su observación.
- **Retiros** — altas manuales o automaticas al desvincular; motivo y fechas; Nómina observa.
- **Permisos** — licencias, sanciones, permisos remunerados y demas novedades de la hoja de permisos/licencia.

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
| Solo consulta | Con permiso de ver: filtra, exporta y ve historial; no edita. |
| Administrador de usuarios | Asigna permisos de tablero y por hoja (ver / editar / revisar) en Admin. |

## Desarrollo

### Asignar acceso

1. En **Admin → Usuarios**, edite el usuario.
2. En **Gestion humana**, marque **Ver tablero MT-GH-04 Novedades** si debe verlo en el menu.
3. En el subgrupo **MT-GH-04 Novedades**:
   - **Ver**: listar, filtrar, exportar e historial.
   - **Editar**: crear, corregir y eliminar filas (columnas GH).
   - **Revisar (Nomina)**: editar solo columnas Nómina (tambien permite ver y exportar esa hoja).
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
   - Con permiso, use también **Exportar** o **Historial**. En el Historial de la hoja puede filtrar por cédula/usuario, tipo de acción y rango de fechas.
4. Con **Editar**: cree o corrija filas. En el alta, escriba la **cédula** y pulse buscar (o Enter / salga del campo): se precargan nombre, cargo y destino desde Ficha.
5. Con **Revisar**: abra la fila y complete solo los campos de Nómina (el resto se ve en solo lectura). Cuando la **Observación Nómina** ya tiene texto, el campo y la celda del listado se muestran en **verde**.

### Retiros automaticos

Cuando se desvincula un empleado (desde Ficha o desde Desvinculaciones masivas), aparece una fila en **Retiros**. Si se revierte la desvinculacion, esa fila deja de mostrarse. Nómina completa despues su observacion.

## Control de cambios

| Version | Fecha | Autor | Descripcion del cambio |
| --- | --- | --- | --- |
| 1.5 | 2026-10-05 | Agent | Vacaciones: columna **Fecha fin** (tras Inicio) en formulario, listado y export. |
| 1.4 | 2026-10-01 | Agent | Historial de hoja (toolbar): filtros por cédula/usuario, acción y fechas. |
| 1.3 | 2026-09-30 | Agent | Filtros mes (YYYY-MM) + quincena; rango fechas opcional con precedencia; limpiar = periodo actual. Obs. Nómina en verde cuando ya tiene valor (listado + modal). |
| 1.2 | 2026-09-30 | Agent | Modales +: secciones Empleado/Novedad; lookup cédula precarga Ficha (blur/Enter/botón buscar). |
| 1.1 | 2026-09-30 | AgentSj | Label UI del tablero: MT-GH-04 Novedades (antes Reportes-Novedades). |
| 1.0 | 2026-09-30 | Documentador | Version inicial FEAT-040. |
