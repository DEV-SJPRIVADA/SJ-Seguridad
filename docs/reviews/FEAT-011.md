# Review Report — FEAT-011

> Generado por el Revisor. Guardar en `docs/reviews/FEAT-011.md`.

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-011 |
| Fecha | 2026-07-28 |
| Alcance revisado | Cambios sin commit en workspace (`git diff`); brief [`docs/briefs/FEAT-011.md`](../briefs/FEAT-011.md) |
| Veredicto | **Aprobado con observaciones** |

## Criterios de aceptacion (checklist)

| # | Criterio (brief) | Resultado |
| --- | --- | --- |
| 1 | Migración: FK `users`, drop catálogo, `recruiter_id` nullificado | OK — migración única; test `test_recruiter_schema_uses_users_and_drops_recruiters_catalog` |
| 2 | Parámetros GH: toggles → `requisitions.selection_officer` | OK — servicio + PATCH + test toggle |
| 3 | Otras áreas: sin UI encargados; PATCH fuera de GH → 404 | OK — vista condicional + `abort_unless` + test 404 |
| 4 | Select: habilitados; asignado actual visible tras toggle off | OK — `recruitersForSelect` + tests |
| 5 | Persistencia `users.id`; no escribir `recruiter_name` desde HTTP | OK — update sin `recruiter_name`; reglas Store/Update sin campo |
| 6 | Presentación unificada (detalle/export/impresión) | OK — `displayRecruiterName()` en export e impresión |
| 7 | CRUD parámetros `recruiters` → 404 | OK — retirado de `PARAMETER_TYPES` + test |
| 8 | Tests + Pint | OK — 10 tests FEAT-011 ejecutados en verde (37 assertions); formato coherente en PHP tocado |

## Hallazgos

### Bloqueantes

Ninguno.

No se detecta bypass de auth: rutas bajo `auth`, `active`, `password.changed` y `requisition.tab:parametros` (permiso `manage.requisition.parameters` vía `RequisitionAccessService`). El PATCH adicional exige `{module} === gestion_humana` (404 en otros módulos). Validación de `recruiter_id` combina `exists:users,id` con `ValidRequisitionRecruiterUser` / `isAllowedRecruiterId` (permiso + área GH activa, o mismo ID ya guardado).

### Observaciones (no bloqueantes)

| # | Archivo | Descripcion | Sugerencia |
| --- | --- | --- | --- |
| 1 | `RequisitionController::updateSelectionOfficer` | Sin `AuditLogService` al activar/desactivar encargado (sí existe paridad en `IndicadorController::updateCapturador`). | Opcional en este slice; Documentador puede citarlo como mejora futura (brief: recomendado, no bloqueante). |
| 2 | `StorePersonalRequisitionRequest` | Sigue aceptando y el `store` persiste `recruiter_id` en tab **Solicitar** si viene en el body; la regla custom mitiga IDs no habilitados, pero el flujo de negocio asume asignación en Gestión. | Valorar ignorar/forzar `null` en `store` si el formulario de solicitud no expone el campo (defensa en profundidad). |
| 3 | `RequisitionSelectionOfficerAccessService::isAllowedRecruiterId` | La excepción «mismo `existingRecruiterId`» no revalida `area_key` GH ni permiso (correcto para legacy y reclutador deshabilitado, pero permitiría conservar un `users.id` atípico si existiera en BD). | Aceptable tras nullificar migración; Documentador debe explicar reasignación manual post-deploy. |
| 4 | `config/access.php` | `requisitions.selection_officer` aparece en `admin_ui.global_groups.requisitions` (asignación manual en Admin). | Alineado con brief («toggle o super-admin»); doc usuario debe dejar claro que la vía oficial es Parámetros GH. |
| 5 | Tests | No hay caso explícito «PATCH toggle → 403 sin `manage.requisition.parameters`» (cubierto por middleware + authorize duplicado en Form Request). | Opcional añadir test de regresión de permiso. |
| 6 | Documentación | `docs/modules/requisitions.md` y resto de entregables doc aún referencian catálogo antiguo (fuera del diff de feature). | Corresponde al **Documentador** post-aprobación. |
| 7 | `RequisitionController.php` (diff) | Reorden de imports y cambios de estilo Pint mezclados con la feature. | Sin impacto funcional; mantener alcance en futuros commits si se separa historial. |

