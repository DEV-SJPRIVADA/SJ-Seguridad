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
- `manage.requisitions`
- `manage.requisition.parameters`
- `requisitions.tab.dashboard`
- `requisitions.tab.solicitar`
- `requisitions.tab.seguimiento`
- `requisitions.tab.gestion`

Permisos del modulo de suministros:

- `supply.tab.my_requests`
- `supply.tab.quality` (Aprobacion Insumos)
- `supply.tab.catalog`
- `manage.supply.catalog`
- `approve.supply.quality`

Permisos del modulo de documentos de Calidad:

- `manage.quality.documents`

Estos permisos se administran visualmente dentro de la fila `Administracion de usuarios` en la matriz de permisos.

## Areas actuales

- `gestion_humana`
- `operaciones`
- `programacion`
- `juridico`
- `comercial`
- `calidad`
- `remuneraciones`
- `facturacion`
- `compras`

## Acciones por area

- `view`
- `manage`

Esto produce permisos como:

- `view.area.gestion_humana`
- `manage.area.gestion_humana`
- `view.area.operaciones`
- `manage.area.operaciones`

## Tableros por area

Cada area puede tener tableros internos definidos en `config/access.php`. Los tableros base son:

- `dashboard`
- `requisiciones`
- `suministros`
- `documentos`

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

## Middleware y enforcement

- `/dashboard` exige `view.dashboard` ademas de autenticacion, usuario activo y contrasena cambiada.
- Rutas de suministros usan middleware `supply.tab:{tab}` alineado con `User::canAccessSupplyTab()` (acepta permisos granulares `supply.tab.*`, variantes full y `view.board.{area}.suministros` para mis solicitudes).
- Administracion de documentos de Calidad solo responde en `module=calidad`; otras areas devuelven 404 aunque el usuario tenga `manage.quality.documents`.
- `supply_request` en rutas se resuelve acotado al `module` de la URL (proteccion IDOR).

## Reglas obligatorias

- No habilitar registro publico salvo instruccion expresa
- Todo acceso sensible debe exigir autenticacion y permisos
- Los usuarios inactivos no pueden operar
- Las contrasenas temporales deben obligar cambio al primer ingreso
- Una vez el usuario actualiza correctamente su contrasena, `must_change_password` debe pasar a `false`
- En requisiciones, un permiso explicito de tablero o pestaña habilita el acceso aunque `users.area_key` sea diferente
- `users.area_key` sigue usandose como contexto operativo del usuario, no como filtro oculto para negar pestañas ya autorizadas

## Impacto de cambios

Cuando se agregue una nueva area del negocio, se deben revisar como minimo:

- `config/access.php`
- `database/seeders/RoleAndPermissionSeeder.php`
- rutas
- navegacion
- vistas del modulo
- pruebas relacionadas con permisos
