# Feature Brief — FEAT-013

> Versión final (Arquitecto). Decisiones de negocio cerradas 2026-07-29 (ver [`FEAT-013-analyst.md`](FEAT-013-analyst.md)).

## Identificacion

| Campo | Valor |
| --- | --- |
| ID | FEAT-013 |
| Modulo / area | **Administracion** (`admin.*`); origen funcional **requisiciones**; diseno **multi-modulo** (v1 solo conecta `requisitions`) |
| Titulo | Configuracion global de notificaciones (catalogo de tipos + correos destinatarios) |
| Solicitante | Manuel-E (via `@agent-sj`) |
| Fecha | 2026-07-29 |

## Objetivo

Centralizar en **Administracion** la gestion de **correos destinatarios** y la **asignacion por tipo de aviso** que hoy vive en Parámetros de requisiciones (catálogo «Correos de notificacion» en todas las areas) y en GH (seccion «Tipos de notificacion»). El administrador con permiso dedicado ve el **catalogo de tipos** definidos por el sistema (seed/migraciones) y asigna correos; los envios automaticos de requisiciones siguen resolviendo destinatarios desde esta capa.

**Para quien:** personal de plataforma con `manage.notifications` (independiente de `manage.users` y de `manage.requisition.parameters`).

**Bug reportado (GH Parámetros → Tipos vacios):** no se mantiene ni repara la UI en GH. Causa probable: migracion `2026_07_28_162227_*` no ejecutada o tablas sin seed en el entorno. Cierre operativo: `php artisan migrate` en despliegue; la feature **elimina** la seccion en GH al entregar el tablero admin.

## Alcance

### Incluye

1. **Esquema BD multi-modulo:** renombrar/evolucionar tablas actuales hacia nombres transversales + columna `module` en tipos (detalle en Base de datos).
2. **Capa de servicio** `NotificationConfigService` (nombre definitivo a confirmar en implementacion) como API unica para: CRUD correos, listado de tipos por modulo, sync tipo↔correos, resolucion `emailsForType(module, slug)` con fallback existente.
3. **Tablero admin** «Configuracion de notificaciones»: listar tipos (agrupados por modulo en v1 solo bloque Requisiciones), CRUD correos (`name` = direccion, `is_active`, `sort_order`), asignacion many-to-many por tipo.
4. **Permiso Spatie** `manage.notifications` en `system_permissions`, UI admin de usuarios (`admin_ui.global_groups.administration`) y entrada sidebar en `navigation.administracion`.
5. **Rutas** bajo `routes/web.php` grupo `admin.*` con middleware `permission:manage.notifications` (no acoplado a `manage.users`).
6. **Retiro completo** en requisiciones: tarjeta/seccion Tipos de notificacion (solo GH), tarjeta Correos de notificacion (todas las areas), ruta `requisitions.notification-types.sync`, datos de vista asociados en `RequisitionController::parameters`.
7. **Envio requisiciones:** `RequisitionController` (y tests existentes) consumen la capa global; slugs actuales `new_requisition`, `management_approval_cargo_nuevo` sin cambio de comportamiento funcional.
8. **Nuevos tipos:** solo via **despliegue** (migracion o seeder en migracion); sin formulario «crear tipo» en admin en v1.
9. **Tests** feature: pantalla admin, permisos, regresion de envio por tipo; parametros sin rutas legacy de notificaciones.
10. **Documentacion** tecnica (`docs/modules/notifications-config.md`) y usuario admin; actualizar `docs/modules/requisitions.md` y `docs/user/requisitions.md` (retiro de Parámetros).

### Fuera de alcance

- **Historial / bandeja de envios** (fecha, asunto, estado de entrega).
- **Activar/desactivar un tipo entero** desde admin (solo correos activos/inactivos y asignacion).
- **Alta manual de tipos** desde UI.
- Integrar en v1 **suministros** u otros modulos (solo preparar esquema + servicio; conexion futura).
- **Correo al solicitante por cambio de estado** (`requested_by`): sigue **fuera** del tablero en v1 (supuesto analista; pregunta 6 sin respuesta).
- Notificaciones in-app, SMS, plantillas editables desde admin.
- Nueva area sidebar «Super Admin» (se usa **Administracion** existente).
- Export Excel (no solicitado).

## Reglas de negocio

