# Control de Acceso

## Base tecnica

El proyecto usa `spatie/laravel-permission` con guard `web`.

## Roles base

- `super-admin` — acceso total; único rol con `manage.users` por defecto
- `administrador` — plataforma/GH (`manage.requisition.parameters`, `requisitions.approve.management`); **sin** `manage.users`
- `director` — autoriza solicitudes de compra y cargo nuevo en requisiciones (`purchase.tab.approval`, `requisitions.approve.management`)
- `usuario`

Los roles antiguos `coordinador` y `consulta` fueron eliminados; el seeder migra a `usuario` cualquier usuario que aun los tenga.

## Permisos del sistema

Definidos en [`config/access.php`](c:/laragon/www/SJSEGURIDAD/config/access.php):

- `view.dashboard`
- `manage.users`
- `system.view.audit` — auditoria global del sistema (super-admin)
- `manage.requisition.parameters`
- `requisitions.tab.dashboard`
- `requisitions.tab.solicitar`
- `requisitions.tab.seguimiento`
- `requisitions.tab.gestion`
- `requisitions.approve.management`
- `requisitions.selection_officer`

`manage.requisitions` permanece en codigo por compatibilidad con asignaciones legacy, pero **no aparece en Admin**. Usar `requisitions.tab.gestion` + tablero visible en alcance.

**Autorizacion gerencia (`requisitions.approve.management`):** pestaña **Autorizacion gerencia** en Requisiciones; aprueba o rechaza solicitudes con motivo **Cargo nuevo** (`pendiente_autorizacion_gerencia` → `solicitada` o `cancelada`). Rol `administrador` lo incluye por seeder. Correo de aviso configurable en Parametros → tipos de notificacion.

**Encargado de seleccion (`requisitions.selection_officer`):** define quien puede aparecer en el select **Reclutador** al gestionar requisiciones. No se asigna por defecto a roles base. La via operativa es el toggle en **Requisiciones → Gestion humana → Parametros → Encargados de seleccion** (permiso `manage.requisition.parameters`). El permiso figura en Admin bajo **Requisiciones — Gestion humana** para visibilidad; la asignacion manual alli es excepcional (p. ej. super-admin). Servicio: `RequisitionSelectionOfficerAccessService`. Tras la migracion `2026_07_28_112704_requisition_recruiter_id_references_users_drop_catalog`, ejecutar `php artisan migrate` y reactivar toggles en GH.

Permisos del modulo de suministros:

- `supply.tab.my_requests`
- `supply.tab.quality` (Aprobacion Insumos)
- `supply.tab.catalog`
- `manage.supply.catalog`
- `approve.supply.quality`

Permisos del modulo **Solicitudes de compra**:

- `purchase.tab.create`
- `purchase.tab.my_requests`
- `purchase.tab.approval`
- `purchase.tab.processing`

Tableros: `view.board.{area}.solicitudes_compra`, `view.board.{area}.bandeja_compras`

Permisos del modulo de documentos de Calidad:

- `manage.quality.documents`

Permisos del modulo de indicadores (area `operaciones`, board `indicadores`):

- `operations.view`
- `operations.capture`
- `operations.manage`
- `operations.export`

Estos permisos viven en `config/access.php` bajo `area_indicador_permissions.operaciones`. En **Administracion de usuarios** aparecen en **Activa visualizacion de otras areas → Operaciones → Indicadores (funciones)**.

Permisos de Matriz comercial (area `comercial`, boards sidebar `dashboard` y `gestion_clientes`):

- `comercial.matriz.view`
- `comercial.matriz.manage`
- `view.board.comercial.dashboard` / `view.area.comercial` (tablero **Dashboard** KPI)
- `view.board.comercial.gestion_clientes` (tablero sidebar **Gestion Clientes**)
- `view.board.comercial.matriz_clientes` (pestaña **Clientes** dentro de Gestion Clientes)
- `view.board.comercial.servicios_comerciales` (pestaña **Servicios** dentro de Gestion Clientes)

Viven en `area_indicador_permissions.comercial`. En Admin usuarios: **Activa visualizacion de otras areas → Comercial** (*Ver tableros* / *Matriz comercial*).

Visibilidad de pestañas: `CommercialAccessService` — usuario con solo permiso de clientes ve pestaña Clientes; solo servicios ve Servicios; `comercial.matriz.*` ve ambas.

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

- `requisitions.tab.gestion`, `requisitions.tab.dashboard`, `manage.requisition.parameters`, `requisitions.selection_officer` (select Reclutador; toggle en Parametros GH)
- `supply.tab.quality`, `supply.tab.catalog`, etc.

`view.area.*` y `manage.area.*` no sustituyen `view.board.*` para requisiciones o suministros. Documentos sigue usando `view.area.*`.

### Migracion manual post-deploy

- Directores: permisos transversales de autorizacion; el menu muestra **Gestion humana → Requisiciones** y **Compras → Solicitudes de compra** (hogares canonicos; no requiere tableros en todas las areas)
- Administradores de personal: funcionalidades de area base + tableros visibles en alcance + tabs por modulo (ej. Gestión en GH)
- Solicitantes insumos: `supply.tab.my_requests` (+ tablero visible si actuan fuera del area base)

