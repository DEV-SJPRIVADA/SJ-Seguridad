# Requisiciones de personal — Guia de usuario

## Objetivo

Permitir solicitar, dar seguimiento y gestionar la contratacion de personal por area, desde la necesidad inicial hasta el cierre por Gestion Humana.

## Alcance

Aplica al tablero **Requisiciones** disponible en las areas autorizadas (principalmente Gestion Humana y areas operativas segun permisos).

El usuario puede, segun su perfil:

- **Solicitar** nuevas requisiciones en su area
- Consultar **Mis requisiciones** (seguimiento de lo solicitado)
- Ver el **Dashboard** de indicadores del modulo
- **Gestionar** solicitudes de todas las areas (solo Gestion Humana)
- Administrar **Parametros** (catalogos: cargos, motivos, ciudades, correos de notificacion, etc.)

La matriz de **Clientes** se administra en Comercial, no en Parametros de requisiciones.

## Definiciones

| Termino | Significado |
| --- | --- |
| Requisicion | Solicitud de contratacion de una o mas personas para un cargo. |
| Area solicitante | Departamento del usuario que crea la solicitud. |
| Estado solicitada | Requisicion recien creada, pendiente de accion de GH. |
| Estado en gestion | Gestion Humana esta trabajando la solicitud. |
| Estado contratado | Proceso cerrado con contratacion exitosa. |
| Estado cancelada | Solicitud descartada. |
| Gestion Humana (GH) | Equipo que valida, completa datos de compensacion y cierra procesos. |
| Cliente | Empresa o entidad para la cual se solicita el personal (desde matriz comercial). |

## Responsabilidades

| Rol / perfil | Responsabilidad |
| --- | --- |
| Solicitante de area | Crear requisiciones en su area; consultar mis requisiciones. |
| Gestion Humana | Gestionar todas las requisiciones; completar compensacion y cierre; cambiar estados. |
| Administrador | Parametros, permisos de usuarios, correos de notificacion. |
| Coordinador / lider de area | Solicitar segun necesidades del servicio (permiso solicitar). |

## Desarrollo

### Solicitar una requisicion

1. Entre al tablero **Requisiciones** de su area.
2. Abra la pestaña **Solicitar**.
3. Complete las secciones del formulario: motivo, cargo, datos del servicio, perfil requerido y observaciones.
4. Si el motivo es *Cargo nuevo* o *Servicio nuevo*, indique la **cantidad** de personas; en otros motivos el sistema registra una persona por solicitud.
5. Seleccione **Cliente** buscando en la matriz comercial (minimo 2 caracteres), salvo tipo de cliente *Interno*.
6. Revise el checklist lateral y envíe la solicitud.
7. Recibira notificacion por correo cuando GH cambie el estado.

### Consultar mis requisiciones

1. Abra **Mis requisiciones** (o Seguimiento segun etiqueta en su instalacion).
2. Use filtros de busqueda, estado, cliente o ciudad.
3. Exporte a Excel si tiene la opcion disponible en su pantalla.

### Gestionar requisiciones (Gestion Humana)

1. Abra la pestaña **Gestion**.
2. Filtre por estado, area o busqueda.
3. Edite una fila para completar compensacion, encargado de seleccion y observaciones de GH.
4. Al marcar **Contratado**, complete fecha de contratacion y campos de compensacion obligatorios.
5. Imprima la ficha si necesita documento fisico.

### Administrar parametros

1. Acceda a **Parametros** (permiso correspondiente).
2. Mantenga catalogos: cargos, motivos, ciudades, tipos de programacion, uniformes, encargados de seleccion, correos de notificacion.

## Control de cambios

| Version | Fecha | Autor | Descripcion del cambio |
| --- | --- | --- | --- |
| 1.0 | 2026-07-22 | Alineacion documental | Version inicial guia de usuario |
