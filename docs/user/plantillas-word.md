# Plantillas Word — Guia de usuario

> Documentacion operativa para usuarios finales. Ubicacion: `docs/user/plantillas-word.md`.
> **Orden obligatorio de secciones.** Uso de pantallas (tablero de administracion).

## Objetivo

Permitir a Gestión Humana administrar en un solo lugar los **tipos de documento** y las **plantillas Word** (.docx) que luego se usan al generar cartas (desvinculación desde la ficha del empleado, cartas de vacaciones desde Cliente interno, cartas de notificación desde el tablero Cartas Notificación, y otros tipos según el flujo).

## Alcance

Aplica al tablero **Plantillas Word** del área **Gestión Humana** (menú lateral). Según su perfil, el usuario puede:

- Ver tipos de documento y la lista de plantillas.
- Crear, editar o desactivar tipos; agregar, reemplazar o eliminar plantillas; descargar la plantilla maestra.

**No incluye:** generar o descargar cartas ya rellenadas (eso se hace en **Ficha empleados** para desvinculación, en **Cliente interno → Cartas Vacaciones** para vacaciones, o en el tablero **Cartas Notificación**). Tampoco hay editor de Word dentro de la aplicación: se sube un archivo ya preparado.

**Importante tras la actualización:** las plantillas antiguas del paquete de Renuncia **no** se migraron automáticamente. Hay que **volver a subirlas** en este tablero, asociadas al tipo **Desvinculacion**.

El sistema trae de fábrica, entre otros, los tipos **Desvinculacion**, **Cartas Vacaciones** y **Cartas Notificación**.

## Definiciones

| Termino | Significado |
| --- | --- |
| Tipo de documento | Categoria del catalogo (por ejemplo **Desvinculacion**, **Cartas Vacaciones** o **Cartas Notificación**) que clasifica las plantillas. |
| Plantilla Word | Archivo `.docx` con variables `${CLAVE}` (ej. `${NOMBRE_COMPLETO}`, `${DOCUMENTO}`) que el sistema rellena al generar una carta. |
| Reemplazar plantilla | Cambiar solo el archivo Word; la etiqueta y el tipo se mantienen. |
| Plantilla maestra | El archivo original guardado en el tablero (no la carta ya generada para un empleado). |
| Cartas Vacaciones | Tipo de plantilla usado desde Cliente interno para generar cartas de vacaciones en lote. Debe haber **exactamente una** plantilla activa con archivo. |
| Cartas Notificación | Tipo de plantilla usado desde el tablero **Cartas Notificación** para generar cartas en lote. Debe haber **exactamente una** plantilla activa con archivo. |

## Responsabilidades

| Rol / perfil | Responsabilidad en este modulo |
| --- | --- |
| Administrador de plantillas (permiso de administrar Plantillas Word) | Mantener tipos y subir/reemplazar/eliminar plantillas; re-subir las de desvinculacion tras el cambio de sistema; dejar **una sola** plantilla activa de Cartas Vacaciones y **una sola** de Cartas Notificación. |
| Consulta de plantillas (solo ver) | Revisar el listado y descargar plantillas maestras si lo necesita. |
| Operador de desvinculacion (Ficha empleados) | No administra este tablero; genera y descarga cartas desde la ficha del empleado. |
| Operador de Cartas Vacaciones (Cliente interno) | No administra este tablero; genera desde Cliente interno usando la plantilla del tipo Cartas Vacaciones. |
| Operador de Cartas Notificación | No administra este tablero; genera desde el tablero Cartas Notificación usando la plantilla de ese tipo. |
| Administrador de usuarios | Asignar el tablero y los permisos de ver/administrar Plantillas Word a quien corresponda. |

## Desarrollo

### Abrir el tablero

1. En el area **Gestion Humana**, pulse **Plantillas Word** en el menu lateral (solo visible si tiene permiso de tablero).
2. Vera las pestanas **Tipos de documento** y **Plantillas** (una tabla por pestana).
3. Use **Tipos de documento** para el catalogo de tipos; use **Plantillas** para subir o reemplazar archivos Word.

### Administrar tipos de documento

1. Para **agregar** un tipo: complete codigo, nombre, orden y estado activo; confirme.
2. Para **editar**: cambie nombre, orden o activo segun necesite. Evite cambiar el codigo del tipo **Desvinculacion**: si se altera, las cartas en ficha pueden dejar de encontrar plantillas.
3. Para **eliminar**: solo si el tipo no tiene plantillas asociadas. Si ya tiene plantillas, **desactívelo** en lugar de borrarlo.

El sistema trae de fábrica tipos como **Desvinculacion** (cartas al desvincular), **Cartas Vacaciones** (lote desde Cliente interno) y **Cartas Notificación** (lote desde el tablero Cartas Notificación).

### Agregar una plantilla

