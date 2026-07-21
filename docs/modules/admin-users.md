# Modulo Admin Users

## Objetivo

Gestionar usuarios internos del sistema, incluyendo rol principal, permisos directos, estado activo y cambio obligatorio de contrasena.

## Alcance actual

- Panel lateral con listado paginado y busqueda de usuarios
- Resumen del usuario seleccionado: ficha operativa y lista plana de permisos directos asignados (sin agrupar por transversales u otras areas)
- Formulario en dos pestanas: **Usuario** y **Que puede hacer**
- Tres bloques de permisos sin repeticion por area:
  1. **En su area asignada** (solicitar, mis requisiciones, mis solicitudes suministros)
  2. **Funcionalidades transversales** (gestion requisiciones, suministros, admin, biblioteca documentos)
  3. **Otras areas** (solo modulos exclusivos: GH, Operaciones/Indicadores, Comercial, Calidad)
- Avisos de coherencia al guardar (soft warnings)

## Rutas

- `GET /admin/users`
- `GET /admin/users/create`
- `POST /admin/users`
- `GET /admin/users/{user}/edit`
- `PUT/PATCH /admin/users/{user}`

## Configuracion UI

`config/access.php` → `admin_ui`:

- `assigned_area_permissions`
- `global_groups`
- `other_areas`

## Servicios

- `UserPermissionFormBuilder` — estructura del formulario
- `UserPermissionValidator` — avisos de coherencia
- `NavigationResolver` — sidebar de la app (sin preview en Admin)

## Reglas de negocio

- `manage.requisitions` oculto en Admin (legacy)
- El motor Spatie no cambia; solo la presentacion en Admin
- Gestión/Parametros de requisiciones se asignan una vez en transversales; el tablero GH va en Otras areas
