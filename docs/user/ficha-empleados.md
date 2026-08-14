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
| Gestion Humana (Ficha empleados, edicion) | Todo lo anterior, mas ejecutar **Gestionar Empleado** / **Gestionar reingreso** y alta manual. |
| Gestion Humana (desvinculacion) | Usuarios con permiso **Desvincular** registran cierre formal de vinculo (causal, fechas, recontratable). |
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
3. Revise el encabezado **"Gestionar empleado — {nombre}"** y el bloque **Referencia de requisición** (solo lectura): código, cliente, cargo, salario/fecha sugeridos, texto de centro de costo y ciudad de la requisición. Esos datos de referencia **no** se exportan automáticamente a nómina.
4. Complete el formulario de ficha con los **catálogos obligatorios**: sexo, fecha ingreso, cargo, salario, centro de costo (catálogo nómina), EPS, AFP, caja de compensación, forma de pago, banco, tipo y número de cuenta. Elija un valor por campo de catálogo (formato `código — nombre`); el sistema guarda código y nombre homólogo.
   - Si corrige cédula o nombre aqui, el cambio queda **solo** en la ficha del empleado; **no** se refleja en la requisicion original.
5. Para descartar los cambios y dejar el registro intacto en **Pendientes**, pulse **Volver** (regresa a la pill Pendientes sin guardar nada).
6. Para confirmar, pulse **Crear empleado**. Si la cedula ingresada ya pertenece a **otro** registro de ficha, el sistema bloquea el guardado con un mensaje de error; corrija la cedula e intente de nuevo.
7. Al guardar con exito, el registro desaparece de **Pendientes**, queda con fecha y usuario que lo movio (**moved_to_ficha_at**/**moved_to_ficha_by**), y usted es redirigido al **listado principal** (pill **En ficha**), donde ya aparece el nuevo registro.

**Nota:** intentar abrir "Gestionar Empleado" de un registro que ya fue movido a ficha (por ejemplo, si otra persona lo gestiono primero) muestra un error de pagina no encontrada; recargue el listado de Pendientes para ver el estado actualizado.

### Consultar registros ya movidos (En ficha)

1. En la pestaña **Empleados**, cambie a la pill **En ficha**.
2. Revise las columnas adicionales **Agregado a ficha** y **Agregado por**.
3. Estos registros no admiten mas acciones desde esta pantalla.

### Completar ficha de empleado

1. En **Pendientes**, **En ficha** o **Nuevo empleado**, abra el formulario de ficha (permiso de edición).
2. Diligencie las siete secciones: identificación, contacto, contrato/nómina, centros, seguridad social, pagos y nómina avanzada.
3. Los campos marcados con **\*** son obligatorios para guardar.
4. Use los selectores de catálogo (EPS, AFP, centro de costo, banco, etc.) — no escriba manualmente el nombre homólogo.
5. La **desvinculación** se registra con **Registrar desvinculación** (permiso `ficha_empleados.terminate`), no con un campo suelto de fecha retiro.

### Registrar desvinculacion

1. Abra la ficha del empleado **activo**.
2. Pulse **Registrar desvinculacion** (solo usuarios con permiso de desvincular).
3. Complete causal, si es recontratable, ultimo dia de trabajo y fecha de desvinculacion.
4. Al confirmar, el vinculo activo se cierra y el empleado queda **desvinculado** (no sale en export masivos sin rango de fechas).

### Generar cartas de desvinculacion (Renuncia)

1. Tras desvincular por causal **Renuncia voluntaria**, abra la ficha del empleado.
2. Pulse **Generar cartas** (permiso de desvincular). El sistema descarga un **ZIP** con 3 Word editables:
   - Aceptacion Carta de Renuncia
   - Autorizacion examen de retiro
   - Certificado Laboral
3. Si ya genero el paquete, use **Descargar cartas** o **Regenerar cartas** (reemplaza el ZIP anterior).
4. Tambien puede generar/descargar desde el **Historial de vinculos** (icono historial en la ficha).

**Plantillas (administrador de catalogos):** en **Catalogos → Causal desvinculacion**, suba un `.docx` por cada documento. Use variables en corchetes (`[NOMBRE]`, `[CEDULA]`, `[SALARIO]`, etc.). Las plantillas se cargan una sola vez; cada desvinculacion solo genera la copia con datos del empleado.

### Reingreso por requisicion

1. Cree una requisicion **Contratado** con la misma cedula de un empleado desvinculado **recontratable**.
2. El registro vuelve a **Pendientes** con badge **Reingreso**.
3. Pulse **Gestionar reingreso**, revise condiciones laborales nuevas (cedula, nombre y fecha nacimiento no cambian) y confirme.
4. Se abre un nuevo vinculo; el historial de vinculos anteriores queda visible en la ficha.

### Exportar plantilla masivos (nómina)

1. Cambie a la pill **En ficha**.
2. Opcional: indique **fecha desde** y **fecha hasta** para filtrar por ingreso.
3. Pulse **Exportar plantilla masivos**.
4. Sin rango de fechas solo se exportan empleados **activos** (no desvinculados).
5. El archivo refleja **únicamente lo guardado** en la ficha (perfil + datos avanzados). No incluye valores inferidos de la requisición. La columna NIT de centro de trabajo **no** se exporta.
6. El archivo conserva el formato legacy de nómina (filas 1–2 de encabezado).

### Importar empleados masivamente

1. Pulse **Descargar plantilla importación** (formato vacío) o **Exportar datos para actualizar** (mismo formato con datos actuales de empleados en ficha).
2. Edite filas desde la fila 3; `cedula` es obligatoria.
3. Suba el archivo con **Importar**; verá un indicador de carga mientras se procesa el archivo.
4. Al terminar, el resumen aparece arriba del listado. Si hubo filas con error, se muestra el **detalle de errores** en pantalla (hasta 100 líneas).
5. Si la cédula ya existe, el import **actualiza** el perfil (no duplica).

**Exportar datos para actualizar:** sin rango de fechas exporta solo **activos**; con fechas filtra por **fecha de ingreso**. Respeta la búsqueda activa del listado (`q`).

### Administrar catalogos (selectores de ficha)

1. Vaya a la pestaña **Catalogos** (solo usuarios con permiso de edicion de ficha).
2. Elija el catalogo (EPS, AFP, Ciudad, Cargo, Centro de costo, Centro de trabajo, Caja compensacion, Banco, Forma de pago, Tipo cuenta, Jornada, etc.).
3. Agregue registros con **codigo**, **nombre**, orden y estado activo.
4. Edite o elimine entradas existentes; los inactivos no aparecen en los selectores de crear/editar empleado.

Alternativa masiva: `php artisan employee-ficha:seed-catalogs --from=docs/Contratacion` o importacion masiva de empleados (upsert de pares codigo/nombre).

## Control de cambios

| Version | Fecha | Autor | Descripcion del cambio |
| --- | --- | --- | --- |
| 1.5 | 2026-08-13 | FEAT-028 | Formulario ficha completo alineado a plantilla masivos (62 cols): selectores de catalogo, campos obligatorios, referencia de requisicion separada de datos exportables, export/import solo con datos guardados, NIT centro trabajo no exportado, tipo documento CE. |
| 1.4 | 2026-08-03 | FEAT-022 | **Gestionar Empleado** reemplaza a "Agregar a ficha empleados": el boton ahora abre el formulario de ficha precargado desde la requisicion (cedula, nombre y demas datos editables) y el registro solo se mueve a ficha al guardar con **Crear empleado**; se elimino el movimiento inmediato de un clic con confirmacion emergente. |
| 1.3 | 2026-07-31 | Catalogos UI | Pestaña Catalogos: CRUD de payroll_catalog_items para selectores de ficha. |
| 1.2 | 2026-07-31 | Import round-trip | Export plantilla import con datos actuales para actualización masiva. |
| 1.1 | 2026-07-31 | Plantillas | Perfil ficha, export Plantilla masivos, import SJ, catálogos nómina, filtro activos/fechas. |
| 1.0 | 2026-07-30 | FEAT-020 | Version inicial: tablero Ficha empleados, pestaña Empleados, accion Agregar a ficha empleados, export Excel. |