1. En el bloque **Plantillas**, indique la **etiqueta** (nombre visible), elija el **tipo** (activo) y seleccione un archivo **.docx**.
2. Confirme. La plantilla aparece en la lista con su tipo.
3. Para cartas de retiro, use el tipo **Desvinculacion** y variables `${…}` del listado de apoyo (icono **Ver variables** / llaves): puede filtrar por clave o descripción; copie/pegue `${NOMBRE_COMPLETO}`, `${DOCUMENTO}`, `${FECHA_TERMINACION_VINCULO}` (mayúsculas en la carta), `${FECHA_TERMINACION_VINCULO_MINUSCULAS}`, `${FECHA_ENTREGA_DOTACION}` (terminación + 3 días sin domingos ni festivos), etc.
4. Para cartas de vacaciones, use el tipo **Cartas Vacaciones** y las variables de la categoría **Cartas vacaciones** (por ejemplo `${FECHA_INICIO}`, `${FECHA_FIN}`, `${FECHA_REINTEGRO}`, `${PERIODOS}`, `${DIAS_DISFRUTADOS}`, además de `${CEDULA}`, `${NOMBRE_COMPLETO}`, `${FIRMA}`). Deje **solo una** plantilla activa de ese tipo.
5. Para cartas de notificación, use el tipo **Cartas Notificación** y las variables de la categoría **Cartas notificación** (`${CEDULA}`, `${NOMBRE_COMPLETO}`, `${DURACION_CONTRATO}`, `${FECHA_TERMINACION}`, `${FIRMA}`, `${CARGO_FIRMA}`, `${FECHA}`). La duración (6 o 12) y la fecha de terminación salen de la grilla del tablero Cartas Notificación. Deje **solo una** plantilla activa de ese tipo.

### Consultar variables de plantilla

1. En la pestaña **Plantillas**, pulse el icono de **Ver variables** (llaves) en la cabecera.
2. Use el campo **Filtrar** para buscar por categoría, clave (`DOCUMENTO`) o descripción.
3. El contador muestra cuántas variables coinciden; el icono X limpia el filtro.

### Filtrar plantillas

1. En la pestana **Plantillas**, use la barra de filtros: busqueda por etiqueta, tipo de documento y estado del archivo (cargada / pendiente).
2. Pulse el icono de buscar para aplicar; el icono X limpia los filtros activos.

### Reemplazar, editar o eliminar una plantilla

1. **Editar:** use el icono de lápiz en la fila para cambiar **etiqueta**, **tipo** u **orden** (sin tocar el archivo).
2. **Reemplazar:** elija solo el nuevo archivo `.docx` de esa fila. La etiqueta y el tipo no cambian.
3. **Eliminar:** confirme cuando el sistema lo pida. Se quita la plantilla del listado (ya no aparecerá al generar cartas).
4. **Descargar:** obtiene la plantilla maestra guardada (sin datos de un empleado).

Al **agregar** plantilla puede **arrastrar y soltar** el `.docx` sobre la zona de carga, o seleccionarlo con clic.

### Relacion con Ficha empleados

1. Tras subir al menos una plantilla de tipo **Desvinculacion**, un usuario con permiso de desvinculacion puede, en la ficha de un empleado ya desvinculado, pulsar **Generar cartas**, elegir una o varias plantillas y descargar el resultado.
2. El detalle de ese flujo esta en la guia de usuario de **Ficha empleados**.

### Relación con Cliente interno (Cartas Vacaciones)

1. Suba **una** plantilla del tipo **Cartas Vacaciones** (si hay cero o más de una activa con archivo, la generación en Cliente interno se bloquea).
2. Un usuario con permiso para generar Cartas Vacaciones arma el lote en **Cliente interno → Cartas Vacaciones** y descarga el Word o el ZIP.
3. El detalle operativo está en la guía de usuario de **Cliente interno**.

### Relación con Cartas Notificación

1. Suba **una** plantilla del tipo **Cartas Notificación** (si hay cero o más de una activa con archivo, la generación en ese tablero se bloquea).
2. Un usuario con permiso de generar arma el lote en **Cartas Notificación** y descarga el Word o el ZIP.
3. El detalle operativo está en la guía de usuario de **Cartas Notificación**.

## Control de cambios

| Version | Fecha | Autor | Descripcion del cambio |
| --- | --- | --- | --- |
| 1.10 | 2026-10-08 | Agent | Variables `${FECHA_TERMINACION_VINCULO_MINUSCULAS}` y `${FECHA_ENTREGA_DOTACION}` (+3 días sin domingos/festivos). |
| 1.9 | 2026-10-08 | Documentador | FEAT-044: tipo seed **Cartas Notificación**; regla de una plantilla activa; vínculo con el tablero Cartas Notificación. |
| 1.8 | 2026-10-07 | Documentador | FEAT-043: tipo seed **Cartas Vacaciones**; regla de una plantilla activa; vínculo con Cliente interno. |
| 1.7 | 2026-10-05 | Agent | Modal **Editar plantilla**: cabecera con icono, campos en rejilla y pie Cancelar / Guardar cambios. |
| 1.6 | 2026-10-05 | Agent | Plantillas: editar etiqueta/tipo/orden; carga .docx con arrastrar y soltar. |
| 1.5 | 2026-10-05 | Agent | Modal **Variables disponibles**: filtro por clave/descripción y contador de resultados. |
| 1.4 | 2026-10-01 | Agent | Variable de apoyo `[LUGAR_NACIMIENTO]` (lugar de nacimiento del perfil de ficha). |
| 1.3 | 2026-09-23 | UI | Filtros en pestana Plantillas (etiqueta, tipo, estado de archivo). |
| 1.2 | 2026-09-23 | UI | Redisenio visual: subnav en header, formularios por seccion, zona de carga `.docx` y acciones de fila compactas. |
| 1.1 | 2026-08-21 | UI | Pestanas **Tipos de documento** y **Plantillas** (una tabla por pestana). |
| 1.0 | 2026-08-21 | FEAT-029 | Version inicial: tablero Plantillas Word (tipos + plantillas); re-subida obligatoria de plantillas de Renuncia; permisos propios del tablero. |
