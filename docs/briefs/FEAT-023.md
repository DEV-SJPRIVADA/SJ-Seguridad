# Feature Brief — FEAT-023

> Feature Brief final — Arquitecto (2026-08-04). Decisiones de negocio en [`FEAT-023-analyst.md`](FEAT-023-analyst.md).

## Identificacion

| Campo | Valor |
| --- | --- |
| ID | FEAT-023 |
| Modulo / area | **Indicadores** — area Operaciones (`operaciones.*`, board `indicadores`) |
| Titulo | Captura delegada de indicadores (suplencia vacaciones) |
| Solicitante | Manuel-E (via `@agent-sj`) |
| Fecha | 2026-08-04 |

## Objetivo

Cuando un jefe capturador de Operaciones (`operations.capture`) está de vacaciones u otra ausencia, otra persona del área debe poder registrar los indicadores FT-OP **a nombre del titular**, de modo que dashboards, ranking, consolidado y exportaciones sigan atribuyendo la captura al jefe ausente y no al suplente.

Hoy `IndicadorController::show` / `storeCapture` y `IndicatorCaptureService::save` usan siempre al usuario autenticado como `user_id`, por lo que un suplente dejaría datos a su nombre. Esta feature introduce un permiso acotado de suplencia, un selector de capturador en la pantalla de captura y la separación semántica titular/digitador en `indicator_captures`.

**Para quien:** personal de Operaciones con permiso de suplencia (`operations.capture.delegate`), asignado por quien administra capturadores en Ajustes.

## Alcance

### Incluye

1. **Permiso nuevo** `operations.capture.delegate` en `config/access.php` (bloque `area_indicador_permissions.operaciones` y subgroup `indicadores` del board Operaciones), **independiente** de `operations.manage` y de `operations.capture`.
2. **Asignación del permiso** solo a usuarios activos con `area_key=operaciones`, vía toggle **Suplencia** en **Ajustes → Capturadores** (columna independiente del toggle **Captura**).
3. **Pantalla de captura** (`indicadores.show`): selector **Capturador** cuando aplique; lista = `IndicatorCaptureAccessService::capturableUsers()`; navegación GET con query `capturador_id`.
4. **Persistencia corregida** en `IndicatorCaptureService::save`:
   - `user_id` = capturador titular resuelto.
   - `created_by_user_id` / `updated_by_user_id` = usuario autenticado (digitador).
5. **Autorización centralizada** en `IndicatorCaptureAccessService` (`canAccessCaptureScreen`, `canDelegateCapture`, resolución de titular).
6. **Comportamiento sin delegación:** usuarios con solo `operations.capture` mantienen flujo actual (captura propia, sin selector).
7. **Auditoría:** eventos `indicator_capture` create/update con metadata `{ delegated: true, titular_user_id, actor_user_id }` cuando titular ≠ digitador.
8. **Mejoras:** `improvements.user_id` = titular; `created_by_user_id` = digitador en alta; no sobrescribir `created_by_user_id` en updates.
9. **Tests** feature (lista concreta abajo).
10. **Documentación** (Documentador): `docs/modules/indicadores.md`, `docs/user/indicadores.md`.

### Fuera de alcance

- Registro de suplencias temporales (fechas, calendario, aprobación del titular).
- Restringir el selector a capturadores pre-asignados al suplente.
- Incluir captura delegada automáticamente en `operations.manage`.
- Exigir `operations.capture` al suplente.
- Cambios en consolidado más allá del uso existente del filtro por capturador.
- Notificaciones por correo al titular cuando un suplente captura.
- Migraciones de esquema (columnas ya existen).
- Nuevas pestañas o tableros en navegación.

## Reglas de negocio

