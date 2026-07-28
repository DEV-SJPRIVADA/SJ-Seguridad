# Requisiciones de personal — Guia de usuario

## Objetivo

Permitir solicitar, dar seguimiento y gestionar la contratacion de personal por area, desde la necesidad inicial hasta el cierre por Gestion Humana, incluyendo la captura de la **Estructura del servicio** (horarios, descansos y condiciones del puesto).

## Alcance

Aplica al tablero **Requisiciones** disponible en las areas autorizadas (principalmente Gestion Humana y areas operativas segun permisos).

El usuario puede, segun su perfil:

- **Solicitar** nuevas requisiciones en su area, con el campo obligatorio **Estructura del servicio**
- Consultar **Mis requisiciones** (seguimiento de lo solicitado) y exportar a Excel con el detalle completo de la solicitud
- Ver el **Dashboard** consolidado (todas las areas) con KPIs: Total, Solicitadas, En gestion, Contratadas y Canceladas, y graficos interactivos unificados (ApexCharts)
- **Gestionar** solicitudes de todas las areas (solo Gestion Humana), incluyendo editar la Estructura del servicio
- Administrar **Parametros** (catalogos: cargos, motivos, ciudades, correos de notificacion, etc.)
- En **Gestion humana** → **Parametros**, activar **encargados de seleccion** (usuarios del area) para que aparezcan como **Reclutador** al gestionar solicitudes

La matriz de **Clientes** se administra en Comercial, no en Parametros de requisiciones.

## Definiciones

| Termino | Significado |
| --- | --- |
| Requisicion | Solicitud de contratacion de una o mas personas para un cargo. |
| Area solicitante | Departamento del usuario que crea la solicitud. |
| Estructura del servicio | Descripcion de horarios, descansos y condiciones del puesto a tener en cuenta al cubrir la vacante. |
| Estado solicitada | Requisicion recien creada, pendiente de accion de GH. |
| Estado en gestion | Gestion Humana esta trabajando la solicitud. |
| Estado contratado | Proceso cerrado con contratacion exitosa. |
| Estado cancelada | Solicitud descartada. |
| Gestion Humana (GH) | Equipo que valida, completa datos de compensacion y cierra procesos. |
| Encargado de seleccion / Reclutador | Persona de GH responsable del proceso de seleccion en una requisicion; se elige en Gestion desde usuarios habilitados en Parametros. |
| Cliente | Empresa o entidad para la cual se solicita el personal (desde matriz comercial). |

## Responsabilidades

| Rol / perfil | Responsabilidad |
| --- | --- |
| Solicitante de area | Crear requisiciones en su area (incluye llenar Estructura del servicio); consultar mis requisiciones. |
| Gestion Humana | Gestionar todas las requisiciones; revisar o corregir Estructura del servicio; completar compensacion y cierre; cambiar estados. |
| Administrador | Parametros, permisos de usuarios, correos de notificacion; en GH, toggles de encargados de seleccion. |
| Coordinador / lider de area | Solicitar segun necesidades del servicio (permiso solicitar). |

## Desarrollo

### Solicitar una requisicion

1. Entre al tablero **Requisiciones** de su area.
2. Abra la pestaña **Solicitar**.
3. Complete las secciones del formulario: motivo, cargo, datos del servicio, perfil requerido, dotacion y observaciones.
4. En la seccion de perfil y dotacion, complete **Estructura del servicio** con horarios, descansos y condiciones del puesto. Es obligatorio; no podra enviar la solicitud si queda vacio.
5. Si el motivo es *Cargo nuevo* o *Servicio nuevo*, indique la **cantidad** de personas; en otros motivos el sistema registra una persona por solicitud.
6. Si el motivo es *Reemplazo* o *Movimiento interno*, complete **cedula** y **nombre completo** de la persona involucrada.
6. Seleccione **Cliente** buscando en la matriz comercial (minimo 2 caracteres), salvo tipo de cliente *Interno*.
7. Revise el checklist lateral y envíe la solicitud.
8. Recibira notificacion por correo cuando GH cambie el estado.

### Consultar el dashboard

1. Abra la pestaña **Dashboard** del tablero Requisiciones.
2. Revise los KPIs (Total, Solicitadas, En gestion, Contratadas, Canceladas).
3. Use los filtros disponibles; la pantalla se actualiza al cambiarlos.
4. Revise los graficos de tendencia, estado, ciudad y cliente.

### Consultar mis requisiciones