## Checklist de revision

- [x] Auth y permisos correctos (`AGENTS.md`) — toggle atado a tab Parámetros; permiso nuevo registrado en `system_permissions` sin otorgarse por defecto en roles base (solo Spatie directo vía toggle)
- [x] Sin registro publico ni bypass de middleware
- [x] Validacion de entradas (Form Requests) — `UpdateRequisitionSelectionOfficerRequest` (`enabled` boolean); Store/Update con regla custom
- [x] Sin duplicacion innecesaria — servicio espejo de capturadores; helper `displayRecruiterName()`
- [x] Rutas en archivo de modulo correcto — `routes/modules/requisitions.php`, orden antes de `{type}` genérico
- [x] Migraciones compatibles con hosting compartido — drop FK, update masivo, add FK, drop table; `down()` reversible con recreación de catálogo
- [x] Export Excel usa `BaseExport` — sin cambio de mecanismo; columna vía helper
- [x] Tests relevantes presentes — suite mínima del brief cubierta en `RequisitionModuleTest.php`

## Seguridad

**Toggle encargados**

- Ruta: `PATCH …/parametros/encargados-seleccion/{user}` dentro de `requisition.tab:parametros`.
- Controlador: `abort_unless($module === 'gestion_humana', 404)`.
- Servicio: `setSelectionOfficerEnabled` rechaza usuarios fuera de GH o inactivos (`InvalidArgumentException` → error de sesión, no 500).
- Tests: grant/revoke Spatie, usuario Operaciones en PATCH GH → error de validación de negocio, PATCH con `module=operaciones` → 404.

**Validación `recruiter_id`**

- `Rule::exists('users', 'id')` + `ValidRequisitionRecruiterUser`.
- Nuevas asignaciones: usuario activo, `area_key = gestion_humana`, permiso `requisitions.selection_officer`.
- Edición sin cambiar reclutador: ID existente permitido aunque el usuario ya no esté habilitado (evita bloqueo de gestión y mantiene trazabilidad).
- Test rechaza GH sin permiso; test logger resuelve nombre de usuario.

**Alcance módulo GH (configuración)**

- UI toggles solo si `$module === gestion_humana` en `parameters()`.
- Listado de configuración: solo usuarios activos GH (`gestionHumanaAreaUsers`).
- Select global de reclutador: pool GH habilitados (+ actual asignado), independiente del `{module}` del tablero (conforme brief «pool único»).

**Riesgos residuales (aceptados)**

- Asignación manual del permiso en Admin UI por super-admin.
- Posible `recruiter_id` en creación vía tab Solicitar si el cliente HTTP envía el campo (obs. 2).

## Consistencia con AGENTS.md y docs

- Permiso en `config/access.php` (`system_permissions` + grupo Admin UI); módulo requisitions desacoplado (servicio, regla, partial, migración).
- Modelo catálogo eliminado; relación `PersonalRequisition::recruiter()` → `User`.
- Documentación viva pendiente de actualizar (obs. 6) — no bloquea código.

## Siguiente paso

- [x] Pasar a Documentador (si aprobado)
- [ ] Devolver a Agente Feature (si bloqueado)

**Notificacion AgentSj:** veredicto **Aprobado con observaciones**. Puede lanzar Documentador. Priorizar en doc: permiso `requisitions.selection_officer`, toggles solo GH, legacy `recruiter_name`, checklist post-deploy. Observaciones 1–2 y 5 son mejoras opcionales para iteración futura.
