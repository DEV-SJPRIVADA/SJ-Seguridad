# Feature Brief — FEAT-011

> Version final (Arquitecto). Decisiones de negocio cerradas 2026-07-28 (ver [`FEAT-011-analyst.md`](FEAT-011-analyst.md)).

## Identificacion

| Campo | Valor |
| --- | --- |
| ID | FEAT-011 |
| Modulo / area | `requisitions` (tablero GH — Parámetros; impacto global en pestaña Gestión / Reclutador) |
| Titulo | Encargados de selección = usuarios GH activables (sin catálogo `requisition_recruiters`) |
| Solicitante | Manuel-E |
| Fecha | 2026-07-28 |

## Objetivo

Reemplazar el catálogo manual de encargados de selección por **usuarios reales de Gestión humana**, habilitados con **toggles** en Parámetros (mismo patrón que Operaciones → Capturadores). La requisición guardará `recruiter_id` apuntando a `users.id`, eliminando la tabla `requisition_recruiters` y alineando select, historial, export e impresión con identidad de usuario.

**Para quién:** personal GH con `manage.requisition.parameters` (configuración) y quienes gestionan solicitudes con `requisitions.tab.gestion` (asignación de Reclutador en cualquier área solicitante).

## Alcance

### Incluye

1. **Migración BD:** eliminar FK a `requisition_recruiters`, poner `personal_requisitions.recruiter_id` en `NULL` en todas las filas (sin emparejar catálogo), crear FK nullable a `users.id`, eliminar tabla `requisition_recruiters` y modelo asociado.
2. **Permiso Spatie** `requisitions.selection_officer` — otorgado/revocado por toggle; define quién aparece en el select **Reclutador** (junto con cuenta activa y `area_key = gestion_humana`).
3. **Servicio** `App\Services\Requisitions\RequisitionSelectionOfficerAccessService` (espejo de `IndicatorCaptureAccessService`): listado de usuarios GH para configuración, listado para select, `setSelectionOfficerEnabled(User, bool)`.
4. **UI Parámetros (solo `module = gestion_humana`):** tarjeta «Encargados de selección» abre **tabla con toggles** (partial dedicado); deja de usarse el CRUD genérico de `PARAMETER_TYPES['recruiters']`.
5. **Ruta PATCH** para toggle + integración en `RequisitionController` (o método dedicado invocado desde el mismo controlador del módulo).
6. **Formulario Gestión:** select alimentado por el servicio; **no** persistir `recruiter_name` desde el formulario (columna legacy solo lectura en BD).
7. **Modelo** `PersonalRequisition`: relación `recruiter()` → `User`; helper de presentación `displayRecruiterName(): string` → `recruiter?->name ?? recruiter_name ?? '—'`.
8. **Validación** en `StorePersonalRequisitionRequest` / `UpdatePersonalRequisitionRequest`: `recruiter_id` nullable, `exists:users,id`, regla de negocio vía servicio (usuario habilitado **o** mismo `recruiter_id` ya guardado en la requisición).
9. **PersonalRequisitionChangeLogger:** `FK_MODELS['recruiter_id']` → `User::class`; resolver etiqueta con `users.name` (incluye usuarios inactivos o sin permiso actual, por ID histórico).
10. **Export** `PersonalRequisitionFullExport`: eager load `recruiter` como `User`; columna «Encargado seleccion» con el helper unificado; mantener columna raw `recruiter_name` solo si tiene valor legacy (opcional: misma fila, sin duplicar lógica en más sitios).
11. **Impresión** `print.blade.php`: firma/reclutador con `displayRecruiterName()` o equivalente.
12. **Tests** mínimos (ver sección Tests).
13. **Documentación** técnica y usuario del módulo requisiciones.

### Fuera de alcance

- Migración automática catálogo → usuario (nombres, matching, re-asignación masiva).
- Poblar `recruiter_id` histórico desde `requisition_recruiters`.
- Cambiar reglas de compensación, estados, notificaciones por correo o matriz comercial.
- Campo texto editable «Nombre reclutador» en formularios nuevos.
- Pantalla de encargados en tableros de requisiciones **distintos** de `gestion_humana`.
- Permisos extra al activar toggle (p. ej. tableros GH) — solo `requisitions.selection_officer`, salvo que negocio pida paridad con capturadores en iteración futura.
- Exponer `requisitions.selection_officer` en Admin de usuarios como permiso asignable manualmente (solo vía toggle en Parámetros GH; el permiso sí se registra en Spatie vía `PermissionCatalog`).

