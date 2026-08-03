# Ficha empleados — Guia de usuario

## Objetivo

Permitir a Gestion Humana revisar la lista de espera de personas contratadas (capturadas al marcar una requisicion como **Contratado**) y moverlas a una ficha informativa de empleados, sin depender de quien gestiona la requisicion en el tablero **Requisiciones**.

## Alcance

Aplica al tablero **Ficha empleados**, visible unicamente en el area **Gestion Humana**, pestaña **Empleados**. Segun su perfil, el usuario puede:

- Ver la lista de espera (**Pendientes**) y los registros ya movidos (**En ficha**), con busqueda por cedula, nombre o codigo de requisicion.
- Exportar a Excel el listado con el filtro activo.
- Ejecutar **Gestionar Empleado** (solo con permiso de edicion): abre el formulario de ficha precargado con los datos de la requisicion, permite revisar/corregir antes de guardar, y solo al presionar **Crear empleado** el registro se mueve de Pendientes a En ficha.

**Fuera de alcance en esta version:** no existe modulo de alta de usuarios/nomina/expediente; "En ficha" es solo un marcador informativo, no crea cuentas ni registros en otros modulos. No hay edicion ni eliminacion de registros desde esta pantalla — las correcciones de cedula/nombre se hacen reabriendo la requisicion en **Requisiciones → Gestion**.

## Definiciones

| Termino | Significado |
| --- | --- |
| Lista de espera | Conjunto de personas contratadas que aun no han sido movidas a la ficha (pill **Pendientes**). |
| Ficha empleados | Registro informativo de personas ya revisadas/movidas por Gestion Humana (pill **En ficha**). |
| Cedula / Nombre del contratado | Datos capturados en la requisicion al marcar el estado **Contratado**; distintos de "Cedula/Nombre a quien reemplaza" del motivo Reemplazo. |
| Gestionar Empleado | Boton de la fila (en **Pendientes**) que abre el formulario de ficha precargado con los datos de la requisicion; el registro solo pasa a **En ficha** cuando se guarda con **Crear empleado**. Reemplaza la accion anterior "Agregar a ficha empleados" (movimiento inmediato de un clic, sin revisar datos, ya retirada). |
| Cedula duplicada | Situacion en la que la misma cedula ya esta registrada en otra requisicion; requiere confirmacion antes de reasignar el registro. Si ocurre al guardar el formulario de **Gestionar Empleado**, en cambio, bloquea el guardado con un error de validacion (no se permite duplicado ahi). |

## Responsabilidades

| Rol / perfil | Responsabilidad |
| --- | --- |
| Gestion Humana (gestiona requisiciones) | Marcar **Contratado** en la requisicion con cedula y nombre completo del contratado; resolver alertas de cedula duplicada. |
| Gestion Humana (Ficha empleados, lectura) | Consultar la lista de espera y la ficha, exportar a Excel. |
| Gestion Humana (Ficha empleados, edicion) | Todo lo anterior, mas ejecutar **Gestionar Empleado** (revisar/completar datos y mover un pendiente a ficha) y el alta manual de empleados sin requisicion. |
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

### Gestionar Empleado (mover un pendiente a ficha)

1. En la pestaña **Empleados**, pill **Pendientes**, ubique el registro a mover.
2. Pulse **Gestionar Empleado** (visible solo con permiso de edicion). Se abre el formulario de ficha; no se ejecuta ningun cambio todavia.
3. Revise el encabezado **"Gestionar empleado — {nombre}"** y el bloque de referencia de solo lectura con el codigo de requisicion, cliente y cargo.
4. Revise y, si es necesario, corrija los campos ya precargados desde la requisicion: **cedula**, **nombre completo**, sexo, salario, fecha de ingreso, centro de costo, cargo, tipo de contrato, ciudad y cliente. Complete los demas datos de la ficha (contacto, nomina, EPS/AFP, etc.) igual que en el alta manual.
   - Si corrige la cedula o el nombre aqui, el cambio queda **solo** en la ficha del empleado; **no** se refleja en la requisicion original.