1. **Titular:** `indicator_captures.user_id` identifica al jefe capturador cuyos KPIs, ranking y consolidado se alimentan.
2. **Digitador:** `created_by_user_id` en alta; `updated_by_user_id` en cada actualización — usuario que operó el formulario (suplente o titular).
3. **Selector:** cualquier usuario activo retornado por `capturableUsers()` (Operaciones + `operations.capture` o `operations.manage`).
4. **Suplente sin `operations.capture`:** captura **solo** en modo delegado; debe tener titular válido (selector o default).
5. **Usuario con `operations.capture` y `operations.capture.delegate`:** puede captura propia o delegada; default = él mismo si ∈ `capturableUsers()`, si no → selector obligatorio.
6. **Usuario con solo `operations.manage`:** sin cambio en consolidado/ajustes; para capturar sigue necesitando `operations.capture` y/o `operations.capture.delegate`.
7. **Periodo cerrado:** misma validación que hoy — bloqueo de guardado.
8. **Unicidad:** una fila por `(indicator_id, user_id titular, period_id)`.
9. **Delegación detectada:** cuando `titular->id !== actor->id`; metadata de auditoría solo en ese caso.
10. **Sin confirmación adicional** al capturar por suplencia.

## Decisiones técnicas — resolución de titular

### Parámetros HTTP

| Contexto | Nombre | Tipo | Notas |
| --- | --- | --- | --- |
| GET `indicadores.show` | `capturador_id` | `int` opcional | Query al cambiar selector año/mes/capturador. |
| POST `indicadores.capture.store` | `capturador_user_id` | `int` | Hidden o select; validado en Form Request. |

**No usar** `capturador`, `user_id` ni otros alias.

### Método nuevo: `IndicatorCaptureAccessService::resolveTitularUser(User $actor, ?int $capturadorId): User`

| Perfil del actor | Regla |
| --- | --- |
| Solo `operations.capture` (sin delegate) | Titular = `$actor`. Ignorar `capturador_id` / forzar self. |
| Solo `operations.capture.delegate` (sin capture ni manage) | `capturador_id` debe ser ID ∈ `capturableUsers()`; si ausente → default = **primer** usuario de `capturableUsers()` ordenado por nombre; si lista vacía → `403`. |
| `operations.capture` + `operations.capture.delegate` | Si `capturador_id` válido → ese titular; si ausente y `$actor` ∈ `capturableUsers()` → self; si ausente y `$actor` ∉ lista → `422` en POST / selector obligatorio en show (sin default). |
| `operations.manage` (+ capture y/o delegate según caso) | Misma resolución que filas anteriores según permisos de captura/delegación; manage solo no implica delegate. |

**Códigos de error:**

- Titular no ∈ `capturableUsers()` → `404` (GET show, alineado con consolidado).
- POST sin `capturador_user_id` cuando es obligatorio → `422`.
- POST con ID inválido → `422` (`capturador_user_id`).

### Flujo `show`

1. Middleware `indicador.tab:capture` exige `canAccessCaptureScreen($actor)`.
2. Controller lee `capturador_id` del query (`$request->integer('capturador_id')` o null).
3. `resolveTitularUser($actor, $capturadorId)` → `$titular`.
4. `buildShowContext(..., titular: $titular, actor: $actor)` carga captura/hoja/gráficos del **titular**.
5. Vista recibe:
   - `capturableUsers`, `selectedCapturadorId` (= `$titular->id`),
   - `showCapturadorSelector` (= `canDelegateCapture($actor)`),
   - `captureUserName` = nombre del titular,
   - `isDelegatedCapture` (= `$titular->id !== $actor->id`).

### Flujo `save`

1. `StoreIndicatorCaptureRequest` autoriza con `canAccessCaptureScreen`.
2. Resuelve titular vía `resolveTitularUser($actor, capturador_user_id del POST)`.
3. `save(..., titular: $titular, actor: $actor)`:
   - Busca/crea por `(indicator_id, user_id: titular, period_id)`.
   - Persiste `user_id` = titular; `created_by_user_id` / `updated_by_user_id` = actor.
4. Si `$titular->id !== $actor->id`, auditoría incluye:

```php
metadata: [
    'delegated' => true,
    'titular_user_id' => $titular->id,
    'actor_user_id' => $actor->id,
]
```