## Reglas de negocio

1. **Pool único:** encargados de selección son siempre personal GH; el mismo listado alimenta el select en rutas `/requisitions/{module}/…` para cualquier `{module}` donde exista pestaña Gestión.
2. **Configuración:** solo en `/requisitions/gestion_humana/parametros` (tab Parámetros + permiso `manage.requisition.parameters`).
3. **Lista de configuración:** usuarios con `is_active = true` y `area_key = gestion_humana`, orden por `name`.
4. **Select Reclutador (nuevas asignaciones):** usuarios GH activos **con** permiso `requisitions.selection_officer`.
5. **Desactivación (toggle off):** no aparecen en el select; requisiciones que ya tienen su `users.id` siguen mostrando el nombre en detalle, historial de cambios, export e impresión.
6. **Migración de datos:** todas las filas quedan con `recruiter_id = null`; trazabilidad previa vía `recruiter_name` si existía en la requisición.
7. **Legacy `recruiter_name`:** no se edita desde el formulario de gestión; presentación = usuario si hay `recruiter_id`, si no texto legacy, si no «—».
8. **Post-despliegue:** GH debe reactivar encargados en toggles; no hay equivalencia automática con filas antiguas de `requisition_recruiters`.

## Permisos (`config/access.php`)

| Permiso | Rol(es) típicos | Descripcion |
| --- | --- | --- |
| `manage.requisition.parameters` | GH administrador de parámetros | Ver Parámetros y usar toggles de encargados (sin cambio de nombre). |
| `requisitions.tab.gestion` | GH gestores | Editar requisiciones y elegir Reclutador en el select. |
| `requisitions.selection_officer` | Usuarios GH habilitados por toggle | Aparecer como opción en select Reclutador. **No** se asigna por defecto a roles base; solo toggle o `super-admin`. |

**Registro:** añadir `requisitions.selection_officer` en `system_permissions` con etiqueta legible (p. ej. «Requisiciones: Actuar como encargado de selección») y en `admin_ui.global_groups.requisitions.permissions` para visibilidad en Admin (opcional: marcar como «solo toggle» en doc usuario).

**Sincronización Spatie:** `PermissionCatalog::sync()` vía `RoleAndPermissionSeeder` — no requiere cambio en `PermissionCatalog` si el permiso vive en `system_permissions`.

## Rutas

| Metodo | URI | Nombre | Archivo de rutas | Middleware / notas |
| --- | --- | --- | --- | --- |
| GET | `/requisitions/{module}/parametros` | `requisitions.parameters` | `routes/modules/requisitions.php` | Existente; vista condiciona sección encargados si `{module} === gestion_humana`. |
| PATCH | `/requisitions/{module}/parametros/encargados-seleccion/{user}` | `requisitions.selection-officers.update` | `routes/modules/requisitions.php` | `requisition.tab:parametros`; abort 404 si `{module} !== gestion_humana`; body `enabled` boolean. |
| POST/PATCH/DELETE | `/requisitions/{module}/parametros/recruiters` … | `requisitions.parameters.*` | `routes/modules/requisitions.php` | **Retirar** tipo `recruiters` de `PARAMETER_TYPES` → rutas devuelven 404 para `type=recruiters`. |

Rutas de gestión existentes (`requisitions.update`, export, etc.) sin cambio de path; solo cambian datos y validación de `recruiter_id`.

## Base de datos

| Tabla / cambio | Tipo | Notas |
| --- | --- | --- |
| `personal_requisitions.recruiter_id` | alter | Drop FK → `requisition_recruiters`; `UPDATE` set `recruiter_id = NULL`; add FK nullable → `users.id` (`nullOnDelete` o `restrict` según convención del proyecto para users). |
| `requisition_recruiters` | drop | Tras desacoplar FK; eliminar modelo `RequisitionRecruiter`. |
| `recruiter_name` | sin cambio | Columna nullable legacy; dejar de escribir desde HTTP. |
| `model_has_permissions` | datos | Usuarios ganan/pierden `requisitions.selection_officer` vía toggle (sin migración desde catálogo). |

