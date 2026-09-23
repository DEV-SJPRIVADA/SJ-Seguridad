# Selección — Guia de usuario

> Documentacion operativa para usuarios finales. Ubicacion: `docs/user/seleccion.md`.

## Objetivo

Apoyar a Gestión Humana en el seguimiento operativo de candidatos y trámites de **ingreso** y **examen ocupacional**, con un panel de indicadores, listados para registrar y consultar casos, y catálogos de apoyo (ciudad, cargo, EPS, etc.).

## Alcance

Aplica al tablero **Selección** en **Gestión Humana**, con pestañas:

- **Dashboard** — totales, ingresos del mes, exámenes en proceso, gráficos por estado de solicitud y tendencias; al cambiar filtros (fechas, cliente, responsable) se actualizan solos.
- **Ingreso** — listado paginado, filtros, alta/edición/eliminación y exportar a Excel. Registra datos de ingreso (cédula, contacto, ciudad, cargo, cliente, tallas, tipo de dotación, RH, responsable, etc.).
- **Examen ocupacional** — mismo tipo de listado para el trámite de examen (EPS, pensión, estado civil, fecha ARL, estado de solicitud, etc.).
- **Catálogos** — solo con permiso de edición: administrar listas usadas en los formularios (ciudad, cargo, EPS, AFP, RH, estado civil, estado de solicitud).

**En esta versión:**

- Los flujos de Ingreso y Examen son **independientes** (no se copian solos a la ficha del empleado ni a requisiciones).
- Se pueden tener **varios registros con la misma cédula** en el mismo listado; el sistema avisa y pide confirmación antes de guardar el duplicado.
- Al eliminar un registro, se borra de forma definitiva (no queda en papelera).
- No hay carga masiva por Excel (solo exportar).
- No se administran clientes comerciales ni tipos de uniforme desde aquí (se eligen de listas ya existentes).
- El responsable del caso debe estar marcado como encargado de selección en Parámetros de Requisiciones (Gestión humana).

## Definiciones

| Término | Significado |
| --- | --- |
| Ingreso | Registro operativo de una persona en proceso de ingreso (datos de contacto, cliente, cargo, tallas, fecha de ingreso, etc.). |
| Examen ocupacional | Registro del trámite de examen (ARL, EPS/AFP, estado de la solicitud, etc.). |
| SOLICITUD | Estado del trámite de examen (por ejemplo CONTRATADO, EN PROCESO, DXEMO). |
| EN PROCESO | Estado de solicitud usado en el Dashboard para el indicador de exámenes en curso. |
| RH | Grupo sanguíneo (O+, A-, etc.). |
| Responsable | Usuario de reclutamiento/selección asignado al caso. |
| Duplicado de cédula | Ya existe al menos un registro con esa cédula **en el mismo listado** (Ingreso o Examen). El aviso no cruza entre Ingreso y Examen. |
| Catálogos | Listas desplegables administrables (ciudad, cargo, EPS, AFP, RH, estado civil, estado de solicitud). |

## Responsabilidades

| Perfil | Puede |
| --- | --- |
| Consulta (ver tablero + permiso de consulta) | Ver Dashboard, Ingreso y Examen; filtrar; exportar a Excel. No crea, edita ni elimina; no ve Catálogos. |
| Operativo (permiso de edición) | Todo lo anterior + crear/editar/eliminar registros de Ingreso y Examen, confirmar duplicados de cédula, y administrar Catálogos. |
| Administración de usuarios | Asignar el tablero y los permisos de Selección a quienes correspondan (no vienen por defecto al rol usuario/administrador). |

## Desarrollo

### Entrar al tablero

1. En el menú de **Gestión Humana**, abra **Selección**.
2. Por defecto entra al **Dashboard**.
3. Use las pestañas superiores para cambiar entre Dashboard, Ingreso, Examen ocupacional y (si aplica) Catálogos.

### Usar el Dashboard

1. Ajuste el rango de fechas, el cliente y/o el responsable.
2. Revise los totales de ingresos y exámenes, los ingresos del mes actual y los exámenes en proceso.
3. Consulte los gráficos de estados de solicitud, tendencia mensual de ingresos y distribución por cliente/responsable.
4. Los datos se recalculan al cambiar un filtro (no hace falta un botón de “buscar”).

### Registrar o editar un Ingreso

1. Vaya a la pestaña **Ingreso**.
2. Pulse el botón para agregar (o editar una fila existente).
3. Complete **todos** los campos: cédula, nombre, correo, teléfono, ciudad, cargo, cliente, tallas, tipo de dotación, fecha de ingreso, RH, a quién reemplaza, responsable, referido y jefe OPE.
4. Si la cédula ya existe en Ingreso, el sistema muestra un aviso con los casos encontrados: confirme que desea continuar antes de guardar.
5. Guarde. El listado se actualizará.

### Registrar o editar un Examen ocupacional

1. Vaya a **Examen ocupacional**.
2. Agregue o edite un registro completando todos los campos (cédula, nombre, cargo, servicio/sector, cliente, EPS, pensión, fecha de nacimiento, ciudad, dirección, correo, celular, estado civil, fecha ARL, estado de solicitud y responsable).
3. Si la cédula ya existe **en Examen**, confirme el duplicado antes de guardar (el aviso no mira los ingresos).
4. Guarde.

### Filtrar, exportar y eliminar

1. Use los filtros del listado (por ejemplo fechas, cliente, responsable u otros disponibles en pantalla).
2. Para Excel, use el botón de exportar: se descarga según los filtros actuales.
3. Para eliminar, confirme en el aviso: el registro desaparece de forma permanente.

### Administrar Catálogos (solo edición)

1. Abra la pestaña **Catálogos** y elija la tarjeta del tipo (ciudad, cargo, EPS, AFP, RH, estado civil o estado de solicitud).
2. Cree o edite ítems (código, nombre, activo, orden).
3. Si un ítem está en uso en registros de Selección, el sistema **no permite borrarlo**; puede desactivarlo para que deje de aparecer en formularios nuevos.
4. Ciudad, cargo, EPS y AFP también se pueden editar desde Ficha empleados: los cambios se ven en ambos lados.

## Control de cambios

| Version | Fecha | Autor | Descripcion del cambio |
| --- | --- | --- | --- |
| 1.0 | 2026-09-22 | Documentador | Version inicial FEAT-035: tablero Selección (Dashboard, Ingreso, Examen ocupacional, Catálogos). |
| 1.1 | 2026-09-23 | Desarrollo | Ingresos: campo obligatorio REFERIDO (formulario, listado y Excel) después de RESPONSABLE. |