1. **Catalogo de correos global:** un mismo pool de direcciones puede asignarse a tipos de distintos modulos (tabla sin `module`).
2. **Tipos por modulo:** cada fila de tipo tiene `module` (v1: `requisitions`) y `slug` unico **por modulo**; etiqueta y descripcion legibles en admin (solo lectura en v1).
3. **Asignacion:** many-to-many tipo ↔ correo; solo correos con `is_active = true` participan en envios.
4. **Resolucion de destinatarios:** si el tipo no existe o no tiene correos activos asignados → fallback `desarrollo.tic@sjsp.com.co` (politica actual).
5. **Parámetros requisiciones:** usuarios con `manage.requisition.parameters` **no** pueden mutar correos ni tipos desde requisiciones; rutas `parameters.store|update|destroy` con `{type}=emails` deben responder **404** (al quitar `emails` de `PARAMETER_TYPES`).
6. **Acceso admin:** solo `manage.notifications` (asignable por rol/usuario independiente de gestion de usuarios).
7. **Datos existentes:** migracion preserva filas de `requisition_notification_*` y pivot; tipos requisiciones reciben `module = 'requisitions'`.
8. **Despliegue pendiente:** entornos sin migracion de tipos deben ejecutar migraciones antes de usar admin; no hay UI alternativa en GH.

## Permisos (`config/access.php`)

| Permiso | Rol(es) tipicos | Descripcion |
| --- | --- | --- |
| `manage.notifications` | Asignado explicitamente (p. ej. `super-admin`; **no** incluir en rol `administrador` por defecto) | Configurar correos destinatarios y asignacion por tipo de aviso del sistema. |
| `manage.requisition.parameters` | GH / administradores de catalogos | Parámetros de requisiciones **sin** correos ni tipos de notificacion. |
| `manage.users` | Administradores de usuarios | Sin cambio; no implica acceso a notificaciones. |

**Registro:**

- Anadir en `system_permissions`: `'manage.notifications' => 'Configurar notificaciones por correo (destinatarios y tipos)'` (texto afinable).
- Anadir en `admin_ui.global_groups.administration.permissions` junto a `manage.users`.
- **Sidebar:** nuevo item en `navigation.administracion.items` (ruta `admin.notifications.index`, permiso `manage.notifications`, patterns `admin.notifications.*`).
- **Visibilidad del bloque Administracion:** hoy el modulo exige `permission => manage.users` a nivel grupo y oculta la seccion a quien solo tenga notificaciones. **Cambio requerido:** quitar el `permission` a nivel de `navigation.administracion` (o ampliar `NavigationResolver` para OR de permisos) y filtrar **solo por permiso de cada item**, de modo que un usuario con solo `manage.notifications` vea el tablero sin entrar a Usuarios.

**Seeders:** `RoleAndPermissionSeeder` no debe otorgar `manage.notifications` al rol `administrador` por defecto; `super-admin` sigue recibiendo todos los permisos via `PermissionCatalog::sync()`.

## Rutas

| Metodo | URI | Nombre | Archivo de rutas | Middleware / notas |
| --- | --- | --- | --- | --- |
| GET | `/admin/notificaciones` | `admin.notifications.index` | `routes/web.php` | `auth`, `active`, `password.changed`, `permission:manage.notifications` — vista unica (correos + tipos/asignacion). |
| POST | `/admin/notificaciones/correos` | `admin.notifications.emails.store` | `routes/web.php` | Idem. |
| PATCH | `/admin/notificaciones/correos/{notification_email}` | `admin.notifications.emails.update` | `routes/web.php` | Idem; modelo tras renombrar (p. ej. `NotificationEmail`). |
| DELETE | `/admin/notificaciones/correos/{notification_email}` | `admin.notifications.emails.destroy` | `routes/web.php` | Idem; validar que no rompa integridad o permitir desactivar en lugar de borrar si hay pivot. |
| PATCH | `/admin/notificaciones/tipos/{notification_type}` | `admin.notifications.types.sync-emails` | `routes/web.php` | Body: `email_ids[]`; tipo identificado por id (slug+module en BD). |

**Grupo admin en `web.php`:** mantener rutas `users` y `supply-sites` bajo `permission:manage.users`; registrar rutas de notificaciones en **grupo hermano** con `permission:manage.notifications` (mismo `prefix('admin')->name('admin.')`).

**Retirar (requisiciones):**

| Metodo | URI | Nombre | Accion |
| --- | --- | --- | --- |
| PATCH | `/requisitions/{module}/parametros/tipos-notificacion` | `requisitions.notification-types.sync` | **Eliminar** ruta y metodo `syncNotificationTypeEmails`. |

Rutas genericas `requisitions.parameters.store|update|destroy` con `{type}=emails`: **404** al retirar clave `emails` de `PARAMETER_TYPES`.