Captura propia (`titular === actor`): sin metadata de delegación (comportamiento actual).

## Permisos (`config/access.php`)

| Permiso | Rol(es) tipicos | Descripcion |
| --- | --- | --- |
| `operations.capture` | Jefes capturadores Operaciones | Captura **propia**. |
| `operations.capture.delegate` | Suplentes Operaciones *(nuevo)* | Captura **a nombre de** cualquier capturador de `capturableUsers()`. **No** otorga `operations.capture`. |
| `operations.manage` | Administradores indicadores | Ajustes, consolidado; no incluye suplencia salvo asignación explícita. |
| `operations.view` / `operations.export` | Sin cambio | Sin cambio en esta feature. |

**Entrada en `area_indicador_permissions.operaciones`:**

```php
'operations.capture.delegate' => 'Indicadores: Capturar por suplencia',
```

**Incluir** en `access.php` → board Operaciones → subgroup `indicadores` → `permissions[]`.

### `IndicatorCaptureAccessService` — métodos nuevos

| Método | Comportamiento |
| --- | --- |
| `canDelegateCapture(User $user): bool` | Activo, `area_key=operaciones`, tiene `operations.capture.delegate`. |
| `canAccessCaptureScreen(User $user): bool` | `canCaptureIndicators($user)` **OR** `canDelegateCapture($user)`. |
| `delegatePermissionsToGrant(): array` | `operations.capture.delegate`, `operations.view`, `view.board.operaciones.indicadores`; si área configurada también `view.area.operaciones`. **Sin** `operations.capture`. |
| `setDelegateCaptureEnabled(User $user, bool $enabled): void` | Misma validación de área/activo que `setCaptureEnabled`; no aplica a manage-only bloqueado; grant/revoke solo `operations.capture.delegate` + permisos mínimos de `delegatePermissionsToGrant()` al activar. |
| `resolveTitularUser(User $actor, ?int $capturadorId): User` | Ver tabla de resolución arriba. |

### Sincronización Spatie

- `PermissionCatalog::configuredNames()` ya incluye claves de `area_indicador_permissions`; **no requiere cambio de código** en `PermissionCatalog.php` salvo que falle un test — basta registrar en `config/access.php` y ejecutar `PermissionCatalog::sync()` en tests/setup.
- Verificar que el permiso aparece en Admin de usuarios vía `ensureSystemPermissions` / sync existente.

## Rutas

| Metodo | URI | Nombre | Archivo | Cambio |
| --- | --- | --- | --- | --- |
| GET | `/operaciones/indicadores/captura/{indicator}` | `indicadores.show` | `routes/areas/operaciones.php` | Query opcional `capturador_id` (int). Sin ruta nueva. |
| POST | `/operaciones/indicadores/captura/{indicator}` | `indicadores.capture.store` | idem | Body `capturador_user_id` (int). |
| PATCH | `/operaciones/indicadores/admin/capturadores/{user}` | `indicadores.admin.capturadores.update` | idem | Sin cambio de contrato (toggle Captura). |
| PATCH | `/operaciones/indicadores/admin/capturadores/{user}/suplencia` | `indicadores.admin.capturadores.delegate.update` | idem | **Nueva.** Payload `{ enabled: bool }`; toggle Suplencia independiente. |

**Middleware:** actualizar `User::canAccessIndicadorTab('capture')` para delegar en `canAccessCaptureScreen` (o llamada equivalente al servicio), de modo que suplentes accedan al grupo `indicador.tab:capture`.

## Base de datos

| Tabla / cambio | Tipo | Notas |
| --- | --- | --- |
| `indicator_captures` | Sin migracion | Semántica titular/digitador en columnas existentes. |
| `indicator_improvements` | Sin migracion | `user_id` titular; `created_by_user_id` digitador en create. |
| `permissions` (Spatie) | sync | `operations.capture.delegate` vía `PermissionCatalog::sync()`. |

## Capas a implementar

