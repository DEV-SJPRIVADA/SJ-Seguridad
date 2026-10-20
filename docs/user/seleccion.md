# Selección — Guia de usuario

> Documentacion operativa para usuarios finales. Ubicacion: `docs/user/seleccion.md`.

## Objetivo

Apoyar a Gestión Humana en el seguimiento operativo de candidatos y trámites de **ingreso** y **examen ocupacional**, con un panel de indicadores, listados para registrar y consultar casos, y catálogos de apoyo (ciudad, cargo, EPS, etc.).

## Alcance

Aplica al tablero **Selección** en **Gestión Humana**, con pestañas:

- **Dashboard** — totales, ingresos del mes, exámenes en proceso, gráficos por estado de solicitud y tendencias; al cambiar filtros (fechas, cliente, responsable) se actualizan solos.
- **Ingreso** — listado paginado, filtros (por fechas de ingreso/asistencia, estado, cliente, responsable, etc.), alta/edición/eliminación y exportar a Excel. Registra datos de ingreso (cédula, contacto, ciudad, cargo, cliente, tallas, tipo de dotación, RH, responsable, **fecha de asistencia**, **estado**, **jefe operativo**, **observaciones**, etc.).
- **Examen ocupacional** — mismo tipo de listado para el trámite de examen (EPS, pensión, estado civil, fecha ARL, estado de solicitud, etc.).
- **Catálogos** — solo con permiso de edición: administrar listas usadas en los formularios (ciudad, cargo, EPS, AFP, RH, estado civil, estado de solicitud, **estado de ingreso**, **jefe operativo**).

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
| Ingreso | Registro operativo de una persona en proceso de ingreso (datos de contacto, cliente, cargo, tallas, fecha de ingreso, estado del proceso, jefe operativo, etc.). |
| Examen ocupacional | Registro del trámite de examen (ARL, EPS/AFP, estado de la solicitud, etc.). |
| SOLICITUD | Estado del trámite de examen (por ejemplo CONTRATADO, EN PROCESO, DXEMO). |
| ESTADO (Examen) | Avance del examen: SOLICITADO, AGENDADO, APROBADO, NO APROBADO o CANCELADO. Lista fija (no es catálogo). |
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
3. Complete los campos obligatorios: cédula, nombre, correo, teléfono, ciudad, cargo, cliente, tallas, tipo de dotación, fecha de ingreso, **estado** del proceso, RH, a quién reemplaza, responsable y **jefe operativo**. Además puede registrar la **fecha de asistencia**, el **referido** y **observaciones** (opcionales).
4. Elija el **Estado** y el **Jefe operativo** desde los catálogos: si el listado del jefe operativo está vacío, primero agregue jefes en la pestaña **Catálogos** (es requisito para guardar el ingreso).
5. El formulario está dividido en tres bloques: **Datos del aspirante**, **Cliente y dotación** y **Proceso de selección**. Los campos opcionales dicen «(opcional)».
6. Si la cédula ya existe en Ingreso, el sistema muestra un aviso con los casos encontrados (debajo de los datos del aspirante): confirme que desea continuar antes de guardar.
7. Pulse **Guardar ingreso** (o **Actualizar ingreso** al editar) en la parte inferior del modal; **Cancelar** cierra sin guardar. El listado se actualizará.

### Registrar o editar un Examen ocupacional

1. Vaya a **Examen ocupacional**.
2. Agregue o edite un registro completando todos los campos. El formulario tiene tres bloques: **Datos del aspirante** (cédula, nombres, fecha de nacimiento, estado civil, celular, correo, ciudad, dirección), **Cargo y afiliaciones** (cliente, cargo, servicio/sector, EPS, pensión) y **Proceso del examen** (fecha ARL, **estado**, solicitud y responsable).
3. El **correo** debe tener un formato válido (por ejemplo nombre@dominio.com); si no, el sistema no deja guardar.
4. El **estado** del examen puede ser SOLICITADO, AGENDADO, APROBADO, NO APROBADO o CANCELADO. Los exámenes nuevos inician en **SOLICITADO**; cámbielo al editar a medida que avance el trámite.
5. Si la cédula ya existe **en Examen**, confirme el duplicado antes de guardar (el aviso no mira los ingresos).
6. Pulse el icono de guardar; el icono ✕ cierra sin guardar.
7. En el listado se ven todos los campos del formulario; el estado aparece como una etiqueta de color. En los filtros puede elegir un estado con los botones de **Estado** (como en Requisiciones).

### Consultar el historial de cambios del Examen (solo edición)

