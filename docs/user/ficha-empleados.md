# Ficha empleados — Guia de usuario

## Objetivo

Permitir a Gestion Humana revisar la lista de espera de personas contratadas (capturadas al marcar una requisicion como **Contratado**) y moverlas a una ficha informativa de empleados, sin depender de quien gestiona la requisicion en el tablero **Requisiciones**.

## Alcance

Aplica al tablero **Ficha empleados**, visible unicamente en el area **Gestion Humana**, pestaña **Empleados**. Segun su perfil, el usuario puede:

- Ver la lista de espera (**Pendientes**) y los registros ya movidos (**En ficha**), con busqueda por cedula, nombre o codigo de requisicion.
- Exportar a Excel el listado con el filtro activo.
- Ejecutar **Gestionar Empleado** (solo con permiso de edicion): abre el formulario de ficha precargado con los datos de la requisicion, permite revisar/corregir antes de guardar, y solo al presionar **Crear empleado** el registro se mueve de Pendientes a En ficha.
- Generar **Carta de contratación** desde Pendientes (mismo permiso de edición): formulario corto solo con los datos de la carta; el empleado **sigue en Pendientes** hasta que complete la ficha.

**Fuera de alcance en esta version:** no existe modulo de alta de usuarios/nomina/expediente; "En ficha" es solo un marcador informativo, no crea cuentas ni registros en otros modulos. No hay edicion ni eliminacion de registros desde esta pantalla — las correcciones de cedula/nombre se hacen reabriendo la requisicion en **Requisiciones → Gestion**.

## Definiciones

| Termino | Significado |
| --- | --- |
| Lista de espera | Conjunto de personas contratadas que aun no han sido movidas a la ficha (pill **Pendientes**). |
| Ficha empleados | Registro informativo de personas ya revisadas/movidas por Gestion Humana (pill **En ficha**). |
| Cedula / Nombre del contratado | Datos capturados en la requisicion al marcar el estado **Contratado**; distintos de "Cedula/Nombre a quien reemplaza" del motivo Reemplazo. |
| Gestionar Empleado | Boton de la fila (en **Pendientes**) que abre el formulario de ficha precargado con los datos de la requisicion; el registro solo pasa a **En ficha** cuando se guarda con **Crear empleado**. Reemplaza la accion anterior "Agregar a ficha empleados" (movimiento inmediato de un clic, sin revisar datos, ya retirada). |
| Carta de contratación (rápida) | Icono de documento en la fila de **Pendientes** (contrataciones nuevas, no reingresos). Abre un formulario corto con los datos que usa la carta Word; al generar descarga el archivo y **no** mueve el registro a En ficha. Luego la misma persona completa la ficha con **Gestionar Empleado**. |
| Cedula duplicada | Situacion en la que la misma cedula ya esta registrada en otra requisicion; requiere confirmacion antes de reasignar el registro. Si ocurre al guardar el formulario de **Gestionar Empleado**, en cambio, bloquea el guardado con un error de validacion (no se permite duplicado ahi). |

## Responsabilidades

| Rol / perfil | Responsabilidad |
| --- | --- |
| Gestion Humana (gestiona requisiciones) | Marcar **Contratado** en la requisicion con cedula y nombre completo del contratado; resolver alertas de cedula duplicada. |
| Gestion Humana (Ficha empleados, lectura) | Consultar la lista de espera y la ficha (abrir detalle en solo lectura), exportar a Excel. |
| Gestion Humana (Ficha empleados, edicion) | Todo lo anterior, mas ejecutar **Gestionar Empleado** / **Gestionar reingreso**, **Carta de contratación** desde Pendientes, alta manual y **editar/guardar** la ficha. |
| Gestion Humana (desvinculacion) | Usuarios con permiso **Desvincular** registran cierre formal de vinculo (causal, fechas, recontratable) y pueden **Generar** / **Descargar** cartas. Al desvincular se crea el seguimiento en **Desvinculaciones**; al generar carta se marca «tiene carta». |
| Gestion Humana (tablero Desvinculaciones) | Operadores con el paquete de permisos del tablero ejecutan Masivos y completan Seguimientos (ver [`desvinculaciones.md`](desvinculaciones.md)). |
| Administrador de Plantillas Word | Sube y mantiene plantillas en el tablero **Plantillas Word** (permiso distinto al de desvinculacion). |
| Administrador | Asignar los permisos de Ficha empleados (lectura/edicion/desvinculacion) y, si aplica, los de Plantillas Word; puede coincidir o no con quien gestiona requisiciones. |

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
5. Para descargar el listado a Excel, use el icono de Excel en la barra de acciones (respeta la búsqueda activa).

