# Modulo Admin Users

## Objetivo

Gestionar usuarios internos del sistema, incluyendo rol principal, permisos directos, estado activo y cambio obligatorio de contrasena.

## Alcance actual

- Panel lateral con listado paginado y busqueda de usuarios
- Por defecto el listado muestra solo usuarios activos; checkbox **Mostrar usuarios inactivos** amplia la lista
- Resumen del usuario seleccionado: ficha operativa, notas de acceso efectivo por rol y lista plana de permisos directos asignados
- Formulario en pestanas: **Identidad**, **Acceso y permisos**, **Seguridad** (edicion)
- **Acceso y permisos:** layout maestro–detalle — sidebar con **Mi area base**, **Transversal** y cada area del negocio; panel derecho con subgrupos y toggles de la seleccion
- La estructura logica sigue en tres bloques (`assigned_area`, `global_groups`, `other_areas`); la UI los navega por area via `sections.navigation`
- Avisos de coherencia al guardar (soft warnings)

## Rutas

- `GET /admin/users`
- `GET /admin/users/create`
- `POST /admin/users`
- `GET /admin/users/{user}/edit`
- `PUT/PATCH /admin/users/{user}`

## Configuracion UI

`config/access.php` → `admin_ui`:

- `sections` — titulos de los tres bloques
- `assigned_area_permissions`
- `global_groups` (incluye `supplies_calidad` y `supplies_compras`)
- `other_areas` (con `subgroups` por area)

## Servicios

- `UserPermissionFormBuilder` — estructura del formulario (`sections` + `sections.navigation` para sidebar)
- `UserPermissionValidator` — avisos de coherencia (GH, Compras, Calidad, Operaciones, Comercial)
- `UserAccessSummary` — notas de acceso efectivo en el listado
- `NavigationResolver` + `SidebarVisibilityService` — sidebar de la app con hogares canonicos (sin preview en Admin)

## Reglas de negocio

- `manage.requisitions` oculto en Admin (legacy migrado a `requisitions.tab.gestion`)
- Tablero `view.board.gestion_humana.suministros` migrado a `view.board.compras.suministros`
- Suministros: aprobacion = Calidad; catalogo = Compras
- **Hogares canonicos:** no asigne `view.board.{area}.requisiciones` en multiples areas a roles transversales; use funcionalidades transversales + hogar GH/Compras (ver `docs/ACCESS_CONTROL.md`)
- El motor Spatie no cambia; solo la presentacion en Admin y el filtro del sidebar

## Control de cambios

| Version | Fecha | Descripcion |
| --- | --- | --- |
| 1.1 | 2026-08-03 | Hogares canonicos del sidebar (`SidebarVisibilityService`); avisos en validador de permisos |

## Referencias

- Guia de usuario: [`docs/user/admin-users.md`](../user/admin-users.md)
- Guia documentacion: [`docs/DOCUMENTATION.md`](../DOCUMENTATION.md)
- Gestión/Parametros de requisiciones se asignan en transversales; tablero GH en **Activa visualizacion de otras areas → Gestion humana**
