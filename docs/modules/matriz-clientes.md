# Modulo Matriz comercial (MT-CO-01)

## Objetivo

Digitalizar la matriz comercial MT-CO-01 con dos tableros independientes:

- **Clientes**: maestro por NIT
- **Servicios**: contratos/portafolios vinculados a un cliente (seleccion obligatorio al crear)

## Alcance V1

- Area exclusiva: `comercial`
- Boards:
  - `matriz_clientes` (etiqueta: **Clientes**)
  - `servicios_comerciales` (etiqueta: **Servicios**)
- Listado clientes: NIT, cliente, ciudad, portafolio(s), tipos de servicio, inicio/fin contrato, conteos
- Listado servicios: cliente, NIT, portafolio, contrato, tipo, asesor, vigencia, acciones
- Modelo:
  - `commercial_clients` (NIT unico, datos maestros)
  - `commercial_services` (N:1 con cliente; portafolio, contrato, checklist, vigencia, contacto operativo)
- Portafolios: `seg_fisica`, `monitoreo`, `ocasionales`, `inactivos`
- Catalogos: `commercial_sectors`, `commercial_client_types`, `commercial_service_types`
- Checklist documental por servicio (estados, sin adjuntos)
- Badge de vencimiento (30/60 dias)

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

`routes/areas/comercial.php`:

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

- `comercial.matriz.view` — ver clientes y servicios
- `comercial.matriz.manage` — crear/editar cliente y servicios, inactivar
- `view.board.comercial.matriz_clientes` — habilita tablero Clientes
- `view.board.comercial.servicios_comerciales` — habilita tablero Servicios
- Quien tenga `comercial.matriz.*` o el board de clientes tambien ve Servicios en nav
- Assignables en Admin usuarios → Alcance Comercial
- `manage.users` puede administrar (bypass)

Ambos boards solo aparecen en el area Comercial.

## Relacion con otros modulos

`RequisitionClient` (parametros de requisiciones) **no** se mezcla con esta matriz.

## Fuente documental

Excel de referencia: [`docs/MT-CO-01 Matriz de clientes.xlsx`](../MT-CO-01%20Matriz%20de%20clientes.xlsx).