- [ ] Migracion(es) — **N/A**.
- [ ] Modelo(s) — sin cambio de fillable; relaciones existentes.
- [ ] Servicio `IndicatorCaptureAccessService` — métodos listados arriba + `delegatePermissionsToGrant()`.
- [ ] Servicio `IndicatorCaptureService` — firmas `buildShowContext(..., User $titular, User $actor)` y `save(..., User $titular, User $actor)`; metadata auditoría delegada; `persistImprovement`: `user_id` titular, `created_by_user_id` actor solo en create.
- [ ] Controlador `IndicadorController` — `show`/`storeCapture` resuelven titular; nuevo `updateCapturadorDelegate`.
- [ ] Form Request `StoreIndicatorCaptureRequest` — `authorize()` con `canAccessCaptureScreen`; regla `capturador_user_id` condicional; resolución titular.
- [ ] Vista `indicadores/show.blade.php` — selector GET con `capturador_id`; hidden POST `capturador_user_id`; cabecera muestra titular.
- [ ] Vista `ajustes/partials/capturadores.blade.php` — columna **Suplencia** + toggle/form independiente.
- [ ] Navegacion — `IndicadorNavigation` tab `captura.visible` = `canAccessCaptureScreen`; `User::canAccessIndicadorTab('capture')` e `indicadorBoardTabsFor()` alineados.
- [ ] JavaScript — recarga GET al cambiar capturador (conservar year/month).
- [ ] Tests — ver lista abajo.

## Componentes reutilizables

- `IndicatorCaptureAccessService::capturableUsers()` — fuente del selector.
- Patrón toggle Ajustes → Capturadores (`setCaptureEnabled`) — espejo `setDelegateCaptureEnabled`.
- `AuditLogService::logModelChange(..., metadata: [...])` — delegación.
- UX selector consolidado (`consolidado/show-capture.blade.php`) — referencia dropdown capturador.

## Tests a escribir

Archivo principal: `tests/Feature/IndicadorModuleTest.php` (o `IndicadorDelegatedCaptureTest.php` si el módulo crece).

| Método de test | Qué verifica |
| --- | --- |
| `test_delegate_user_can_access_captura_tab` | Solo `operations.capture.delegate` + permisos mínimos → tab Captura y `indicadores.index` OK. |
| `test_delegate_only_user_show_defaults_to_first_capturador` | GET show sin `capturador_id` carga titular = primer `capturableUsers()`. |
| `test_delegate_only_user_show_with_valid_capturador_id` | GET con `capturador_id` válido muestra datos del titular. |
| `test_show_with_invalid_capturador_id_returns_not_found` | GET con ID fuera de lista → 404. |
| `test_delegate_user_can_store_capture_for_titular` | POST delegado persiste fila bajo titular. |
| `test_delegated_capture_sets_user_id_titular_and_created_by_actor` | BD: `user_id` titular, `created_by_user_id`/`updated_by_user_id` suplente. |
| `test_delegated_capture_audit_includes_delegation_metadata` | `audit_logs.metadata` con `delegated`, `titular_user_id`, `actor_user_id`. |
| `test_store_with_invalid_capturador_user_id_returns_unprocessable` | POST ID inválido → 422. |
| `test_delegate_only_user_cannot_store_without_capturador_user_id` | POST sin campo cuando obligatorio → 422. |
| `test_capture_only_user_self_capture_regression` | Solo `operations.capture`: sin selector; tres IDs = actor. |
| `test_user_with_both_permissions_defaults_to_self_on_show` | Capture + delegate, actor capturable, sin query → titular self. |
| `test_user_with_both_permissions_can_delegate_to_other_capturador` | Capture + delegate elige otro → `user_id` ≠ actor. |
| `test_operations_manage_without_delegate_cannot_access_delegate_flow` | Manage sin delegate: no suplencia; captura propia solo si capture. |
| `test_operations_manage_user_can_enable_delegate_for_operaciones_user` | PATCH suplencia activa permiso + mínimos sin `operations.capture`. |
| `test_operations_manage_user_can_disable_delegate_for_operaciones_user` | PATCH suplencia revoca `operations.capture.delegate`. |
| `test_delegate_toggle_does_not_grant_operations_capture` | Tras activar suplencia, usuario no tiene `operations.capture`. |
| `test_delegated_improvement_created_by_reflects_actor` | `indicator_improvements.created_by_user_id` = suplente en create delegado. |
| `test_closed_period_blocks_delegated_capture` | Periodo cerrado → error igual que captura propia. |