1. Abra **Mis requisiciones** (o Seguimiento segun etiqueta en su instalacion).
2. Use filtros de busqueda, estado, cliente o ciudad.
3. Opcional: indique **Fecha inicio** y **Fecha fin** (fecha de solicitud) y pulse **Buscar** para acotar la lista.
4. Exporte a Excel si tiene la opcion disponible; el archivo trae todos los campos de la requisicion segun los filtros activos.

### Gestionar requisiciones (Gestion Humana)

1. Abra la pestaña **Gestion**.
2. Filtre por estado, busqueda y rango de **Fecha inicio / Fecha fin** (fecha de solicitud); pulse **Buscar**.
3. En la tabla, la columna **Reclutador** (despues de **Reemplaza a**) muestra el encargado asignado o **sin asignar** si aun no hay reclutador.
4. Edite una fila para completar compensacion, **Reclutador** (encargado de seleccion) y observaciones de GH.
5. Revise o edite **Estructura del servicio** si necesita corregir horarios, descansos o condiciones del puesto; el campo es obligatorio al guardar. Los cambios quedan en el **Historial de cambios**.
6. Al marcar **Contratado**, complete fecha de contratacion y campos de compensacion obligatorios.
7. Exporte a Excel; incluye todos los campos de la requisicion (incluida compensacion) segun los filtros activos.
8. Imprima la ficha si necesita documento fisico.

### Administrar parametros

1. Acceda a **Parametros** (permiso correspondiente).
2. Mantenga catalogos: cargos, motivos, ciudades, tipos de programacion, uniformes, **Correos de notificacion** y **Tipos de notificacion** (asignar correos por tipo de aviso).

### Tipos de notificacion (solo Gestion humana → Parametros)

1. Mantenga el catalogo de direcciones en **Correos de notificacion**.
2. Abra **Tipos de notificacion**.
3. Para **Nueva requisicion** y **Autorizacion requisicion cargo nuevo**, marque los correos que deben recibir cada aviso y guarde.

### Autorizar requisiciones cargo nuevo (Gerencia)

1. Ingrese con usuario que tenga permiso **Autorizar cargo nuevo (gerencia)** (rol administrador).
2. Abra **Requisiciones → Gestion humana → Autorizacion gerencia**.
3. Revise la lista (solo pendientes). Abra **Revisar**, **Autorizar** o **Rechazar** (comentario obligatorio al rechazar).
4. Tras autorizar, la solicitud pasa a **Solicitada** y Gestion humana puede continuar. Si rechaza, queda **Cancelada**.

### Activar encargados de seleccion (solo Gestion humana)

Esta seccion aparece **unicamente** en el tablero **Requisiciones** del area **Gestion humana**, pestaña **Parametros**.

1. Entre a **Requisiciones** → **Gestion humana** → **Parametros**.
2. Abra la tarjeta **Encargados de seleccion**.
3. Revise la tabla de usuarios activos del area Gestion humana (nombre y correo).
4. Use el **interruptor (toggle)** en la columna **Encargado** para activar o desactivar a cada persona.
5. Solo los usuarios con toggle **activo** aparecen en la lista **Reclutador** al editar requisiciones en **Gestion** (cualquier area solicitante).

**Despues de una actualizacion del sistema:** si antes los encargados se registraban en un catalogo aparte, las asignaciones antiguas pueden quedar vacias hasta que GH vuelva a activar toggles y reasigne Reclutador donde corresponda. Las requisiciones que ya tenian nombre guardado en texto pueden seguir mostrando ese nombre hasta que se elija un usuario en el select.

**Al desactivar un toggle:** esa persona ya no se ofrece para **nuevas** asignaciones, pero las requisiciones donde ya figuraba como Reclutador siguen mostrando su nombre en detalle, historial, exportacion e impresion.

**Nota:** el permiso relacionado puede verse en Administracion de usuarios, pero la forma oficial de habilitar encargados es el toggle en Parametros de Gestion humana (salvo ajustes puntuales por super-administrador).

## Control de cambios

| Version | Fecha | Autor | Descripcion del cambio |
| --- | --- | --- | --- |
| 1.0 | 2026-07-22 | Alineacion documental | Version inicial guia de usuario |
| 1.2 | 2026-07-24 | AgentSj / Feature | Export Excel completo + rango fechas en Gestion y Seguimiento | FEAT-006 |
| 1.3 | 2026-07-27 | FEAT-010 | Dashboard con graficos ApexCharts unificados; seccion Consultar el dashboard |
| 1.4 | 2026-07-28 | FEAT-011 | Encargados de seleccion por toggles en Parametros GH; Reclutador desde usuarios habilitados; checklist post-actualizacion |
