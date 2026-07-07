# Modulo Requisiciones

## Objetivo

Gestionar el flujo de requisicion de personal por area, desde la solicitud inicial del usuario hasta la gestion operativa y el cambio de estados por parte de gestion humana.

## Alcance actual

- Tablero interno de `Requisiciones` disponible por area segun permiso `view.board.{area}.requisiciones`
- Subtableros internos:
  - `Dashboard`
  - `Solicitar`
  - `Seguimiento`
  - `Gestion`
  - `Parametros`
- Creacion de requisiciones sobre el modulo autorizado, incluso si el `area_key` del usuario es diferente
- Edicion solo para gestion humana o usuarios con permiso operativo equivalente
- Historial de cambios de estado
- Catalogos iniciales para cargos, motivos, clientes, ciudades, tipos de cliente y tipos de programacion

## Reglas de negocio actuales

- El usuario puede solicitar en cualquier modulo de requisiciones que tenga autorizado por permiso explicito
- El tablero `Seguimiento` es solo lectura para usuarios solicitantes y muestra requisiciones del area propia del usuario
- El filtro `Solo mis solicitudes` permite reducir la vista del area a lo creado por el usuario autenticado
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

## Orden de campos y Matriz de Visibilidad (Sprint 2026-04-29)

El formulario ha sido expandido para incluir toda la matriz de compensación y seguimiento, con visibilidad restringida según el rol:

### Campos visibles para Solicitantes (Perfil de área)
1. Lider / solicitante (readonly)
2. Area solicitante (readonly)
3. Cargo solicitado
4. Sexo
5. Cantidad
6. Cedula / Nombre a quien reemplaza (opcional)
7. Area operativa
8. Motivo
9. Cliente
10. Ciudad
11. Tipo de cliente
12. Tipo de programacion
13. Perfil requerido
14. Dotacion requerida
15. Centro de costo
16. Observaciones del solicitante

### Campos exclusivos para Gestión Humana (GH)
17. **Compensación**: Tipo de contrato, Duración, Salario Base, Auxilios (Transporte, Movilidad), Bonificaciones, Contrato de Arrendamiento.
18. **Seguimiento**: Encargado de selección (Recruiter).
19. **Cierre**: Cantidad contratada, Fecha de contratación, Observaciones de GH.

## Identificación Visual y UI

- **Estados por colores**: Se implementó un sistema de indicadores visuales (status-pills) en tablas e historial:
  - 🔵 `solicitada`: Pendiente.
  - 🟡 `en_gestion`: En proceso por GH.
  - 🟢 `contratado`: Proceso finalizado con éxito.
  - 🔴 `cancelada`: Solicitud descartada.
- **Layout Fijo**: Las barras de navegación (Módulo y Sub-tableros) permanecen fijas en la parte superior, optimizando el desplazamiento en pantallas pequeñas y formularios largos.
- **Dashboard Compacto**: Indicadores KPI en una sola fila para maximizar el espacio de las tablas de datos.

## Rutas

Definidas en [`routes/modules/requisitions.php`](c:/laragon/www/SJSEGURIDAD/routes/modules/requisitions.php):

- `GET /requisitions/{module}/dashboard`
- `GET /requisitions/{module}/solicitar`
- `POST /requisitions/{module}/solicitar`
- `GET /requisitions/{module}/seguimiento`
- `GET /requisitions/{module}/gestion`
- `GET /requisitions/{module}/gestion/{requisition}/editar`
- `GET /requisitions/{module}/gestion/{requisition}/imprimir`
- `PATCH /requisitions/{module}/gestion/{requisition}`
- `GET /requisitions/{module}/parametros`
- `POST /requisitions/{module}/parametros/{type}`
- `PATCH /requisitions/{module}/parametros/{type}/{parameterId}`
- `DELETE /requisitions/{module}/parametros/{type}/{parameterId}`

## Permisos relacionados

- `view.board.{area}.requisiciones`
- `requisitions.tab.dashboard`
- `requisitions.tab.solicitar`
- `requisitions.tab.seguimiento`
- `requisitions.tab.gestion`
- `manage.requisitions`
- `manage.requisition.parameters`
- `manage.area.gestion_humana` (Otorga visibilidad completa de campos y acceso a tablero GH)
- `manage.users`

## Tablas implicadas

- `personal_requisitions` (Actualizada con 12 campos nuevos de compensación y cierre)
- `personal_requisition_status_logs`
- `requisition_positions`
- `requisition_request_reasons`
- `requisition_clients`
- `requisition_cities`
- `requisition_client_types`
- `requisition_programming_types`

## Riesgos

- **Visibilidad Sensible**: Los campos de compensación son críticos; cualquier cambio en la lógica de `showHumanResourcesFields` puede exponer salarios a solicitantes.
- **Validación de Cierre**: `hired_quantity` y `hiring_date` son requeridos para estados finales.

## Correcciones aplicadas (Sprint final 2026-04)

- **Ampliación de Modelo**: Migración `2026_04_29_144000` ejecutada para campos de compensación y reclutamiento.
- **Matriz de Visibilidad**: Formulario `partials/form-fields.blade.php` reorganizado por secciones de acceso controlado.
- **Sistema de Colores**: Implementación de clases `.status-pill--req-*` en CSS y vistas.
- **Navegación Fija**: Reestructuración de `app.blade.php` y slots de cabecera para evitar superposición de menús.
- **Dashboard KPI**: Compactación de indicadores en 4 columnas fijas en una sola fila.
- **Notificaciones**: Migración a sistema de Toasts dinámicos en la esquina inferior derecha.
