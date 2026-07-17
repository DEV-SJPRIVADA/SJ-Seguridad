# Control de Acceso

## Base tecnica

El proyecto usa `spatie/laravel-permission` con guard `web`.

## Roles base

- `super-admin`
- `administrador`
- `usuario`

Los roles antiguos `coordinador` y `consulta` fueron eliminados; el seeder migra a `usuario` cualquier usuario que aun los tenga.

## Permisos del sistema

Definidos en [`config/access.php`](c:/laragon/www/SJSEGURIDAD/config/access.php):

- `view.dashboard`
- `manage.users`
- `manage.requisition.parameters`
- `requisitions.tab.dashboard`
- `requisitions.tab.solicitar`
- `requisitions.tab.seguimiento`
- `requisitions.tab.gestion`

`manage.requisitions` permanece en codigo por compatibilidad con asignaciones legacy, pero **no aparece en Admin**. Usar `requisitions.tab.gestion` + tablero visible en alcance.

Permisos del modulo de suministros:

- `supply.tab.my_requests`
- `supply.tab.quality` (Aprobacion Insumos)
- `supply.tab.catalog`
- `manage.supply.catalog`
- `approve.supply.quality`

Permisos del modulo de documentos de Calidad:

- `manage.quality.documents`

Permisos del modulo de indicadores (area `operaciones`, board `indicadores`):

- `operations.view`
- `operations.capture`
- `operations.manage`
- `operations.export`

Estos permisos viven en `config/access.php` bajo `area_indicador_permissions.operaciones`. En **Administracion de usuarios** aparecen en **Alcance por Area → Operaciones**, no en la seccion de permisos funcionales.

Permisos de Matriz comercial (area `comercial`, boards `dashboard`, `matriz_clientes` y `servicios_comerciales`):

- `comercial.matriz.view`
- `comercial.matriz.manage`
- `view.board.comercial.dashboard` / `view.area.comercial` (tablero **Dashboard** KPI)
- `view.board.comercial.matriz_clientes` (tablero **Clientes**)
- `view.board.comercial.servicios_comerciales` (tablero **Servicios**)

Viven en `area_indicador_permissions.comercial`. En Admin usuarios: **Alcance por Area → Comercial**.

## Areas actuales

- `gestion_humana`
- `operaciones`
- `programacion`
- `juridico`
- `comercial`
- `calidad`
- `admin_financiero` (unifica las antiguas `remuneraciones` y `facturacion`)
- `compras`

La migracion `2026_07_10_120000_merge_remuneraciones_facturacion_into_admin_financiero` actualiza `area_key` en usuarios, requisiciones, suministros y documentos; fusiona permisos Spatie de las dos areas legacy hacia `admin_financiero`. Las URLs del modulo quedan como `/requisitions/admin_financiero`, `/supplies/admin_financiero`, etc. El proceso de Calidad `gestion_financiera` no cambia.

## Acciones por area

- `view`
- `manage`

Esto produce permisos como:

- `view.area.gestion_humana`
- `manage.area.gestion_humana`
- `view.area.operaciones`
- `manage.area.operaciones`

## Modelo de tres dimensiones

1. **`users.area_key` (area base):** contexto operativo. Solicitar, Mis requisiciones y Mis solicitudes de suministros operan siempre en esta area.
2. **`view.board.{area}.{board}` (alcance):** solo visualiza el tablero en el sidebar. No otorga acciones.
3. **Permisos funcionales:** habilitan subtabs/acciones.

### Funcionalidades de area base

Operan en `{area_key}` del usuario (sin exigir `view.board` en el area base):

- `requisitions.tab.solicitar`
- `requisitions.tab.seguimiento` (UI: **Mis requisiciones**)
- `supply.tab.my_requests`

### Funcionalidades por tablero visible

Requieren permiso funcional **y** `view.board.{module}.{board}`:

- `requisitions.tab.gestion`, `requisitions.tab.dashboard`, `manage.requisition.parameters`
- `supply.tab.quality`, `supply.tab.catalog`, etc.

`view.area.*` y `manage.area.*` no sustituyen `view.board.*` para requisiciones o suministros. Documentos sigue usando `view.area.*`.

### Migracion manual post-deploy

- Directores: tablero + `requisitions.tab.solicitar` (+ Mis requisiciones si aplica)
- Administradores de personal: funcionalidades de area base + tableros visibles en alcance + tabs por modulo (ej. Gestión en GH)
- Solicitantes insumos: `supply.tab.my_requests` (+ tablero visible si actuan fuera del area base)

## Tableros por area

Cada area puede tener tableros internos definidos en `config/access.php`. Los tableros base son:

- `dashboard`
- `requisiciones`
- `suministros`
- `documentos`
- `indicadores` (solo en area `operaciones`; acceso por permisos `operations.*`, no por `view.board.*`)
- `matriz_clientes` (etiqueta UI: **Clientes**; solo en area `comercial`; acceso por `comercial.matriz.*` y/o `view.board.comercial.matriz_clientes`)
- `servicios_comerciales` (etiqueta UI: **Servicios**; solo en area `comercial`; acceso por `comercial.matriz.*` y/o `view.board.comercial.servicios_comerciales` / board clientes)
- En area `comercial`, el board `dashboard` redirige a `comercial/dashboard` (KPIs de matriz); acceso por `comercial.matriz.*`, `view.board.comercial.dashboard` o `view.area.comercial`

