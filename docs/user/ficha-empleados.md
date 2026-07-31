# Ficha empleados — Guia de usuario

## Objetivo

Permitir a Gestion Humana revisar la lista de espera de personas contratadas (capturadas al marcar una requisicion como **Contratado**) y moverlas a una ficha informativa de empleados, sin depender de quien gestiona la requisicion en el tablero **Requisiciones**.

## Alcance

Aplica al tablero **Ficha empleados**, visible unicamente en el area **Gestion Humana**, pestaña **Empleados**. Segun su perfil, el usuario puede:

- Ver la lista de espera (**Pendientes**) y los registros ya movidos (**En ficha**), con busqueda por cedula, nombre o codigo de requisicion.
- Exportar a Excel el listado con el filtro activo.
- Ejecutar **Agregar a ficha empleados** (solo con permiso de edicion) para mover un registro de Pendientes a En ficha.

**Fuera de alcance en esta version:** no existe modulo de alta de usuarios/nomina/expediente; "En ficha" es solo un marcador informativo, no crea cuentas ni registros en otros modulos. No hay edicion ni eliminacion de registros desde esta pantalla — las correcciones de cedula/nombre se hacen reabriendo la requisicion en **Requisiciones → Gestion**.

## Definiciones

| Termino | Significado |
| --- | --- |
| Lista de espera | Conjunto de personas contratadas que aun no han sido movidas a la ficha (pill **Pendientes**). |
| Ficha empleados | Registro informativo de personas ya revisadas/movidas por Gestion Humana (pill **En ficha**). |
| Cedula / Nombre del contratado | Datos capturados en la requisicion al marcar el estado **Contratado**; distintos de "Cedula/Nombre a quien reemplaza" del motivo Reemplazo. |
| Agregar a ficha empleados | Accion que mueve un registro de Pendientes a En ficha de forma inmediata y permanente. |
| Cedula duplicada | Situacion en la que la misma cedula ya esta registrada en otra requisicion; requiere confirmacion antes de reasignar el registro. |

## Responsabilidades

| Rol / perfil | Responsabilidad |
| --- | --- |
| Gestion Humana (gestiona requisiciones) | Marcar **Contratado** en la requisicion con cedula y nombre completo del contratado; resolver alertas de cedula duplicada. |
| Gestion Humana (Ficha empleados, lectura) | Consultar la lista de espera y la ficha, exportar a Excel. |
| Gestion Humana (Ficha empleados, edicion) | Todo lo anterior, mas ejecutar **Agregar a ficha empleados**. |
| Administrador | Asignar los permisos de Ficha empleados (lectura/edicion) a los usuarios de Gestion Humana que corresponda; puede coincidir o no con quien gestiona requisiciones. |

## Desarrollo

### Capturar la cedula y el nombre al marcar Contratado (Requisiciones → Gestion)

1. Abra **Requisiciones → Gestion humana → Gestion** y edite la requisicion.
2. En la seccion **Cierre**, cambie el **Estado** a **Contratado**.
3. Complete **Cedula persona contratada** y **Nombre completo persona contratada** (obligatorios solo con este estado); no los confunda con **Cedula/Nombre a quien reemplaza** (seccion Motivo).
4. Guarde. Si la cedula ya esta registrada en otra requisicion, aparecera una alerta de confirmacion indicando el codigo de esa requisicion; confirme solo si esta seguro de que es la misma persona (el registro existente se reasigna a la requisicion actual).
5. La persona queda automaticamente en la lista de espera de **Ficha empleados** (pill **Pendientes**).

### Consultar la lista de espera (Pendientes)

1. Entre al tablero **Ficha empleados** (visible solo si tiene el permiso correspondiente).
2. Abra la pestaña **Empleados**; por defecto se muestra la pill **Pendientes**.
3. Use el buscador (cedula, nombre o codigo de requisicion) para filtrar.
4. Revise las columnas: codigo de requisicion, cedula, nombre, cargo, cliente, ciudad y fecha de contratacion.

### Agregar a ficha empleados

1. En la pestaña **Empleados**, pill **Pendientes**, ubique el registro a mover.
2. Pulse **Agregar a ficha empleados** (visible solo con permiso de edicion).
3. Confirme en el mensaje emergente.
4. El registro desaparece de **Pendientes** y aparece en la pill **En ficha**, con fecha y usuario que lo movio.

### Consultar registros ya movidos (En ficha)

1. En la pestaña **Empleados**, cambie a la pill **En ficha**.
2. Revise las columnas adicionales **Agregado a ficha** y **Agregado por**.
3. Estos registros no admiten mas acciones desde esta pantalla.

### Exportar a Excel

1. En cualquiera de las dos pills (**Pendientes** o **En ficha**), aplique los filtros que necesite (busqueda).
2. Pulse **Exportar a Excel**.
3. El archivo descargado respeta el filtro y la busqueda activos al momento de exportar.

## Control de cambios

| Version | Fecha | Autor | Descripcion del cambio |
| --- | --- | --- | --- |
| 1.0 | 2026-07-30 | FEAT-020 | Version inicial: tablero Ficha empleados, pestaña Empleados, accion Agregar a ficha empleados, export Excel. |