## Base de datos

### Propuesta concreta (renombrar + multi-modulo)

| Tabla actual | Tabla objetivo | Cambios |
| --- | --- | --- |
| `requisition_notification_emails` | `notification_emails` | Solo renombre; columnas `id`, `name`, `is_active`, `sort_order`, `timestamps` sin cambio. |
| `requisition_notification_types` | `notification_types` | Renombre + columna `module` (`string`, 64, index); indice unico compuesto `(module, slug)` sustituye `slug` unique global. |
| `req_notif_type_email` | `notification_type_email` | Renombre; FKs hacia tablas nuevas (`notification_type_id`, `notification_email_id`); mismo unique compuesto. |

**Migracion unica sugerida (orden):**

1. Renombrar tablas (o crear nuevas, copiar datos, drop viejas si el motor complica FKs en rename).
2. En `notification_types`: anadir `module`, poblar `'requisitions'` en filas existentes, crear unique `(module, slug)`, eliminar unique solo en `slug`.
3. Recrear/reapuntar foreign keys del pivot si hace falta tras rename.
4. **Seed embebido** (mismo contenido que `2026_07_28_162227`): solo si tras rename no hay filas (idempotente: `insertOrIgnore` por `module`+`slug`); reasignacion inicial opcional de todos los correos activos al tipo `new_requisition` (paridad migracion actual).

**Constantes de dominio (codigo, no BD):**

- Modulo requisiciones: `NotificationType::MODULE_REQUISITIONS = 'requisitions'` (o enum/string backed).
- Slugs existentes: mantener en modelo/constantes (`SLUG_NEW_REQUISITION`, `SLUG_MANAGEMENT_APPROVAL_CARGO_NUEVO`).

**Modelos:**

- `App\Models\NotificationEmail` (tabla `notification_emails`).
- `App\Models\NotificationType` (tabla `notification_types`, scope `forModule(string $module)`).
- Eliminar o dejar alias temporal **no** recomendado: retirar `RequisitionNotificationEmail` y `RequisitionNotificationType` tras migracion.

**Futuro (otros modulos):** nueva migracion inserta filas en `notification_types` con `module = 'supplies'` (etc.); el tablero admin lista por modulo sin cambio de esquema.

## Capas a implementar

- [ ] Migracion(es) — rename + `module` + FKs
- [ ] Modelo(s) — `NotificationEmail`, `NotificationType`
- [ ] Servicio — `App\Services\Notifications\NotificationConfigService`
- [ ] Controlador(es) — `App\Http\Controllers\Admin\NotificationConfigController` (nombre alineado a convencion admin)
- [ ] Form Request(s) — store/update email; sync emails por tipo (validacion `exists` en tablas nuevas)
- [ ] Vista(s) Blade — `resources/views/admin/notifications/` (layout `x-app-layout`, estilo panel admin usuarios)
- [ ] JavaScript (si aplica) — minimo: formularios POST/PATCH; reutilizar patrones de tablas admin existentes
- [ ] Export Excel — **no**
- [ ] Tests — ver seccion Tests
- [ ] Retiro legacy requisiciones — controller, vistas, requests, rutas

## Servicios y deuda tecnica

### `NotificationConfigService` (capa canonica)

Responsabilidades:

| Metodo (orientativo) | Uso |
| --- | --- |
| `listModulesWithTypes(): array` | Admin: agrupar tipos por `module` con labels legibles (mapa config o `config('access.modules')` / constantes). |
| `typesWithAssignedEmailIds(?string $module = null): array` | Admin: matriz tipo ↔ ids correo. |
| `activeEmails(): Collection` | Admin: catalogo ordenado. |
| `storeEmail / updateEmail / destroyEmail` | Admin CRUD. |
| `syncTypeEmails(NotificationType $type, array $emailIds): void` | Admin PATCH. |
| `recipientEmails(string $module, string $slug): array` | Runtime envio (requisiciones y futuros modulos). |

**Fallback:** constante privada igual a `desarrollo.tic@sjsp.com.co`.

### `RequisitionNotificationRecipientService`

- **v1:** eliminar tras mover callers a `NotificationConfigService`, **o** convertir en fachada delgada que delega `recipientEmails('requisitions', $slug)` para diff minimo en `RequisitionController` (preferible **una sola llamada directa** al servicio global y borrar la clase requisiciones-specific).
- Corregir en el mismo slice la referencia a `RequisitionNotificationEmail` sin import en el servicio actual (afecta `syncTypeEmails`, no listado).

