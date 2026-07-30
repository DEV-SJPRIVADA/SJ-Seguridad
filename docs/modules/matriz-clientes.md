# Modulo Matriz comercial (MT-CO-01)

## Objetivo

Digitalizar la matriz comercial MT-CO-01 con tableros en Comercial:

- **Dashboard**: indicadores interactivos de clientes/servicios
- **Clientes**: maestro por NIT
- **Servicios**: contratos/portafolios vinculados a un cliente (seleccion obligatorio al crear)

## Alcance V1

- Area exclusiva: `comercial`
- Boards (sidebar):
  - `dashboard` (etiqueta: **Dashboard**) — redirige a `comercial/dashboard`
  - `gestion_clientes` (etiqueta: **Gestion Clientes**) — pestañas **Clientes** y **Servicios** (`.module-tab`, partial `gestion-clientes-subnav`)
- Pestañas del tablero Gestion Clientes (FEAT-017): rutas sin cambio (`comercial/clientes/*`, `comercial/servicios/*`); visibilidad por pestaña con permisos legacy `view.board.comercial.matriz_clientes` y `view.board.comercial.servicios_comerciales`
- **Parametros (FEAT-018):** pestaña `comercial/parametros` — CRUD de sectores, tipos de cliente/servicio y portafolios (`commercial_portfolios` con `slug`); permiso `manage.commercial.parameters` o `comercial.matriz.manage`
- Dashboard: filtros portafolio/ciudad (stock); año/mes para **clientes nuevos** (`created_at`) y tendencia de altas (`contract_start`); KPIs (total clientes, clientes nuevos, activos, por vencer ≤30, vencidos, inactivos) y **ApexCharts** via Vite (`resources/js/comercial-dashboard-charts.js` + defaults `resources/js/charts/apex-defaults.js`). **FEAT-010:** Chart.js retirado; misma libreria que GH y Operaciones.
- Listado clientes: NIT, cliente, ciudad, portafolio(s), tipos de servicio, conteos, **Estado** (**Activo** = al menos un servicio con `is_active = true`; **Inactivo** = todos los servicios con `is_active = false` o sin servicios); filtros GET `q`, `city`, `status=active|inactive`
- Listado servicios (FEAT-016): columnas NIT, Cliente, Contrato, Tipo servicio, Portafolio, Asesor, Inicio, Fin, **Estado**, Acciones; estado del servicio = baja logica (`is_active`) + contrato; filtros `vigencia=expiring|expired` (30 dias, solo contrato activo)
- Modelo:
  - `commercial_clients` (NIT unico, datos maestros; **vencimiento documentacion** `documentation_expires_on` + `alert_days_before`)
  - `commercial_client_document_items` (estado por documento, 10 filas por cliente)
  - `commercial_services` (N:1 con cliente; portafolio, contrato, **`is_active`** baja logica, contacto operativo — **sin** checklist)
- Portafolios: slugs en `commercial_portfolios` (`seg_fisica`, `monitoreo`, `ocasionales`, `inactivos` por defecto); columna `commercial_services.portfolio` sigue siendo slug string
- Catalogos: `commercial_sectors`, `commercial_client_types`, `commercial_service_types`, `commercial_portfolios`
- Checklist documental por **cliente** (estados por documento; vencimiento y dias de anticipacion unicos por NIT; sin adjuntos)
- Badge de vencimiento documental del cliente en pantalla checklist; filtros compactos (`req-manage-filters`) con `q`, `city`, `doc_vigencia=expiring|expired`; selects/pills de estado por documento con color (OK verde, Pendiente rojo, Incompleto naranja, N/A amarillo, X rojo intenso)
- Filtros `vigencia=expiring|expired` en servicios: solo `contract_end` (excluye `is_active = false`)
- **Estado servicio** (etiquetas): **Inactivo** (`is_active = false`) → **Vencido** → **Por vencer** (30 dias) → **Activo**. Inactivar/Activar no cambia portafolio.
- **Estado cliente**: **Activo** si existe al menos un servicio con `is_active = true` (independiente de vencimiento de contrato).
- **Notificacion correo (FEAT-015):** comando diario `comercial:send-documentation-notification-digest` (06:00 `America/Bogota`) envia **digest** a destinatarios del tipo `comercial` / `documentation_expiring` en Admin → Configuracion de notificaciones; misma regla que checklist «Por vencer» / vencida; dedupe en `commercial_client_documentation_notification_logs` (una vez por ciclo `expiring` y una vez `expired` por fecha de vencimiento)
- **Notificacion correo (FEAT-019):** comando diario `comercial:send-service-contract-notification-digest` (06:00 `America/Bogota`) envia **digest** a destinatarios del tipo `comercial` / `service_contract_expiring`; misma regla que filtro servicios `vigencia=expiring` (30 dias, solo activos); dedupe en `commercial_service_contract_notification_logs` (una vez por servicio y `contract_end`)

## Fuera de V1

- BORRADOR SVC / facturacion / consecutivos
- Sync automatico con `requisition_clients` al crear/editar requisiciones (`CommercialClientBridge`)
- Adjuntos PDF / documentos Calidad
- Historial de envios de correo en admin

## Importacion desde Excel

El archivo **MT-CO-01 Matriz de clientes.xlsx** no se versiona en el repositorio (datos sensibles / copia maestra fuera de Git). Guardelo en una ruta local o de red accesible al servidor.

Comando (ruta **obligatoria**):

