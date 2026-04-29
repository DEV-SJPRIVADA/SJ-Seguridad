# Modulo Requisiciones

## Objetivo

Gestionar el flujo de requisicion de personal por area, desde la solicitud inicial del usuario hasta la gestion operativa y el cambio de estados por parte de gestion humana.

## Alcance actual

- Tablero interno de `Requisiciones` disponible por area segun permiso `view.board.{area}.requisiciones`
- Subtableros internos:
  - `Dashboard`
  - `Solicitar`
  - `Gestion`
  - `Parametros`
- Creacion de requisiciones sobre el area propia del usuario autenticado
- Edicion solo para gestion humana o usuarios con permiso operativo equivalente
- Historial de cambios de estado
- Catalogos iniciales para cargos, motivos, clientes, ciudades, tipos de cliente y tipos de programacion

## Reglas de negocio actuales

- El usuario solicita requisiciones solo para su `area_key`
- `leader_name` y `requesting_area_key` se toman del usuario autenticado
- `Cliente` y `Motivo` se seleccionan desde parametros
- `Centro de costo` es texto libre
- Existen dos observaciones:
  - `requester_observation`
  - `human_resources_observation`
- Los estados permitidos en V1 son:
  - `solicitada`
  - `en_gestion`
  - `contratado`
  - `cancelada`
- Despues de creada, la requisicion ya no se modifica desde el flujo del solicitante; solo gestion humana puede hacerlo
- Usuarios con `manage.users` o `manage.area.gestion_humana` pueden crear solicitudes en cualquier modulo sin necesidad de tener `area_key` coincidente

## Orden de campos en el formulario de solicitud

1. Lider / solicitante (readonly, tomado del usuario autenticado)
2. Area solicitante (readonly, tomado del modulo)
3. Cargo solicitado
4. Sexo
5. Cantidad
6. Cedula a quien reemplaza
7. Nombre completo a quien reemplaza
8. Area operativa
9. Motivo
10. Cliente
11. Ciudad
12. Tipo de cliente
13. Tipo de programacion
14. Perfil requerido
15. Dotacion requerida
16. Centro de costo
17. Observaciones del solicitante
18. (Solo en edicion por gestion humana) Estado y Observaciones de gestion humana

## Rutas

Definidas en [`routes/web.php`](c:/laragon/www/SJSEGURIDAD/routes/web.php):

- `GET /requisitions/{module}/dashboard`
- `GET /requisitions/{module}/solicitar`
- `POST /requisitions/{module}/solicitar`
- `GET /requisitions/{module}/gestion`
- `GET /requisitions/{module}/gestion/{requisition}/editar`
- `PATCH /requisitions/{module}/gestion/{requisition}`
- `GET /requisitions/{module}/parametros`
- `POST /requisitions/{module}/parametros/{type}`

## Permisos relacionados

- `view.board.{area}.requisiciones`
- `manage.requisitions`
- `manage.requisition.parameters`
- `manage.area.gestion_humana`
- `manage.users`

## Tablas implicadas

- `personal_requisitions`
- `personal_requisition_status_logs`
- `requisition_positions`
- `requisition_request_reasons`
- `requisition_clients`
- `requisition_cities`
- `requisition_client_types`
- `requisition_programming_types`

## Riesgos

- Si el usuario no tiene `area_key`, no puede usar el tablero `Solicitar` (excepto usuarios con `manage.users` o `manage.area.gestion_humana`)
- Cambios en parametros impactan formularios, filtros y tableros del modulo
- Cambios en navegacion deben revisar la resolucion del tablero `Requisiciones` en `AppServiceProvider`

## Correcciones aplicadas (sprint 2026-04)

- **Tab Solicitar no aparecia**: La condicion `area_key === moduleKey` bloqueaba a super-admin y administrador. Ahora usuarios con `manage.users` o `manage.area.gestion_humana` siempre ven el tab.
- **403 al acceder al dashboard con `view.area.*`**: `authorizeBoardAccess` no aceptaba permisos de area. Ahora incluye `view.area.{module}` y `manage.area.{module}` como condiciones validas.
- **Variables indefinidas en edicion**: El `@include` de `form-fields` en `edit.blade.php` no pasaba `$catalogs`, `$sexOptions`, `$areaOptions` ni `$statusLabels`. Corregido pasando todas las variables al include explicitamente.
- **Codigo de requisicion inconsistente**: `nextCode()` usaba `max('id')` que falla con registros eliminados. Ahora parsea el ultimo codigo del anio actual por query ordenada.