**Orden sugerido en migración única (o dos pasos en una clase):** (1) drop foreign key old, (2) nullificar IDs, (3) add foreign key users, (4) drop table recruiters.

## Capas a implementar

- [ ] Migracion(es) — FK users + drop `requisition_recruiters`
- [ ] Modelo(s) — `PersonalRequisition` (relación User); eliminar `RequisitionRecruiter`
- [ ] Servicio — `RequisitionSelectionOfficerAccessService`
- [ ] Controlador(es) — `RequisitionController`: `parameters` (datos toggles), `updateSelectionOfficer`; quitar `recruiters` de `PARAMETER_TYPES` y de `catalogs()`
- [ ] Form Request(s) — `UpdateRequisitionSelectionOfficerRequest` (`enabled` required boolean); ajustar Store/Update personal requisition + regla custom `RecruiterId` o closure con servicio
- [ ] Vista(s) Blade — `parameters.blade.php` (tarjeta + sección); partial `selection-officers.blade.php` (estilo toggles capturadores); `form-fields.blade.php`; `print.blade.php`
- [ ] JavaScript (si aplica) — reutilizar patrón `onchange` submit del toggle en capturadores (sin nuevo bundle obligatorio)
- [ ] Export Excel — `PersonalRequisitionFullExport` (relación User + columnas)
- [ ] Tests — ver abajo
- [ ] Eliminar referencias — factory/seeder/tests que creen `RequisitionRecruiter`

## Componentes reutilizables

| Componente | Uso |
| --- | --- |
| `IndicatorCaptureAccessService` | **Patrón de referencia** (no modificar): área fija, listado usuarios área, toggle ↔ permiso Spatie. |
| `RequisitionSelectionOfficerAccessService` | **Nuevo** — lógica de negocio encapsulada; controlador delgado. |
| `PersonalRequisition::displayRecruiterName()` | Vista, export, impresión, evitar duplicar `??` chains. |
| `BaseExport` / `<x-export-excel>` | Sin cambio de mecanismo; solo mapeo de columnas. |
| `PersonalRequisitionChangeLogger` | Actualizar mapa FK a `User`. |
| Clases CSS toggles | Reutilizar clases `.toggle-switch` / tabla estilo `capturadores.blade.php` o `.supply-table` según branding requisiciones. |

**Auditoría (recomendado, no bloqueante):** log `admin_action` al activar/desactivar encargado (paridad `IndicadorController::updateCapturador`) vía `AuditLogService` si ya está inyectado en el proyecto para acciones admin.

## Documentacion a actualizar

- [x] `docs/modules/requisitions.md` — modelo de datos, permiso, Parámetros GH, legacy `recruiter_name`
- [x] `docs/user/requisitions.md` — cómo activar encargados, comportamiento al desactivar
- [x] `docs/ACCESS_CONTROL.md` — listar `requisitions.selection_officer`
- [ ] `docs/INDEX.md` — solo si el índice referencia catálogo recruiters
- [ ] `README.md` — no salvo mención explícita al catálogo antiguo

## Archivos compartidos (`shared-files`)

Marcar **`shared-files: true`** en Task Card FEAT-011:

- `config/access.php` — permiso nuevo + grupo Admin UI
- `routes/modules/requisitions.php` — ruta PATCH encargados
- `app/Http/Controllers/Requisitions/RequisitionController.php` — parámetros, catálogos, toggle
- `resources/views/modules/requisitions/parameters.blade.php`
- `database/seeders/RoleAndPermissionSeeder.php` — solo si se documenta permiso demo; sync ya cubre `PermissionCatalog`
- `app/Support/PermissionCatalog.php` — **no** debe requerir cambio si el permiso está en `system_permissions`

Archivos de módulo (ownership requisitions, no flag global salvo convención del plan):

- Migración, servicio, requests, model, export, logger, partials form/print, tests.

## Criterios de aceptacion