5. Para descartar los cambios y dejar el registro intacto en **Pendientes**, pulse **Volver** (regresa a la pill Pendientes sin guardar nada).
6. Para confirmar, pulse **Crear empleado**. Si la cedula ingresada ya pertenece a **otro** registro de ficha, el sistema bloquea el guardado con un mensaje de error; corrija la cedula e intente de nuevo.
7. Al guardar con exito, el registro desaparece de **Pendientes**, queda con fecha y usuario que lo movio (**moved_to_ficha_at**/**moved_to_ficha_by**), y usted es redirigido al **listado principal** (pill **En ficha**), donde ya aparece el nuevo registro.

**Nota:** intentar abrir "Gestionar Empleado" de un registro que ya fue movido a ficha (por ejemplo, si otra persona lo gestiono primero) muestra un error de pagina no encontrada; recargue el listado de Pendientes para ver el estado actualizado.

### Consultar registros ya movidos (En ficha)

1. En la pestaña **Empleados**, cambie a la pill **En ficha**.
2. Revise las columnas adicionales **Agregado a ficha** y **Agregado por**.
3. Estos registros no admiten mas acciones desde esta pantalla.

### Completar ficha de empleado

1. En **Pendientes** o **En ficha**, pulse **Completar ficha** (permiso de edición).
2. Diligencie datos de contacto, nómina, EPS/AFP, centro de costo, etc.
3. Si indica **fecha retiro**, el empleado queda como **desvinculado** y no saldrá en exportaciones masivas sin rango de fechas.

### Exportar plantilla masivos (nómina)

1. Cambie a la pill **En ficha**.
2. Opcional: indique **fecha desde** y **fecha hasta** para filtrar por ingreso.
3. Pulse **Exportar plantilla masivos**.
4. Sin rango de fechas solo se exportan empleados **activos** (no desvinculados).
5. El archivo conserva el formato legacy de nómina (filas 1–2 de encabezado).

### Importar empleados masivamente

1. Pulse **Descargar plantilla importación** (formato vacío) o **Exportar datos para actualizar** (mismo formato con datos actuales de empleados en ficha).
2. Edite filas desde la fila 3; `cedula` es obligatoria.
3. Suba el archivo con **Importar**; verá un indicador de carga mientras se procesa el archivo.
4. Al terminar, el resumen aparece arriba del listado. Si hubo filas con error, se muestra el **detalle de errores** en pantalla (hasta 100 líneas).
5. Si la cédula ya existe, el import **actualiza** el perfil (no duplica).

**Exportar datos para actualizar:** sin rango de fechas exporta solo **activos**; con fechas filtra por **fecha de ingreso**. Respeta la búsqueda activa del listado (`q`).

### Administrar catalogos (selectores de ficha)

1. Vaya a la pestaña **Catalogos** (solo usuarios con permiso de edicion de ficha).
2. Elija el catalogo (EPS, AFP, Ciudad, Cargo, Centro de costo, etc.).
3. Agregue registros con **codigo**, **nombre**, orden y estado activo.
4. Edite o elimine entradas existentes; los inactivos no aparecen en los selectores de crear/editar empleado.

Alternativa masiva: `php artisan employee-ficha:seed-catalogs --from=docs/Contratacion` o importacion masiva de empleados (upsert de pares codigo/nombre).

## Control de cambios

| Version | Fecha | Autor | Descripcion del cambio |
| --- | --- | --- | --- |
| 1.4 | 2026-08-03 | FEAT-022 | **Gestionar Empleado** reemplaza a "Agregar a ficha empleados": el boton ahora abre el formulario de ficha precargado desde la requisicion (cedula, nombre y demas datos editables) y el registro solo se mueve a ficha al guardar con **Crear empleado**; se elimino el movimiento inmediato de un clic con confirmacion emergente. |
| 1.3 | 2026-07-31 | Catalogos UI | Pestaña Catalogos: CRUD de payroll_catalog_items para selectores de ficha. |
| 1.2 | 2026-07-31 | Import round-trip | Export plantilla import con datos actuales para actualización masiva. |
| 1.1 | 2026-07-31 | Plantillas | Perfil ficha, export Plantilla masivos, import SJ, catálogos nómina, filtro activos/fechas. |
| 1.0 | 2026-07-30 | FEAT-020 | Version inicial: tablero Ficha empleados, pestaña Empleados, accion Agregar a ficha empleados, export Excel. |