Esto produce permisos como:

- `view.board.gestion_humana.dashboard`
- `view.board.gestion_humana.requisiciones`
- `view.board.gestion_humana.suministros`

El tablero `documentos` **no** usa `view.board.{area}.documentos`. Aparece en cada area con acceso (`view.area.*` o `manage.area.*`) y la biblioteca filtra por documentos activos asignados al area. La administracion requiere el permiso funcional `manage.quality.documents`.

Adicionalmente, un documento puede asignarse a usuarios especificos mediante la tabla `quality_document_users`. Esos destinatarios lo consultan en la pestaña `Mis documentos` del tablero `Documentos` de su area (`/quality-documents/{module}/mis-documentos`). No se requiere permiso adicional para ver esa pestaña.

## Asignacion base de roles

Sembrada en [`database/seeders/RoleAndPermissionSeeder.php`](c:/laragon/www/SJSEGURIDAD/database/seeders/RoleAndPermissionSeeder.php):

- `super-admin`: todos los permisos
- `administrador`: `view.dashboard`, `manage.users` y `manage.requisition.parameters`
- `usuario`: `view.dashboard`

Los roles antiguos `coordinador` y `consulta` se migran a `usuario` durante el seeder si existen. Los permisos de areas que ya no esten definidos en `config/access.php` se eliminan para evitar accesos obsoletos.

### Sincronizar permisos sin resetear roles

Comando artisan `app:sync-permissions`:

- Crea o actualiza permisos de sistema, areas y tableros segun `config/access.php`
- Excluye `view.board.{area}.documentos` (el tablero Documentos no usa ese permiso)
- Elimina permisos huerfanos de areas/tableros obsoletos
- **No** modifica roles ni permisos asignados a usuarios

Util cuando se agregan areas o permisos nuevos sin ejecutar el seeder completo.

## Configuracion en Admin de usuarios

El formulario en **Administracion → Usuarios** usa tres bloques (`config/access.php` → `admin_ui`):

1. **En su area asignada:** solicitar, mis requisiciones, mis solicitudes suministros (operan en `users.area_key`).
2. **Funcionalidades transversales:** gestion/parametros requisiciones, suministros, admin, biblioteca documentos por area (una sola lista).
3. **Otras areas:** modulos exclusivos (Gestión humana tableros, Operaciones/indicadores, Comercial, Calidad).

Documentacion: [`docs/modules/admin-users.md`](modules/admin-users.md).

## Middleware y enforcement

- `/dashboard` exige `view.dashboard` ademas de autenticacion, usuario activo y contrasena cambiada.
- Rutas de requisiciones usan middleware `requisition.tab:{tab}` alineado con `RequisitionAccessService`.
- Rutas de suministros usan middleware `supply.tab:{tab}` alineado con `SupplyAccessService`.
- Administracion de documentos de Calidad solo responde en `module=calidad`; otras areas devuelven 404 aunque el usuario tenga `manage.quality.documents`.
- `supply_request` en rutas se resuelve acotado al `module` de la URL (proteccion IDOR).

Servicios centrales:

- [`app/Services/Access/RequisitionAccessService.php`](../app/Services/Access/RequisitionAccessService.php)
- [`app/Services/Access/SupplyAccessService.php`](../app/Services/Access/SupplyAccessService.php)

## Sede fisica del usuario (suministros)

En **Administracion de usuarios** (`manage.users`) cada usuario puede tener `sede_id` (catalogo `supply_sites`). Es requerida para crear solicitudes de insumos y define el snapshot Utilizacion/Ubicacion del reporte FO-AD-44. Las sedes se administran desde el modal **Gestionar** en el formulario de usuario (rutas `admin.supply-sites.*`). El permiso `supply.tab.quality` habilita las pestañas **Aprobacion Insumos** e **Insumos aprobados**. Ver [`docs/modules/suministros.md`](modules/suministros.md).

## Reglas obligatorias

- No habilitar registro publico salvo instruccion expresa
- Todo acceso sensible debe exigir autenticacion y permisos
- Los usuarios inactivos no pueden operar
- Las contrasenas temporales deben obligar cambio al primer ingreso
- Una vez el usuario actualiza correctamente su contrasena, `must_change_password` debe pasar a `false`
- `users.area_key` define el modulo de Solicitar y Mis requisiciones; no otorga permisos por si solo
- `view.board.*` en alcance solo visualiza; las acciones requieren permisos funcionales explicitos

## Impacto de cambios

Cuando se agregue una nueva area del negocio, se deben revisar como minimo:

- `config/access.php`
- `database/seeders/RoleAndPermissionSeeder.php`
- rutas
- navegacion
- vistas del modulo
- pruebas relacionadas con permisos