### Gestionar Empleado (mover un pendiente a ficha)

1. En la pestaña **Empleados**, pill **Pendientes**, ubique el registro a mover.
2. Pulse el icono **Gestionar Empleado** (visible solo con permiso de edición; al pasar el mouse muestra el nombre de la acción). Se abre el formulario de ficha; no se ejecuta ningún cambio todavía.
3. Revise el encabezado **"Gestionar empleado — {nombre}"** y el bloque **Referencia de requisición** (solo lectura): código, cliente, cargo, salario/fecha sugeridos, texto de centro de costo y ciudad de la requisición. Esos datos de referencia **no** se exportan automáticamente a nómina.
4. Complete el formulario de ficha: primero indique si **requiere cursos** y/o **requiere acreditación** (por defecto ambos activos); luego cédula, **lugar de nacimiento** (obligatorio), y los **catálogos obligatorios**: sexo, fecha ingreso, cargo, salario, centro de costo (catálogo nómina), EPS, AFP, caja de compensación, forma de pago, banco, tipo y número de cuenta. Elija un valor por campo de catálogo (formato `código — nombre`); el sistema guarda código y nombre homólogo.
   - Si corrige cédula o nombre aqui, el cambio queda **solo** en la ficha del empleado; **no** se refleja en la requisicion original.