```powershell
php artisan comercial:import-mt-co-01 "C:\ruta\MT-CO-01 Matriz de clientes.xlsx"
```

Opciones:

- `--fresh` elimina clientes/servicios comerciales y vuelve a cargar (destructivo)

Importa hojas `SEG. FISICA`, `MONITOREO`, `OCASIONALES`, `INACTIVOS`.

- Cliente: upsert por NIT normalizado
- Servicio: upsert por cliente + portafolio + numero de contrato
- Catalogos sector/tipo se crean si no existen
- Implementacion: `App\Services\Comercial\MtCo01Importer` + `comercial:import-mt-co-01`
- Al importar/guardar se descartan duraciones > 600 meses y fechas de contrato anteriores a 1980 (artefactos de Excel); al editar un servicio con datos corruptos, el guardado los normaliza automaticamente
- Si el Excel trae columnas de vencimiento documental reconocidas (ej. `vencimiento rut`, `fecha vencimiento fo-co-02`, `vencimiento contrato documental`), se asigna `*_tracks_expiry=true` y `*_expires_on`. No confundir con `fecha de terminacion contrato` (`contract_end`). Sin columna reconocida, no se inventan fechas.

## Rutas

`routes/areas/comercial.php`:

### Dashboard

- `GET /comercial/dashboard` (`comercial.dashboard`) — KPIs y graficos; el board Dashboard de Comercial redirige aqui

### Clientes — prefijo `comercial/clientes`

- `GET /` listado
- `GET /checklist-documental` checklist documental (FEAT-014)
- `GET /checklist-documental/exportar` export Excel checklist
- `PATCH /{client}/checklist-documental` actualizar estados + vencimiento/dias

### Job / CLI (documentacion comercial)

- `php artisan comercial:send-documentation-notification-digest` — digest diario (scheduler 06:00 Bogota)
- Opciones: `--date=Y-m-d`, `--dry-run`
- Destinatarios: `NotificationConfigService` → tipo `comercial` / `documentation_expiring` (`config/notifications.php`)

### Job / CLI (contratos de servicio)

- `php artisan comercial:send-service-contract-notification-digest` — digest diario (scheduler 06:00 Bogota)
- Opciones: `--date=Y-m-d`, `--dry-run`
- Destinatarios: `NotificationConfigService` → tipo `comercial` / `service_contract_expiring` (`config/notifications.php`)

### Clientes — prefijo (continuacion)

- `GET|POST /crear` alta
- `GET /{client}` ficha (servicios relacionados, solo lectura/enlaces)
- `GET|PATCH /{client}/editar`

### Servicios — prefijo `comercial/servicios`

- `GET /` listado independiente
- `GET|POST /crear` alta con **busqueda de cliente** por nombre/NIT (`commercial_client_id`)
- Endpoint auxiliar: `GET /comercial/clientes/buscar?q=` (JSON)
- `GET|PATCH /{service}/editar` (puede reasignar cliente)
- `POST /{service}/inactivar` — `is_active = false` (no cambia portafolio)
- `POST /{service}/activar` — `is_active = true`

### Parametros — prefijo `comercial/parametros`

- `GET /` tablero de catalogos (`comercial.parameters.index`)
- `POST|PATCH|DELETE /{type}/{id?}` CRUD por tipo: `sectors`, `client-types`, `service-types`, `portfolios`

Nombres de ruta: `comercial.matriz.clients.*`, `comercial.matriz.services.*` y `comercial.parameters.*`.

Desde la ficha del cliente, “Agregar servicio” abre el alta de servicios con el cliente preseleccionado (`?client={id}`).

## Permisos

- `comercial.matriz.view` — ver clientes, servicios y dashboard
- `comercial.matriz.manage` — crear/editar cliente y servicios, inactivar
- `view.board.comercial.dashboard` / `view.area.comercial` — tambien habilitan el dashboard
- `view.board.comercial.gestion_clientes` — muestra el tablero **Gestion Clientes** en sidebar (migracion: quien tenia `matriz_clientes` o `servicios_comerciales`)
- `view.board.comercial.matriz_clientes` — pestaña **Clientes** dentro de Gestion Clientes
- `view.board.comercial.servicios_comerciales` — pestaña **Servicios** dentro de Gestion Clientes
- `manage.commercial.parameters` — pestaña **Parametros** (CRUD catalogos); tambien accesible con `comercial.matriz.manage`
- Quien tenga `comercial.matriz.*` ve el tablero y ambas pestañas; con solo uno de los permisos de pestaña ve unicamente esa pestaña
- Assignables en Admin usuarios → Alcance Comercial (*Ver tableros* / *Matriz comercial*)
- `manage.users` puede administrar (bypass)

El tablero Gestion Clientes y el dashboard KPI solo aplican al area Comercial. Servicio de acceso: `CommercialAccessService`.

## Relacion con otros modulos

Al crear o editar una requisicion de personal, el cliente se elige desde esta matriz. `CommercialClientBridge` vincula por nombre con `requisition_clients` (tabla interna usada por `personal_requisitions.client_id` y filtros del dashboard). Esa tabla **no** se administra en Parametros de requisiciones.

## Fuente documental

Formato de negocio MT-CO-01 (Excel maestro). El archivo **no** vive en el repo; solicitar copia al area Comercial o usar exportaciones desde el sistema.

## Referencias

- Guia de usuario: [`docs/user/matriz-clientes.md`](../user/matriz-clientes.md)
- Guia documentacion: [`docs/DOCUMENTATION.md`](../DOCUMENTATION.md)
