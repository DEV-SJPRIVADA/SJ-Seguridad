# Modulo Admin Users

## Objetivo

Gestionar usuarios internos del sistema, incluyendo rol principal, permisos directos, estado activo y cambio obligatorio de contrasena.

## Alcance actual

- Panel lateral con listado paginado y busqueda de usuarios
- Por defecto el listado muestra solo usuarios activos; checkbox **Mostrar usuarios inactivos** amplia la lista
- Resumen del usuario seleccionado: ficha operativa, notas de acceso efectivo por rol y lista plana de permisos directos asignados
- Formulario en dos pestanas: **Usuario** y **Que puede hacer**
- Tres bloques de permisos sin repeticion por area:
  1. **En su area asignada** (solicitar, mis requisiciones, mis solicitudes suministros)
  2. **Funcionalidades transversales** (requisiciones GH, suministros Calidad/Compras, admin, biblioteca documentos)
  3. **Activa visualizacion de otras areas** (tableros y funciones por area: GH, Compras, Operaciones, Comercial, Calidad)
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

- `UserPermissionFormBuilder` — estructura del formulario
- `UserPermissionValidator` — avisos de coherencia (GH, Compras, Calidad, Operaciones, Comercial)
- `UserAccessSummary` — notas de acceso efectivo en el listado
- `NavigationResolver` — sidebar de la app (sin preview en Admin)

## Reglas de negocio

- `manage.requisitions` oculto en Admin (legacy migrado a `requisitions.tab.gestion`)
- Tablero `view.board.gestion_humana.suministros` migrado a `view.board.compras.suministros`
- Suministros: aprobacion = Calidad; catalogo = Compras
- El motor Spatie no cambia; solo la presentacion en Admin
- Gestión/Parametros de requisiciones se asignan en transversales; tablero GH en **Activa visualizacion de otras areas → Gestion humana**