1. Para ver todo lo que se ha hecho en Examen ocupacional, pulse el icono de **historial** (reloj) junto a Exportar.
2. Filtre por **usuario**, acción (alta, actualización, eliminación), cédula y rango de fechas.
3. Para ver solo un registro, pulse el icono de historial en la columna **Acciones** de esa fila.
4. Cada evento muestra fecha, usuario y los campos cambiados (valor anterior → valor nuevo).

### Filtrar, exportar y eliminar

1. Use los filtros del listado (fechas de ingreso y de asistencia —desde/hasta—, estado, cliente, responsable, ciudad, cargo y búsqueda).
2. Para Excel, use el botón de exportar: se descarga según los filtros actuales.
3. Para eliminar, confirme en el aviso: el registro desaparece de forma permanente.

### Administrar Catálogos (solo edición)

1. Abra la pestaña **Catálogos** y elija la tarjeta del tipo (ciudad, cargo, EPS, AFP, RH, estado civil, estado de solicitud, **estado de ingreso** o **jefe operativo**).
2. Cree o edite ítems. En **ciudad, cargo, EPS y AFP** se indica código, nombre, orden y si está activo. En **RH, estado civil, estado de solicitud, estado de ingreso y jefe operativo** solo se escribe el **nombre** y si está activo: el sistema asigna el código y el orden (los nuevos quedan al final). No se permiten dos ítems con el mismo nombre en esos catálogos. Los estados de ingreso iniciales ya vienen cargados (Contratado, En proceso, En reserva, Desiste del proceso, No contratado).
3. Si un ítem está en uso en registros de Selección, el sistema **no permite borrarlo**; puede desactivarlo para que deje de aparecer en formularios nuevos.
4. Ciudad, cargo, EPS y AFP también se pueden editar desde Ficha empleados: los cambios se ven en ambos lados.

## Control de cambios

| Version | Fecha | Autor | Descripcion del cambio |
| --- | --- | --- | --- |
| 1.0 | 2026-09-22 | Documentador | Version inicial FEAT-035: tablero Selección (Dashboard, Ingreso, Examen ocupacional, Catálogos). |
| 1.1 | 2026-09-23 | Desarrollo | Ingresos: campo obligatorio REFERIDO (formulario, listado y Excel) después de RESPONSABLE. |
| 1.6 | 2026-10-20 | Desarrollo | Examen ocupacional: nuevo **estado** (SOLICITADO, AGENDADO, APROBADO, NO APROBADO, CANCELADO), todas las columnas del formulario en la tabla (estado y correo tras fecha ARL), validación de formato de correo, historial de cambios por usuario y por registro, y modal Nuevo/Editar rediseñado por bloques. |
| 1.5 | 2026-10-20 | Desarrollo | Ingreso: filtros en dos filas (búsqueda y fechas arriba con los íconos de filtrar/limpiar a la derecha; selectores abajo). El ícono Exportar Excel queda junto a Nuevo ingreso y exporta con los filtros aplicados. |
| 1.4 | 2026-10-20 | Desarrollo | Catálogos con tabla más compacta; modal de Ingreso con botones en icono (cerrar / guardar) y sin saltos al abrir los selectores. |
| 1.3 | 2026-10-20 | Desarrollo | Catálogos RH, estado civil, estado de solicitud, estado de ingreso y jefe operativo: sin campos Código ni Orden (asignación automática; nombre único). |
| 1.6 | 2026-10-20 | Desarrollo | Ingreso: al guardar, editar o eliminar ya no aparece el aviso verde encima de los filtros; la confirmación se ve solo en el mensaje flotante. |
| 1.5 | 2026-10-20 | Desarrollo | Ingreso: el campo **Referido** ya no es obligatorio (al crear ni al editar). |
| 1.4 | 2026-10-20 | Desarrollo | Ingreso: modal Nuevo/Editar con tres bloques (aspirante, cliente y dotación, proceso), botones **Cancelar** y **Guardar ingreso**; se corrige el error que impedía validar el duplicado de cédula al guardar (también en Examen). |
| 1.2 | 2026-10-20 | Desarrollo | Ingresos: nuevas columnas FECHA ASISTENCIA, ESTADO (con catálogo **Estado ingreso**), JEFE OPERATIVO (con catálogo **Jefe operativo**) y OBSERVACIONES; filtros de asistencia (rango) y estado; export ampliado; modal Nuevo/Editar rediseñado y botón Guardar/Actualizar en icono; los desplegables ya no se recortan dentro de los modales. |