5. Para descartar los cambios y dejar el registro intacto en **Pendientes**, use el icono **Volver** (flecha) en la barra superior (regresa a la pill Pendientes sin guardar nada).
6. Para confirmar, use el icono **Guardar** (disquete) en la barra superior. Si falta un campo obligatorio, la cédula ya existe u otro dato es inválido, verá un aviso rojo arriba del formulario con la lista de motivos y, cuando aplique, el mensaje bajo el campo; la página se desplaza hacia el primer error. Corrija e intente de nuevo.
7. Al guardar con exito, el registro desaparece de **Pendientes**, queda con fecha y usuario que lo movio (**moved_to_ficha_at**/**moved_to_ficha_by**), y usted es redirigido al **listado principal** (pill **En ficha**), donde ya aparece el nuevo registro.

**Nota:** si intenta abrir **Gestionar Empleado** de un registro que ya fue movido a ficha (por ejemplo, si otra persona lo gestionó primero), el sistema lo regresa al listado de Pendientes con un mensaje explicando que el registro ya no está disponible.

### Carta de contratación desde Pendientes (antes de En ficha)

1. En la pill **Pendientes**, pulse el icono de **Carta de contratación** (documento) de la fila. No aparece en reingresos (use **Gestionar reingreso**).
2. Complete solo los datos de la carta: nombre, documento, lugar y fecha de nacimiento, dirección, ciudad de residencia, teléfono, correo, salario, fecha de ingreso y cargo. La ciudad de la requisición se muestra solo lectura.
3. Elija plantilla(s) de contratación y firmante; pulse el icono de generar (documento) en la barra superior.
4. Se descarga el Word/ZIP. El empleado **sigue en Pendientes**. Los datos mínimos quedan guardados para cuando complete la ficha con **Gestionar Empleado**.
5. Si ya está **En ficha**, genere la carta desde la barra de acciones de la ficha (flujo anterior).

### Consultar registros ya movidos (En ficha)

1. En la pestaña **Empleados**, cambie a la pill **En ficha**.
2. Haga clic en una fila para abrir el detalle de la ficha.
3. Con permiso solo de **ver**, la ficha se muestra en **solo lectura** (sin «Habilitar edición» ni Guardar). Con permiso de **edición**, use **Habilitar edición** (barra sobre el formulario) y guarde. Use el icono **Generar Cartas** (documento) en la barra superior: se abre el mismo modal con **tipo de documento** (todos los tipos activos de Plantillas Word), lista compacta de plantillas según el tipo y firmante. En ficha de desvinculado el icono abre el mismo modal preseleccionando desvinculación; en activo, contratación. Tipos no aplicables aparecen deshabilitados con mensaje. Al generar se llama al endpoint del tipo elegido. Con permiso de ver ficha también puede abrir **Consultar cursos** (gorro) y **Consultar acreditación** (insignia) para ver el historial por cédula sin salir de la ficha.
4. En el filtro **Desvinculado** aparece la columna **Recontratable** (Si/No) según lo registrado al desvincular.

### Completar ficha de empleado

1. En **Pendientes**, **En ficha** o **Nuevo empleado**, abra el formulario de ficha (permiso de edición).
2. En **Nuevo empleado** / **Gestionar empleado**, la primera sección es **Cursos y acreditación**: marque o desmarque si la persona requiere cursos y/o acreditación (por defecto ambos activos). Si desactiva un requisito, no aparecerá en las validaciones de ese módulo.
3. Diligencie documento (cédula) y las secciones: identificación, contacto, contrato/nómina, centros, seguridad social, pagos y nómina avanzada.
4. Los campos marcados con **\*** son obligatorios para guardar.
5. Use los selectores de catálogo (EPS, AFP, centro de costo, banco, etc.) — no escriba manualmente el nombre homólogo.
6. En **Contrato y nómina** verá **Fecha desvinculación** (si el empleado se retiró por importación o desvinculación formal). Puede corregirla al editar; si tiene fecha ≤ hoy el estado pasa a desvinculado. La desvinculación **formal** (causal, cartas, seguimiento) se registra con **Registrar desvinculación**.
7. En alta, use los iconos de la barra superior: **Volver** y **Guardar**.

### Registrar desvinculacion

1. Abra la ficha del empleado **activo**.
2. Pulse **Registrar desvinculacion** (solo usuarios con permiso de desvincular).
3. Complete causal, si es recontratable, ultimo dia de trabajo y fecha de desvinculacion.
4. Si la persona tiene **vacaciones, incapacidad o permiso** en **MT-GH-04 Novedades** cuyas fechas se cruzan con el retiro, el sistema **no permite** desvincular y muestra el motivo. (Solo un **super-admin** puede marcar «Forzar…» para continuar.)
5. Al confirmar, el vinculo activo se cierra y el empleado queda **desvinculado** (no sale en export masivos sin rango de fechas).
6. Automaticamente se crea (si no existia) una fila en el tablero **Desvinculaciones → Seguimientos** para el checklist post-retiro. Guia: [`desvinculaciones.md`](desvinculaciones.md).

### Generar y descargar cartas de desvinculacion

Disponible cuando el vinculo esta **cerrado** (empleado desvinculado), con **cualquier** causal, y usted tiene permiso de desvinculacion.

1. Abra la ficha del empleado desvinculado (o el **Historial de vínculos**).
2. En la barra superior, junto al icono de **Historial de vínculos**, pulse el icono de **Generar Cartas** (documento). También puede usarlo en cada vínculo cerrado del modal de historial. Se abre el mismo modal que en activos, con tipo **Desvinculación** preseleccionado y las plantillas de ese tipo.
3. Marque **una o varias** plantillas (al menos una) y confirme.
   - Si elige **una**, descarga un archivo Word (`.docx`).
   - Si elige **varias**, descarga un **ZIP** con esos Word.
4. El archivo queda guardado en ese vinculo. Si vuelve a generar, **reemplaza** el archivo anterior. Ademas, el seguimiento en **Desvinculaciones** pasa a indicar que **tiene carta generada**.
5. Use el icono **Descargar cartas** (flecha hacia abajo) para volver a bajar el último archivo generado (no vuelve a armarlo desde cero). No existe un botón separado de “Regenerar”: para generar de nuevo, use otra vez **Generar cartas**.
6. Si un lote de **Desvinculaciones → Masivos** dejó al empleado sin carta, use este mismo flujo de Ficha (hace falta el permiso de desvincular en Ficha) para generar la carta y actualizar el seguimiento.

Si el listado del modal esta vacio, un administrador debe subir plantillas en el tablero **Plantillas Word** (tipo Desvinculacion). Las plantillas antiguas de Renuncia **no** se migraron solas: hay que re-subirlas. Guia: [`plantillas-word.md`](plantillas-word.md).

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
5. El archivo refleja **únicamente lo guardado** en la ficha (perfil + datos avanzados). No incluye valores inferidos de la requisición. La columna NIT de centro de trabajo **no** se exporta. La **ciudad de trabajo** tampoco va en esta plantilla de nómina.
6. El archivo conserva el formato legacy de nómina (filas 1–2 de encabezado).

### Importar empleados masivamente

1. Pulse **Descargar plantilla vacia** (formato vacío) o **Exportar datos para actualizar** (mismo formato con datos actuales de empleados en ficha).
2. Edite filas desde la fila 3; `cedula` es obligatoria. Use `primer_apellido`, `segundo_apellido`, `primer_nombre` y `segundo_nombre` (como en la ficha). `nombre` completo es opcional (plantillas antiguas). El orden sigue el extracto tipo nompr07; al final van opcionales `codigo_ciudad_trabajo`, `ciudad_trabajo` y `codigo_requisicion`. Puede pegar valores legibles (`CEDULA`, `Masculino`, `Ahorro`); el sistema los guarda como códigos (`C`, `M`, `1`) para la plantilla de nómina.
3. Suba el archivo con **Importar**; verá un indicador de carga mientras se procesa el archivo.
4. Al terminar, el resumen aparece arriba del listado. Si hubo filas con error, se muestra el **detalle de errores** en pantalla (hasta 100 líneas).
5. Si la cédula ya existe, el import **actualiza** el perfil (no duplica) y también sincroniza el nombre del listado (`hired_full_name`) con los apellidos/nombres del Excel.
6. Si el Excel trae nombres con `?` por encoding roto (p. ej. `MU?OZ`), el import los corrige a `Ñ`/`Ó` al guardar.

**Exportar datos para actualizar:** sin rango de fechas exporta solo **activos**; con fechas filtra por **fecha de ingreso**. Respeta la búsqueda activa del listado (`q`).

### Ciudad de trabajo

Al gestionar un empleado desde una requisicion, la **ciudad de trabajo** se precarga con la ciudad de la requisicion (y se sincroniza al catalogo Ciudad si hace falta). Puede editarla en el formulario. Va en la plantilla de importacion/actualizacion; **no** en la exportacion de plantilla masivos (nomina).

### Administrar catalogos (selectores de ficha)

1. Vaya a la pestaña **Catalogos** (solo usuarios con permiso de edicion de ficha).
2. Elija el catalogo (EPS, AFP, Ciudad, Cargo, Centro de costo, Centro de trabajo, Caja compensacion, Banco, Forma de pago, Tipo cuenta, Jornada, etc.).
3. Agregue registros con **codigo**, **nombre**, orden y estado activo.
4. Edite o elimine entradas existentes; los inactivos no aparecen en los selectores de crear/editar empleado.

Alternativa masiva: `php artisan employee-ficha:seed-catalogs --from=docs/Contratacion` o importacion masiva de empleados (upsert de pares codigo/nombre).

## Control de cambios

| Version | Fecha | Autor | Descripcion del cambio |
| --- | --- | --- | --- |
| 1.29 | 2026-10-05 | Agent | Unificación: activo y desvinculado usan el mismo modal **Generar Cartas** (tipo preseleccionado según el icono). |
| 1.28 | 2026-10-05 | Agent | Icono **Generar Cartas**: modal con selector de tipo (catálogo Plantillas Word), plantillas en cards y generación según tipo; icono de desvinculación se mantiene. |
| 1.30 | 2026-10-05 | Agent | Modal Generar cartas: pasos numerados, tarjetas más claras y pie con Cancelar / Generar y descargar. |
| 1.29 | 2026-10-05 | Agent | Ficha: botón «Ir al inicio» al final del formulario para subir el scroll. |
| 1.28 | 2026-10-05 | Agent | Cabecera de ficha: nombre destacado, chips de cédula/requisición/vínculo y pill de modo edición. |
| 1.27 | 2026-10-05 | Agent | Modales Consultar cursos / acreditación: más anchos, scroll horizontal/vertical y cabecera con contador. |
| 1.26 | 2026-10-05 | Agent | Ficha: icono Consultar acreditación (modal por cédula) junto a Consultar cursos; mismo permiso view de ficha. |
| 1.25 | 2026-10-05 | Agent | Alta/Gestionar empleado: errores de validación en español, alerta visible, scroll al campo y mensaje si el pendiente ya no existe (sin 404). |
| 1.24 | 2026-10-05 | Agent | Desvinculación bloqueada si hay cruce con Vacaciones/Incapacidad/Permiso (MT-GH-04); forzar solo super-admin. |
| 1.23 | 2026-10-05 | Agent | **Carta de contratación** desde Pendientes (formulario corto; no mueve a En ficha). |
| 1.23 | 2026-10-05 | Agent | Listado Empleados: la tabla ocupa el alto disponible hasta el borde inferior (sin hueco vacío). |
| 1.22 | 2026-10-05 | Agent | Generar/Descargar cartas pasan a iconos en la barra (junto a Historial de vínculos) y en el modal de historial. |
| 1.21 | 2026-10-01 | Agent | Export Excel del listado Pendientes (icono en barra; respeta búsqueda `q`). |
| 1.20 | 2026-10-01 | Agent | Campo obligatorio **Lugar de nacimiento** en create/editar ficha (también `?desde=`); variable Word `[LUGAR_NACIMIENTO]`. Sin cambio en import/Selección. |
| 1.19 | 2026-10-01 | Agent | Pendientes: accion Gestionar Empleado / reingreso pasa a icono en fila. |
| 1.18 | 2026-10-01 | Agent | Alta / Gestionar empleado: toolbar icon-only (Volver/Guardar); seccion Cursos y acreditacion editable desde la creacion. |
| 1.17 | 2026-10-01 | Agent | Ficha: seccion Cursos/acreditacion primero; barra de acciones icon-only (cursos, historial, editar, guardar, desvinculacion, volver). |
| 1.16 | 2026-09-28 | Ficha | Listado Desvinculado: columna Recontratable (Si/No). |
| 1.15 | 2026-09-28 | Ficha | Permiso solo ver: puede abrir la ficha en solo lectura; sin editar/guardar. |
| 1.14 | 2026-09-28 | Ficha | Import masivo repara nombres con `?` (Ñ/Ó); comando `ficha:fix-name-encoding` para datos ya guardados. |
| 1.13 | 2026-09-28 | Ficha | Import masivo: al actualizar una cédula existente, el nombre del listado (`hired_full_name`) se sincroniza con los campos del formulario. |
| 1.12 | 2026-09-18 | Ficha | Listado Empleados: se retira la columna **Fecha contrato** (quedan ingreso y retiro). |
| 1.11 | 2026-09-18 | Ficha | Campo **Fecha desvinculación** visible/editable en el formulario de ficha (junto a fecha ingreso). |
| 1.10 | 2026-09-18 | Ficha | Plantilla import alineada a orden nompr07 (campos nuevos opcionales); normaliza CEDULA/Masculino/Ahorro a C/M/1; plantilla nómina sin cambio. |
| 1.9 | 2026-09-14 | Ficha | Plantilla vacía / import: columnas de primer/segundo apellido y nombre alineadas a BD; `nombre` completo queda opcional. |
| 1.8 | 2026-09-14 | FEAT-031 | Desvincular individual crea seguimiento en Desvinculaciones; Generar cartas marca «tiene carta»; regenerar carta sigue en Ficha (permiso terminate). |
| 1.7 | 2026-08-25 | Ficha | Ciudad de trabajo en perfil (precarga desde requisicion); columnas en plantilla importar/actualizar; sin cambio en plantilla masivos nomina. |
| 1.6 | 2026-08-21 | FEAT-029 | Cartas: modal Generar (seleccion 1/N → docx o zip) en cualquier causal de vinculo cerrado; Descargar ultimo archivo; sin Regenerar aparte; plantillas en tablero Plantillas Word (re-subir legacy Renuncia). |
| 1.5 | 2026-08-13 | FEAT-028 | Formulario ficha completo alineado a plantilla masivos (62 cols): selectores de catalogo, campos obligatorios, referencia de requisicion separada de datos exportables, export/import solo con datos guardados, NIT centro trabajo no exportado, tipo documento CE. |
| 1.4 | 2026-08-03 | FEAT-022 | **Gestionar Empleado** reemplaza a "Agregar a ficha empleados": el boton ahora abre el formulario de ficha precargado desde la requisicion (cedula, nombre y demas datos editables) y el registro solo se mueve a ficha al guardar con **Crear empleado**; se elimino el movimiento inmediato de un clic con confirmacion emergente. |
| 1.3 | 2026-07-31 | Catalogos UI | Pestaña Catalogos: CRUD de payroll_catalog_items para selectores de ficha. |
| 1.2 | 2026-07-31 | Import round-trip | Export plantilla import con datos actuales para actualización masiva. |
| 1.1 | 2026-07-31 | Plantillas | Perfil ficha, export Plantilla masivos, import SJ, catálogos nómina, filtro activos/fechas. |
| 1.0 | 2026-07-30 | FEAT-020 | Version inicial: tablero Ficha empleados, pestaña Empleados, accion Agregar a ficha empleados, export Excel. |
