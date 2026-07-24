# Requisiciones de personal — Guia de usuario

## Objetivo

Permitir solicitar, dar seguimiento y gestionar la contratacion de personal por area, desde la necesidad inicial hasta el cierre por Gestion Humana, incluyendo la captura de la **Estructura del servicio** (horarios, descansos y condiciones del puesto).

## Alcance

Aplica al tablero **Requisiciones** disponible en las areas autorizadas (principalmente Gestion Humana y areas operativas segun permisos).

El usuario puede, segun su perfil:

- **Solicitar** nuevas requisiciones en su area, con el campo obligatorio **Estructura del servicio**
- Consultar **Mis requisiciones** (seguimiento de lo solicitado) y exportar a Excel (incluye la columna Estructura del servicio)
- Ver el **Dashboard** de indicadores del modulo
- **Gestionar** solicitudes de todas las areas (solo Gestion Humana), incluyendo editar la Estructura del servicio
- Administrar **Parametros** (catalogos: cargos, motivos, ciudades, correos de notificacion, etc.)

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
| Cliente | Empresa o entidad para la cual se solicita el personal (desde matriz comercial). |

## Responsabilidades

| Rol / perfil | Responsabilidad |
| --- | --- |
| Solicitante de area | Crear requisiciones en su area (incluye llenar Estructura del servicio); consultar mis requisiciones. |
| Gestion Humana | Gestionar todas las requisiciones; revisar o corregir Estructura del servicio; completar compensacion y cierre; cambiar estados. |
| Administrador | Parametros, permisos de usuarios, correos de notificacion. |
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

### Consultar mis requisiciones

1. Abra **Mis requisiciones** (o Seguimiento segun etiqueta en su instalacion).
2. Use filtros de busqueda, estado, cliente o ciudad.
3. Exporte a Excel si tiene la opcion disponible; el archivo incluye la columna **Estructura del servicio**.

### Gestionar requisiciones (Gestion Humana)

1. Abra la pestaña **Gestion**.
2. Filtre por estado, area o busqueda.
3. Edite una fila para completar compensacion, encargado de seleccion y observaciones de GH.
4. Revise o edite **Estructura del servicio** si necesita corregir horarios, descansos o condiciones del puesto; el campo es obligatorio al guardar. Los cambios quedan en el **Historial de cambios**.
5. Al marcar **Contratado**, complete fecha de contratacion y campos de compensacion obligatorios.
6. Exporte a Excel desde Gestion cuando lo necesite; la exportacion incluye la columna **Estructura del servicio**.
7. Imprima la ficha si necesita documento fisico.

### Administrar parametros

1. Acceda a **Parametros** (permiso correspondiente).
2. Mantenga catalogos: cargos, motivos, ciudades, tipos de programacion, uniformes, encargados de seleccion, correos de notificacion.

## Control de cambios

| Version | Fecha | Autor | Descripcion del cambio |
| --- | --- | --- | --- |
| 1.0 | 2026-07-22 | Alineacion documental | Version inicial guia de usuario |
| 1.1 | 2026-07-24 | Documentador (FEAT-005) | Campo Estructura del servicio en Solicitar y Gestion; export Excel; historial de cambios |