## Hogares canonicos del sidebar

El sidebar usa [`SidebarVisibilityService`](../app/Services/Navigation/SidebarVisibilityService.php) y la config `board_canonical_areas` en [`config/access.php`](../config/access.php). Cada tablero transversal aparece **una vez** en su area natural; las demas areas solo muestran tableros propios del solicitante en su `area_key`.

| Tablero | Hogar en menu | Excepcion |
| --- | --- | --- |
| Requisiciones (gestion GH, autorizacion gerencia) | `gestion_humana` | — |
| Requisiciones (solicitar / mis req.) | `users.area_key` | Con permisos de solicitante |
| Suministros (aprobacion Calidad) | `calidad` | — |
| Suministros (catalogo / operacion Compras) | `compras` | — |
| Suministros (mis solicitudes) | `users.area_key` | Con `supply.tab.my_requests` |
| Solicitudes de compra (pendientes / bandeja) | `compras` | — |
| Solicitudes de compra (crear / mis) | `users.area_key` | Con permisos base |
| Documentos (administrar) | `calidad` | — |
| Documentos (consulta) | `users.area_key` o `view.area.*` explicito | Super-admin: solo Calidad + su area base |

**Matriz rol → areas visibles tipicas**

| Rol | Areas en sidebar |
| --- | --- |
| Super-admin | GH, Compras, Calidad, Operaciones, Comercial (+ area base si distinta) |
| Director | Gestion humana, Compras |
| Usuario de area | Solo su `area_key` (+ otras areas con `view.board` explicito sin alcance GH global) |
| Compras (processing) | Compras |

La visibilidad del sidebar **no restringe rutas**: un super-admin puede seguir accediendo por URL directa. Los middleware y policies conservan el bypass operativo con `manage.users`.

**Pruebas:** `tests/Feature/NavigationVisibilityTest.php` (super-admin, director, usuario de area, compras).


Cada area puede tener tableros internos definidos en `config/access.php`. Los tableros base son:

- `dashboard`
- `requisiciones`
- `suministros`
- `documentos`
- `indicadores` (solo en area `operaciones`; acceso por permisos `operations.*`, no por `view.board.*`)
- `gestion_clientes` (etiqueta UI: **Gestion Clientes**; solo en area `comercial`; sidebar por `view.board.comercial.gestion_clientes`, `comercial.matriz.*` o permisos legacy de pestaña; pestañas Clientes/Servicios con permisos `view.board.comercial.matriz_clientes` y `view.board.comercial.servicios_comerciales`)
- En area `comercial`, el board `dashboard` redirige a `comercial/dashboard` (KPIs de matriz); acceso por `comercial.matriz.*`, `view.board.comercial.dashboard` o `view.area.comercial`

Esto produce permisos como:

- `view.board.gestion_humana.dashboard`
- `view.board.gestion_humana.requisiciones`
- `view.board.gestion_humana.requisiciones`
- `view.board.compras.suministros` (tablero de suministros; area **Compras**, no GH)

El tablero `documentos` **no** usa `view.board.{area}.documentos`. En el **sidebar**, aparece en el `area_key` del usuario, en areas con `view.area.*` / `manage.area.*` explicito, o en **Calidad** para quien administra documentos. Super-admin ve Documentos solo en Calidad + su area base (no en las 8 areas). La biblioteca filtra por documentos activos asignados al area. La administracion requiere el permiso funcional `manage.quality.documents`.

Adicionalmente, un documento puede asignarse a usuarios especificos mediante la tabla `quality_document_users`. Esos destinatarios lo consultan en la pestaña `Mis documentos` del tablero `Documentos` de su area (`/quality-documents/{module}/mis-documentos`). No se requiere permiso adicional para ver esa pestaña.

## Asignacion base de roles

Sembrada en [`database/seeders/RoleAndPermissionSeeder.php`](c:/laragon/www/SJSEGURIDAD/database/seeders/RoleAndPermissionSeeder.php):

- `super-admin`: todos los permisos (sidebar compacto via `SidebarVisibilityService`)
- `administrador`: `view.dashboard`, `manage.requisition.parameters`, `requisitions.approve.management`
- `director`: autorizacion compras + cargo nuevo; menu en GH y Compras (ver hogares canonicos)
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
2. **Funcionalidades transversales:** requisiciones GH, suministros Calidad/Compras, admin, biblioteca documentos por area.
3. **Activa visualizacion de otras areas:** tableros y modulos por area (GH, **Compras**, Operaciones, Comercial, Calidad) con subgrupos *Ver tableros* / *funciones*.

El listado de usuarios muestra permisos **directos** y notas de acceso efectivo por rol (`UserAccessSummary`).

Migracion `2026_07_21_140000_migrate_legacy_requisition_and_supply_board_permissions` reemplaza:
- `manage.requisitions` → `requisitions.tab.gestion`
- `view.board.gestion_humana.suministros` → `view.board.compras.suministros`

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