### Que retirar de `RequisitionController` / Parámetros

| Elemento | Accion |
| --- | --- |
| `PARAMETER_TYPES['emails']` | Eliminar entrada completa. |
| `parameters()` | Quitar variables `notificationTypes`, `notificationEmailOptions`, `showNotificationTypes`; no inyectar servicio solo para UI tipos. |
| `syncNotificationTypeEmails()` | Eliminar metodo. |
| Imports | `SyncRequisitionNotificationTypeRequest`, modelos email/type si ya no se usan en este controlador. |
| Envio correos en `store` / flujo gerencia | Sustituir `$this->notificationRecipients->emailsForType(...)` por `NotificationConfigService::recipientEmails('requisitions', ...)`. |

**Vistas:**

- `resources/views/modules/requisitions/parameters.blade.php` — quitar tarjeta y seccion `notification-types` y tarjeta catalogo `emails`.
- `resources/views/modules/requisitions/partials/notification-types.blade.php` — **eliminar** archivo.

**Requests:**

- `App\Http\Requests\Requisitions\SyncRequisitionNotificationTypeRequest` — **eliminar**; equivalente bajo `App\Http\Requests\Admin\...`.

## Componentes reutilizables

| Componente | Uso |
| --- | --- |
| `NotificationConfigService` | Unica fuente de verdad para admin y runtime. |
| Patron `SupplySiteController` / `UserController` | Rutas en `web.php` grupo `admin.*`, vistas bajo `resources/views/admin/`. |
| `PermissionCatalog::sync()` | Registra `manage.notifications` desde `system_permissions`. |
| `NavigationResolver` | Ajuste para mostrar Administracion con items por permiso item-level. |
| Estilos `.panel`, `.module-tab`, nav chrome | Alinear con [`nav-chrome-ui.mdc`](../../.cursor/rules/nav-chrome-ui.mdc) si el tablero usa pestanas (p. ej. «Correos» / «Tipos por modulo»). |

## Documentacion a actualizar

- [ ] `docs/modules/notifications-config.md` — **nuevo**: esquema, permiso, rutas, extension multi-modulo.
- [ ] `docs/modules/requisitions.md` — quitar referencias a Parámetros para correos/tipos; apuntar a config global.
- [ ] `docs/user/notifications-config.md` — **nuevo**: objetivo, alcance, responsabilidades admin.
- [ ] `docs/user/requisitions.md` — Parámetros ya no incluyen notificaciones.
- [ ] `docs/ACCESS_CONTROL.md` — permiso `manage.notifications`.
- [ ] `docs/INDEX.md` — enlace al modulo admin notificaciones si aplica.
- [ ] `README.md` — solo si menciona correos en Parámetros.

## Archivos compartidos (`shared-files`)

Marcar **`shared-files: true`** en el plan AgentSj / Task Cards que toquen:

| Archivo | Motivo |
| --- | --- |
| `config/access.php` | `system_permissions`, `admin_ui.global_groups`, `navigation.administracion` |
| `routes/web.php` | Rutas admin notificaciones + posible ajuste middleware grupos |
| `app/Services/Navigation/NavigationResolver.php` | Visibilidad sidebar Administracion sin `manage.users` obligatorio |
| `database/seeders/RoleAndPermissionSeeder.php` | Documentar que `administrador` no recibe `manage.notifications` por defecto |
| `routes/modules/requisitions.php` | Eliminar ruta tipos-notificacion |
| `app/Http/Controllers/Requisitions/RequisitionController.php` | Retiro parametros + envio via servicio global |
| `resources/views/modules/requisitions/parameters.blade.php` | Retiro UI |
| `tests/Feature/RequisitionModuleTest.php` | Regresion envio + parametros |

**Ownership principal admin notificaciones (sin flag global salvo convencion):**

- Migracion, modelos, servicio, controlador admin, requests admin, vistas admin, tests admin, docs nuevo modulo.

## Task cards sugeridas (vertical slices)

Orden recomendado para AgentSj (un agente feature a la vez; respetar shared-files):

### FEAT-013-T1 — Esquema y capa de dominio

- Migracion rename + `module` + seed idempotente.
- Modelos `NotificationEmail`, `NotificationType`; eliminar modelos `RequisitionNotification*`.
- Implementar `NotificationConfigService` + sustituir uso en envio requisiciones.
- Tests: `recipientEmails` con pivot; regresion tests mail existentes en `RequisitionModuleTest`.
- **Sin UI admin aun**; datos editables via tinker/seed o migracion.

