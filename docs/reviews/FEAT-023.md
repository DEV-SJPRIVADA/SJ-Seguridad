# Review Report — FEAT-023

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-023 |
| Fecha | 2026-08-04 |
| Revisor | Agente Revisor |
| Brief | [`docs/briefs/FEAT-023.md`](../briefs/FEAT-023.md) |
| Alcance revisado | Captura delegada indicadores: `config/access.php`, `IndicatorCaptureAccessService`, `IndicatorCaptureService`, `IndicadorController`, `StoreIndicatorCaptureRequest`, rutas Operaciones, vistas `show`/`capturadores`, `IndicadorNavigation`, `User`, tests |
| Veredicto | **Aprobado** — apto para pasar a Documentador |

## Verificacion de criterios de aceptacion (brief, seccion "Criterios de aceptacion")

| # | Criterio | Estado | Evidencia |
| --- | --- | --- | --- |
| 1 | Solo `operations.capture.delegate` captura a nombre del titular; BD `user_id` titular, `created_by`/`updated_by` suplente | OK | `IndicatorCaptureService::save()` persiste titular/actor; tests `test_delegate_user_can_store_capture_for_titular`, `test_delegated_capture_sets_user_id_titular_and_created_by_actor` |
| 2 | Solo `operations.capture` mantiene flujo actual (tres IDs = actor; sin selector) | OK | `resolveTitularUser()` fuerza self; vista oculta selector si `!canDelegateCapture`; test `test_capture_only_user_self_capture_regression` |
| 3 | Ambos permisos: captura propia o delegada | OK | Tests `test_user_with_both_permissions_defaults_to_self_on_show`, `test_user_with_both_permissions_can_delegate_to_other_capturador` |
| 4 | `capturador_id` / `capturador_user_id` invalidos → 404 (show) o 422 (store) | OK | `resolveTitularUser()` abort 404; Form Request `Rule::in($capturableIds)`; tests `test_show_with_invalid_capturador_id_returns_not_found`, `test_store_with_invalid_capturador_user_id_returns_unprocessable` |
| 5 | `operations.manage` sin delegate no puede capturar por suplencia | OK | `canDelegateCapture()` independiente de manage; test `test_operations_manage_without_delegate_cannot_access_delegate_flow` |
| 6 | Ajustes → Capturadores: columna Suplencia independiente; otorga delegate + minimos sin `operations.capture` | OK | `capturadores.blade.php`, `updateCapturadorDelegate()`, `setDelegateCaptureEnabled()`; tests toggle enable/disable y `test_delegate_toggle_does_not_grant_operations_capture` |
| 7 | Tab Captura visible si `canAccessCaptureScreen` | OK | `IndicadorNavigation`, `User::canAccessIndicadorTab('capture')`, `EnsureIndicadorAccess`; test `test_delegate_user_can_access_captura_tab` |
| 8 | Auditoria create/update con metadata de delegacion cuando titular ≠ digitador | OK | `IndicatorCaptureService::save()` metadata `{ delegated, titular_user_id, actor_user_id }`; test `test_delegated_capture_audit_includes_delegation_metadata` |
| 9 | Dashboard, ranking y consolidado reflejan titular (`user_id`) | OK | Sin cambio de esquema; `save()` persiste `user_id` = titular; consolidado ya filtra por `user_id` existente |
| 10 | Periodo cerrado bloquea guardado delegado | OK | Misma validacion en `save()`; test `test_closed_period_blocks_delegated_capture` |
| 11 | Tests listados en verde | OK | 18/18 passed (ver seccion Tests) |

## Hallazgos

### Bloqueantes

| # | Archivo | Descripcion | Accion requerida |
| --- | --- | --- | --- |
| — | — | Sin hallazgos bloqueantes | — |

### Observaciones (no bloqueantes)

| # | Archivo | Descripcion | Sugerencia |
| --- | --- | --- | --- |
| 1 | `docs/modules/indicadores.md`, `docs/user/indicadores.md` | Documentacion de modulo/usuario pendiente segun brief (alcance Documentador) | Completar en fase Documentador |
| 2 | `IndicatorCaptureAccessService::resolveTitularUser()` | Usuario con `operations.capture` + `operations.capture.delegate` pero no ∈ `capturableUsers()` y sin `capturador_id` recibe HTTP 422 en GET show (caso atipico: permisos desalineados con area/activo) | Bajo riesgo en produccion; opcional: renderizar show con selector y mensaje en lugar de abort 422 |
| 3 | `IndicatorCaptureService::persistImprovement()` | Auditoria de `improvement` no incluye metadata de delegacion (solo `indicator_capture`) | Alineado con brief; ampliar solo si negocio lo pide |
| 4 | `show.blade.php` | Campo hidden `capturador_user_id` se envia siempre, incluso para usuarios solo-captura | Inofensivo: `resolveTitularUser()` ignora el parametro si no hay delegate |
| 5 | `capturadores.blade.php` | Toggle Suplencia disponible tambien para usuarios con `operations.manage` (no bloqueado como Captura) | Coherente con brief; no exige restriccion adicional |

