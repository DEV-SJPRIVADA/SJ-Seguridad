# Modulo Matriz de clientes (MT-CO-01)

## Objetivo

Digitalizar la matriz comercial MT-CO-01: maestro de **clientes** (NIT) con multiples **servicios/contratos** que pueden pertenecer a distintos portafolios.

## Alcance V1

- Area exclusiva: `comercial`
- Board: `matriz_clientes` (etiqueta de navegacion: **Clientes**)
- Listado principal: NIT, cliente, ciudad, **portafolio(s)**, tipos de servicio, **inicio/fin contrato** (rango sobre servicios activos: inicio mas antiguo y fin mas reciente), conteos
- Modelo:
  - `commercial_clients` (NIT unico, datos maestros)
  - `commercial_services` (1:N, portafolio, contrato, checklist, vigencia, contacto operativo)
- Portafolios: `seg_fisica`, `monitoreo`, `ocasionales`, `inactivos`
- Catalogos: `commercial_sectors`, `commercial_client_types`, `commercial_service_types`
- Checklist documental por servicio (estados, sin adjuntos)
- Badge de vencimiento (30/60 dias) en ficha cliente

## Fuera de V1

- BORRADOR SVC / facturacion / consecutivos
- Sync con `requisition_clients`
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

## Rutas

Prefijo `comercial/matriz-clientes` (`routes/areas/comercial.php`):

- `GET /` listado clientes
- `GET|POST /crear` alta cliente
- `GET /{client}` ficha + servicios
- `GET|PATCH /{client}/editar`
- `GET|POST /{client}/servicios/crear`
- `GET|PATCH /{client}/servicios/{service}/editar`
- `POST /{client}/servicios/{service}/inactivar`

## Permisos

- `comercial.matriz.view` — ver listado y fichas
- `comercial.matriz.manage` — crear/editar cliente y servicios, inactivar
- `view.board.comercial.matriz_clientes` — tambien habilita el board en nav
- Assignables en Admin usuarios → Alcance Comercial
- `manage.users` puede administrar (bypass)

El board solo aparece en el area Comercial (mismo patron que Indicadores en Operaciones).

## Relacion con otros modulos

`RequisitionClient` (parametros de requisiciones) **no** se mezcla con esta matriz.

## Fuente documental

Excel de referencia: [`docs/MT-CO-01 Matriz de clientes.xlsx`](../MT-CO-01%20Matriz%20de%20clientes.xlsx).