### FEAT-013-T2 — Admin UI, permisos y navegacion (`shared-files`)

- `manage.notifications` en `access.php` + item sidebar + ajuste `NavigationResolver`.
- `NotificationConfigController` + vistas + rutas `web.php` (grupo `permission:manage.notifications`).
- Form requests admin.
- Tests: 403 sin permiso, 200 con permiso, CRUD correo, sync tipo.

### FEAT-013-T3 — Limpieza requisiciones y cierre (`shared-files`)

- Retirar `PARAMETER_TYPES['emails']`, UI partials, ruta sync, request requisitions.
- Assert 404 en CRUD parametros `type=emails`.
- Documentacion tecnica/usuario + actualizar requisitions.
- Test: GET parametros GH/operaciones sin textos «Correos de notificacion» / «Tipos de notificacion».

**Nota operativa (no task de codigo GH):** checklist despliegue — ejecutar migraciones pendientes antes de validar admin en produccion.

## Criterios de aceptacion

1. Usuario con `manage.notifications` abre **Administracion → Configuracion de notificaciones** y ve al menos los **2 tipos** de requisiciones con etiqueta y descripcion; puede gestionar correos y asignarlos por tipo.
2. Usuario **sin** `manage.notifications` recibe **403** en rutas `admin.notifications.*`.
3. Usuario con `manage.requisition.parameters` en cualquier area **no** ve correos ni tipos en Parámetros; PATCH legacy `tipos-notificacion` ya no existe (404).
4. Crear requisicion y flujo cargo nuevo envian correo a destinatarios segun asignacion en admin (tests existentes adaptados a tablas/modelos nuevos).
5. Tras migracion, datos previos en `requisition_notification_*` siguen disponibles bajo tablas renombradas con `module = requisitions`.
6. Rol `administrador` por defecto **no** incluye `manage.notifications` salvo asignacion manual.
7. Sidebar muestra item notificaciones a usuario con solo ese permiso (sin exigir `manage.users`).
8. No hay pantalla de historial de envios ni alta manual de tipos en v1.

## Validacion local

1. `php artisan migrate`
2. Asignar `manage.notifications` a un usuario de prueba; CRUD correo y asignacion a `new_requisition`.
3. Usuario GH con parametros: verificar Parámetros sin secciones de notificacion.
4. Flujo crear requisicion + mail fake/assert destinatarios.
5. `php artisan test --compact` (archivos de feature afectados).
6. `vendor/bin/pint --dirty` en PHP modificado.

## Tests (minimos)

| Test | Intencion |
| --- | --- |
| `test_notification_admin_forbidden_without_permission` | GET admin notificaciones → 403. |
| `test_notification_admin_lists_requisition_types` | GET con permiso → contiene slugs/labels seed. |
| `test_notification_email_crud_and_sync` | POST/PATCH correo; PATCH sync tipo; pivot en BD. |
| `test_requisition_mail_uses_global_config` | Adaptar tests actuales de `RequisitionModuleTest` (tipos/correos). |
| `test_parameters_excludes_notification_sections` | GET parametros GH y otra area sin copy/tarjetas notificacion. |
| `test_parameters_emails_crud_returns_404` | store/update/destroy `type=emails` → 404. |
| `test_legacy_notification_types_sync_route_removed` | PATCH tipos-notificacion → 404. |

## Riesgos y dependencias

| Riesgo | Mitigacion |
| --- | --- |
| Rename de tablas y FKs en hosting compartido | Probar migracion en copia BD; script down documentado; backup previo. |
| Usuarios que solo gestionaban correos desde Parámetros area | Comunicar nuevo tablero y permiso `manage.notifications`. |
| Entorno prod sin migracion 2026_07_28 | `migrate` antes de UAT admin; bug GH no se «arregla» localmente en Parametros. |
| Sidebar oculto para rol solo-notificaciones | Cambio `NavigationResolver` / quitar permission a nivel modulo administracion. |
| Borrado de correo con pivots | Preferir desactivar (`is_active`) o validar 422 al delete si hay asignaciones. |
| Dependencia | `spatie/laravel-permission`, Mail requisiciones existente, middleware estandar auth/active/password.changed. |

## Aprobacion

- [x] Analista — preguntas 1–5 cerradas; pregunta 6 supuesto fuera v1
- [x] Arquitecto — brief final
- [ ] Usuario — confirmacion explicita del brief
- [ ] AgentSj — plan de orquestacion y Task Card(s) en `docs/TASKS.md`