## Checklist de revision

- [x] Auth y permisos correctos (`AGENTS.md`)
- [x] Sin registro publico ni bypass de middleware
- [x] Validacion de entradas (Form Requests)
- [x] Sin duplicacion innecesaria
- [x] Rutas en archivo de modulo/area correcto (`routes/areas/operaciones.php`)
- [x] Migraciones compatibles con hosting compartido (N/A — sin migracion)
- [x] Export Excel usa `BaseExport` si aplica (N/A — sin export nuevo)
- [x] Tests relevantes presentes (18 tests del brief en `IndicadorModuleTest.php`)

## Seguridad y permisos

| Control | Estado | Detalle |
| --- | --- | --- |
| Middleware captura | OK | `indicador.tab:capture` → `User::canAccessIndicadorTab('capture')` → `canAccessCaptureScreen()` |
| Autorizacion POST | OK | `StoreIndicatorCaptureRequest::authorize()` exige `canAccessCaptureScreen` |
| Resolucion titular centralizada | OK | Un solo metodo `resolveTitularUser()`; capture-only ignora IDs ajenos |
| Lista blanca titulares | OK | Solo IDs ∈ `capturableUsers()` (404/422) |
| Permiso delegate independiente | OK | `operations.capture.delegate` no implica `operations.capture` ni `operations.manage` |
| Toggle suplencia | OK | Ruta bajo `indicador.tab:manage`; validacion area/activo en servicio |
| Usuarios inactivos / otra area | OK | `canDelegateCapture()` y `canCaptureIndicators()` verifican `is_active` + `area_key=operaciones` |
| Periodo cerrado | OK | Bloqueo en servicio antes de persistir |
| Auditoria delegacion | OK | Metadata solo cuando titular ≠ actor; captura propia sin metadata extra |
| IDOR / bypass | OK | Suplente no puede elegir titular fuera de lista; capture-only no puede suplantar via POST |

## Consistencia con AGENTS.md y docs

- Modulo desacoplado en area Operaciones (controlador, rutas, vistas, servicios).
- Permiso registrado en `config/access.php` (`area_indicador_permissions` + board Operaciones subgroup `indicadores`).
- Navegacion chrome alineada: `IndicadorNavigation` e `User::indicadorBoardTabsFor()` usan `canAccessCaptureScreen`.
- Sin comandos destructivos de BD; columnas titular/digitador reutilizan esquema existente.
- Documentacion viva pendiente para Documentador (`docs/modules/indicadores.md`, `docs/user/indicadores.md`).

## Tests

Comando ejecutado:

```bash
php artisan test --compact tests/Feature/IndicadorModuleTest.php --filter="delegate|invalid_capturador|self_capture_regression|both_permissions_defaults"
```

| Resultado | Detalle |
| --- | --- |
| **18 passed** | 53 assertions, ~52s |

Tests del brief cubiertos:

- `test_delegate_user_can_access_captura_tab`
- `test_delegate_only_user_show_defaults_to_first_capturador`
- `test_delegate_only_user_show_with_valid_capturador_id`
- `test_show_with_invalid_capturador_id_returns_not_found`
- `test_delegate_user_can_store_capture_for_titular`
- `test_delegated_capture_sets_user_id_titular_and_created_by_actor`
- `test_delegated_capture_audit_includes_delegation_metadata`
- `test_store_with_invalid_capturador_user_id_returns_unprocessable`
- `test_delegate_only_user_cannot_store_without_capturador_user_id`
- `test_capture_only_user_self_capture_regression`
- `test_user_with_both_permissions_defaults_to_self_on_show`
- `test_user_with_both_permissions_can_delegate_to_other_capturador`
- `test_operations_manage_without_delegate_cannot_access_delegate_flow`
- `test_operations_manage_user_can_enable_delegate_for_operaciones_user`
- `test_operations_manage_user_can_disable_delegate_for_operaciones_user`
- `test_delegate_toggle_does_not_grant_operations_capture`
- `test_delegated_improvement_created_by_reflects_actor`
- `test_closed_period_blocks_delegated_capture`

## Siguiente paso

- [x] Revisor — **Aprobado**, sin bloqueantes.
- [ ] Documentador — `docs/modules/indicadores.md`, `docs/user/indicadores.md`, `docs/INDEX.md` si aplica.
