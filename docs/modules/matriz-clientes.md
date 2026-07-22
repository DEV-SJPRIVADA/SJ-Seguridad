# Modulo Matriz comercial (MT-CO-01)

## Objetivo

Digitalizar la matriz comercial MT-CO-01 con tableros en Comercial:

- **Dashboard**: indicadores interactivos de clientes/servicios
- **Clientes**: maestro por NIT
- **Servicios**: contratos/portafolios vinculados a un cliente (seleccion obligatorio al crear)

## Alcance V1

- Area exclusiva: `comercial`
- Boards:
  - `dashboard` (etiqueta: **Dashboard**) — redirige a `comercial/dashboard`
  - `matriz_clientes` (etiqueta: **Clientes**)
  - `servicios_comerciales` (etiqueta: **Servicios**)
- Dashboard: filtros portafolio/ciudad (stock); año/mes para **clientes nuevos** (`created_at`) y tendencia de altas (`contract_start`); KPIs (total clientes, clientes nuevos, activos, por vencer ≤30, vencidos, inactivos) y Chart.js
- Listado clientes: NIT, cliente, ciudad, portafolio(s), tipos de servicio, inicio/fin contrato, conteos
- Listado servicios: cliente, NIT, portafolio, contrato, tipo, asesor, vigencia, acciones; filtros `vigencia=expiring|expired`
- Modelo:
  - `commercial_clients` (NIT unico, datos maestros)
  - `commercial_services` (N:1 con cliente; portafolio, contrato, checklist, vigencia, contacto operativo)
- Portafolios: `seg_fisica`, `monitoreo`, `ocasionales`, `inactivos`
- Catalogos: `commercial_sectors`, `commercial_client_types`, `commercial_service_types`
- Checklist documental por servicio (estados, sin adjuntos)
- Badge de vencimiento (30/60 dias)

## Fuera de V1

- BORRADOR SVC / facturacion / consecutivos
- Sync automatico con `requisition_clients` al crear/editar requisiciones (`CommercialClientBridge`)
- Adjuntos PDF / documentos Calidad
- Notificaciones de vencimiento

## Importacion desde Excel

Comando:

```powershell
php artisan comercial:import-mt-co-01
```

Opciones:

- `--path=...` ruta alternativa al xlsx
- `--fresh` elimina clientes/servicios comerciales y vuelve a cargar (destructivo)

Por defecto lee [`docs/MT-CO-01 Matriz de clientes.xlsx`](../MT-CO-01%20Matriz%20de%20clientes.xlsx) e importa hojas `SEG. FISICA`, `MONITOREO`, `OCASIONALES`, `INACTIVOS`.

- Cliente: upsert por NIT normalizado
- Servicio: upsert por cliente + portafolio + numero de contrato
- Catalogos sector/tipo se crean si no existen
- Implementacion: `App\Services\Comercial\MtCo01Importer` + `comercial:import-mt-co-01`
- Al importar/guardar se descartan duraciones > 600 meses y fechas de contrato anteriores a 1980 (artefactos de Excel); al editar un servicio con datos corruptos, el guardado los normaliza automaticamente

## Rutas

`routes/areas/comercial.php`:

### Dashboard

- `GET /comercial/dashboard` (`comercial.dashboard`) — KPIs y graficos; el board Dashboard de Comercial redirige aqui

### Clientes — prefijo `comercial/clientes`

- `GET /` listado
- `GET|POST /crear` alta
- `GET /{client}` ficha (servicios relacionados, solo lectura/enlaces)
- `GET|PATCH /{client}/editar`

### Servicios — prefijo `comercial/servicios`

- `GET /` listado independiente
- `GET|POST /crear` alta con **busqueda de cliente** por nombre/NIT (`commercial_client_id`)
- Endpoint auxiliar: `GET /comercial/clientes/buscar?q=` (JSON)
- `GET|PATCH /{service}/editar` (puede reasignar cliente)
- `POST /{service}/inactivar`

Nombres de ruta: `comercial.matriz.clients.*` y `comercial.matriz.services.*`.

Desde la ficha del cliente, “Agregar servicio” abre el alta de servicios con el cliente preseleccionado (`?client={id}`).

## Permisos

- `comercial.matriz.view` — ver clientes, servicios y dashboard
- `comercial.matriz.manage` — crear/editar cliente y servicios, inactivar
- `view.board.comercial.dashboard` / `view.area.comercial` — tambien habilitan el dashboard
- `view.board.comercial.matriz_clientes` — habilita tablero Clientes
- `view.board.comercial.servicios_comerciales` — habilita tablero Servicios
- Quien tenga `comercial.matriz.*` o el board de clientes tambien ve Servicios en nav
- Assignables en Admin usuarios → Alcance Comercial
- `manage.users` puede administrar (bypass)

Los boards de matriz y el dashboard KPI solo aplican al area Comercial.

## Relacion con otros modulos

Al crear o editar una requisicion de personal, el cliente se elige desde esta matriz. `CommercialClientBridge` vincula por nombre con `requisition_clients` (tabla interna usada por `personal_requisitions.client_id` y filtros del dashboard). Esa tabla **no** se administra en Parametros de requisiciones.

## Fuente documental

Excel de referencia: [`docs/MT-CO-01 Matriz de clientes.xlsx`](../MT-CO-01%20Matriz%20de%20clientes.xlsx).

## Referencias

- Guia de usuario: [`docs/user/matriz-clientes.md`](../user/matriz-clientes.md)
- Guia documentacion: [`docs/DOCUMENTATION.md`](../DOCUMENTATION.md)