## Documentacion a actualizar

- [ ] `docs/modules/indicadores.md` — permiso, captura delegada, campos titular/digitador, Ajustes capturadores.
- [ ] `docs/user/indicadores.md` — procedimiento suplencia vacaciones.
- [ ] `docs/INDEX.md` (si aplica)
- [ ] `README.md` (si aplica)

## Archivos compartidos (`shared-files`)

| Archivo | Motivo |
| --- | --- |
| `config/access.php` | Permiso `operations.capture.delegate`, label español, inclusion en board Operaciones. |
| `app/Support/IndicadorNavigation.php` | Visibilidad tab Captura vía `canAccessCaptureScreen`. |
| `app/Models/User.php` | `canAccessIndicadorTab('capture')` e `indicadorBoardTabsFor()` deben incluir suplencia. |
| `app/Support/PermissionCatalog.php` | Coordinacion: permiso nuevo debe quedar en `configuredNames()` via config; validar sync en tests (cambio de codigo solo si hiciera falta). |

**Ownership:** Agente Feature modulo `indicadores` (Operaciones). Flag `shared-files` en `docs/TASKS.md` para los archivos anteriores.

## Criterios de aceptacion

1. Usuario con **solo** `operations.capture.delegate` abre captura, elige (o recibe default) un capturador de `capturableUsers()` y guarda; BD `user_id` = titular, `created_by_user_id` / `updated_by_user_id` = suplente.
2. Usuario con **solo** `operations.capture` captura como hoy: tres IDs = actor; sin selector de delegación.
3. Usuario con ambos permisos captura propia o delegada; al elegir otro titular, `user_id` ≠ digitador.
4. `capturador_user_id` / `capturador_id` fuera de `capturableUsers()` → 404 (show) o 422 (store).
5. `operations.manage` sin `operations.capture.delegate` no puede capturar por suplencia.
6. Ajustes → Capturadores: columna **Suplencia** independiente de **Captura**; otorga/revoca delegate + permisos mínimos (sin `operations.capture`).
7. Tab **Captura** visible si `canAccessCaptureScreen`.
8. Auditoría create/update con metadata de delegación cuando titular ≠ digitador.
9. Dashboard, ranking y consolidado reflejan **titular** (`user_id`).
10. Periodo cerrado bloquea guardado delegado.
11. `php artisan test` (tests listados) en verde.

## Validacion local

1. Asignar `operations.capture.delegate` a suplente (sin `operations.capture`).
2. Capturar indicador eligiendo titular → verificar BD y auditoría.
3. Ingresar como titular con `operations.capture` → regresión captura propia.
4. Verificar consolidado/dashboard filtrado por titular.
5. Probar toggles Captura y Suplencia independientes en Ajustes.
6. `php artisan test --compact --filter=Indicador`

## Riesgos y dependencias

| Riesgo | Mitigacion |
| --- | --- |
| Datos historicos con tres IDs iguales | No migrar retrospectivamente. |
| Suplente elige titular equivocado | Selector claro + auditoría con digitador. |
| Confusion manage vs suplencia | Tests + doc usuario. |
| `Improvement.created_by_user_id` sobrescrito en update | Solo set en create; update no toca `created_by_user_id`. |
| Desalineacion middleware / navegacion / servicio | Un solo metodo `canAccessCaptureScreen` como fuente de verdad. |

## Aprobacion

- [x] Analista — vacios cerrados (2026-08-04)
- [x] Arquitecto — brief final (2026-08-04)
- [ ] Usuario — confirmacion
