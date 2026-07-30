# Feature Brief — FEAT-018

## Objetivo

Pestaña **Parametros** en tablero **Gestion Clientes** (Comercial) para CRUD de catalogos que alimentan selects en `comercial/servicios/crear`, siguiendo UX de [`resources/views/modules/requisitions/parameters.blade.php`](../../resources/views/modules/requisitions/parameters.blade.php).

## Catalogos

| Tipo (route) | Modelo | Label |
| --- | --- | --- |
| `sectors` | `CommercialSector` | Sectores |
| `client-types` | `CommercialClientType` | Tipos de cliente |
| `service-types` | `CommercialServiceType` | Tipos de servicio |
| `portfolios` | `CommercialPortfolio` (nuevo) | Portafolios |

**Portafolio:** tabla `commercial_portfolios` (`slug`, `name`, `is_active`, `sort_order`). Migrar slugs existentes (`seg_fisica`, `monitoreo`, `ocasionales`, `inactivos`). Columna `commercial_services.portfolio` sigue siendo **slug** string; selects/validacion desde BD.

## Permiso

`manage.commercial.parameters` — tab Parametros visible solo con este permiso o `comercial.matriz.manage` / `manage.users`.

## Rutas

Bajo `comercial/parametros` (auth + password.changed + permiso):

- GET index
- POST/PATCH/DELETE `{type}/{id?}` como requisiciones

## Fuera de alcance

- Cambiar estilos globales; reutilizar grid/cards de requisiciones (copia adaptada).
- Cliente picker (busqueda NIT).

## Tests

CommercialMatrixTest: acceso parametros, CRUD tipo, crear servicio usa catalogo activo, portfolio desde BD.