1. Tras `migrate`, no existe `requisition_recruiters` y `personal_requisitions.recruiter_id` referencia `users.id` (nullable); todos los IDs previos del catálogo quedaron en `NULL`.
2. En GH → Parámetros → Encargados de selección, la tabla lista solo usuarios activos con `area_key = gestion_humana`; el toggle persiste permiso `requisitions.selection_officer`.
3. En otras áreas con tab Parámetros, **no** aparece la tarjeta/sección de encargados; PATCH con `module != gestion_humana` responde 404.
4. Select Reclutador en Gestión muestra solo usuarios habilitados (permiso + activos GH); al editar una requisición con reclutador ya asignado, la opción actual permanece aunque el usuario esté deshabilitado para nuevas asignaciones.
5. Guardar gestión persiste `recruiter_id` como `users.id`; no se actualiza `recruiter_name` desde el formulario.
6. Detalle, historial de cambios, export e impresión muestran nombre de usuario si hay `recruiter_id`; si no, `recruiter_name` legacy; si no, «—».
7. CRUD genérico de parámetros `recruiters` (POST/PATCH/DELETE) ya no está disponible (404).
8. Tests listados en verde; `vendor/bin/pint --dirty` en PHP tocado.

## Validacion local

1. `php artisan migrate`
2. Usuario GH con `manage.requisition.parameters`: activar 1–2 encargados; verificar permiso en BD o `$user->can('requisitions.selection_officer')`.
3. Usuario con `requisitions.tab.gestion`: editar requisición (cualquier `{module}`), asignar Reclutador, guardar; ver FK a `users.id`.
4. Desactivar encargado en toggles; confirmar que no sale en select pero sí en requisición ya asignada.
5. Export Excel gestión: columna encargado coherente con reglas legacy.
6. `php artisan test --compact tests/Feature/RequisitionModuleTest.php` (y tests nuevos del feature).

## Tests (minimos)

| Test | Intención |
| --- | --- |
| Migración / esquema | FK a `users`; tabla recruiters eliminada; `recruiter_id` nullificado (assert post-migrate en test que corre migraciones). |
| `test_selection_officer_toggle_grants_and_revokes_permission` | PATCH toggle GH; Spatie permission on/off; usuario fuera de GH → error 422/403 según servicio. |
| `test_gestion_humana_parameters_includes_selection_officers_section` | GET parametros GH contiene tabla/toggles; GET parametros operaciones no. |
| `test_recruiter_select_only_enabled_gh_users` | Catálogo del edit incluye habilitados; excluye no habilitados. |
| `test_gestion_humana_can_persist_recruiter_id` | **Actualizar** test existente: usar `User` GH con permiso en lugar de `RequisitionRecruiter`. |
| `test_assigned_recruiter_visible_after_toggle_off` | Requisición con `recruiter_id` set; desactivar toggle; assert display/export sigue mostrando nombre. |
| `test_update_rejects_recruiter_id_without_permission` | POST/PATCH con `users.id` sin permiso → 422. |
| `test_change_logger_resolves_recruiter_id_to_user_name` | Log de cambio muestra nombre de usuario, no ID crudo. |
| `test_parameters_recruiters_crud_routes_return_404` | store/update/destroy con `type=recruiters` → 404. |

## Riesgos y dependencias

| Riesgo | Mitigación |
| --- | --- |
| Pérdida visual de encargado previo ligado solo al catálogo (`recruiter_id` old) | Comunicar a negocio: reasignación manual; conservar `recruiter_name` si existía en la fila. |
| Operación olvida reactivar toggles post-deploy | Checklist despliegue + doc usuario. |
| IDs huérfanos si migración incompleta | Nullificar antes de crear FK a users. |
| Confusión Admin UI si el permiso aparece asignable | Documentar que la vía oficial es Parámetros GH. |
| Dependencia | `spatie/laravel-permission`, columna `users.area_key`, middleware `requisition.tab:parametros`. |

## Aprobacion

- [x] Analista — preguntas 1–6 cerradas; propuesta #7 aceptada por usuario (2026-07-28)
- [x] Arquitecto — brief final
- [ ] Usuario — confirmacion explicita del brief (opcional antes de Feature)
- [ ] AgentSj — plan de orquestacion y Task Card(s)
