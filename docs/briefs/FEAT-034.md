# Feature Brief — FEAT-034

> Brief final del Arquitecto (2026-09-21). Consolida `docs/briefs/FEAT-034-analyst.md` + **Respuestas del usuario (2026-09-21)** + decisión de diseño crítica (encolado explícito post go-live). **No es borrador.**

## Identificacion

| Campo | Valor |
| --- | --- |
| ID | FEAT-034 |
| Modulo / area | Gestion humana — tablero **Cursos** (`cursos`); hooks en **Ficha empleados** |
| Titulo | Cola «Nuevos sin curso» en pestaña Registros (Cursos) |
| Solicitante | Usuario / AgentSj (chat 2026-09-21 cola pendientes cursos) |
| Fecha | 2026-09-21 |

## Objetivo

Avisar a operadores de **Cursos** (`cursos.edit`) cuántas personas **activas en ficha** ingresaron **después del go-live** y aún no tienen ningún registro en `employee_cursos`, con el mismo patrón visual de Ficha (icono + contador). Permitir sacar de la cola creando el primer curso u omitiendo («No aplica») con motivo opcional.

Hoy el dashboard de vigencia cubre ACTUALIZAR/VENCIDO, pero **no** el caso «cero cursos». La cola no debe inventariar el stock histórico ni mezclarse con renovaciones.

## Decisiones de negocio (ley — no reabrir)

| # | Tema | Decision |
| --- | --- | --- |
| 1 | Reingreso con historial de cursos | Si la cédula **ya tiene** alguna fila en `employee_cursos` (histórico), **no** se encola. |
| 2 | Omitir | Botón «No aplica / omitir» con **motivo opcional**. V1 **sin** UI de deshacer (supuesto analista confirmado por omisión). |
| 3 | Estado empleo | Solo cuentan / se listan filas de cola cuyo perfil en ficha tiene `employment_status = activo`. |
| 4 | Salida de cola | Primer curso (UI `store` o **insert** de import Cursos) **o** omitir. |
| 5 | Vigencia / renovar | **No** encolar por ACTUALIZAR/VENCIDO ni por actualizar curso existente. Otro flujo. |
| 6 | UI | Icono + contador como Ficha; tooltip / `title` / `aria-label` **«Nuevos sin curso»**; **solo icono visible** (texto al hover). |
| 7 | Ubicación | **Solo** pestaña **Registros** (`/registros`). No Dashboard ni Catálogo. |
| 8 | Permisos | Solo `cursos.edit` (y bypass `manage.users`) ve icono/contador/listado y puede omitir / agregar. **Sin permiso nuevo.** `cursos.view` solo **no** ve la cola. |
| 9 | Arranque | **Solo** ingresos nuevos al contratar / pasar a ficha / import ficha **desde go-live**. **No** inventariar activos actuales sin curso. |

## Decisiones tecnicas del Arquitecto

| Tema | Decision | Justificacion |
| --- | --- | --- |
| Modelo de cola | Tabla nueva **`employee_curso_pending`** (encolado **explícito**). **No** derivar «todos los activos sin `employee_cursos`». | Punto 9: el stock pre-deploy no debe aparecer; hace falta marca de encolado post-deploy. |
| Identidad | Clave operativa = `document_number` (cédula), alineada a `employee_cursos.document_number`. Guardar `employee_ficha_profile_id` y/o `personal_requisition_ficha_entry_id` como FK nullable de soporte. | Cursos ya operan por cédula; reingreso se evalúa por historial de cursos de esa cédula. |
| Estados de fila | `status`: `pending` \| `omitted` \| `resolved`. Contador/listado UI = solo `pending` + perfil `activo`. | Permite omitir sin reaparecer; resolved al primer curso. |
| Encolar (hook) | Servicio `EmployeeCursoPendingService::enqueueIfEligible(...)` llamado **solo** cuando una persona **entra a ficha** post-deploy: `FichaEmpleadosController::store` (alta manual y modo `desde`/promoción) y `EmployeeFichaImportService` al crear/mover a ficha (`moved_to_ficha_at` se setea). | Fuentes reales de «ingreso nuevo»; no al editar ficha ni al sync de requisición sin pasar a ficha. |
| Guardas al encolar | **No** encolar si: (a) ya existe ≥1 `employee_cursos` para la cédula; (b) ya existe fila `omitted` o `resolved` para esa cédula; (c) ya existe `pending` abierta; (d) opcional: perfil no activo al momento del enqueue (no debería ocurrir en alta). | Cumple reingreso + omitir + idempotencia import. |
| Desencolar | Al **crear** el primer curso (insert) para esa cédula: marcar `pending` → `resolved` (`resolved_at`, `resolved_by`, `resolved_via = first_curso`, opcional `employee_curso_id`). Al omitir: `omitted` + `omitted_at`/`omitted_by`/`omit_reason` nullable. | Regla 4. Updates/renovaciones de cursos existentes **no** tocan la cola. |
| Borrar último curso | Si se elimina el único `employee_cursos` de una cédula, **no** reencolar (alineado a reingreso / historial ya existió; y si estaba `resolved`/`omitted` permanece fuera). | Analista supuesto 6. |
| Desvinculación | Si pasa a no-activo con fila `pending`: **ocultar** del contador/listado (filtro `activo`); **no** borrar ni auto-omitir. Si vuelve a `activo` sin curso y sigue `pending`, reaparece. | Regla 3 sin perder la marca de go-live. |
| Go-live / migrate | Migración crea tabla **vacía**. **Prohibido** seeder/backfill que inventarie activos actuales sin curso. | Regla 9. |
| Permisos | Reutilizar `cursos.edit` + `CursosAccessService::canEdit`. **No** tocar `config/access.php`. | Usuario + expectativa shared-files. |
| UI patrón | Reutilizar clases/estilo de `ficha-empleados-filters__pending-link` + icono Remix/Lucide coherente + badge contador; en barra de filtros/acciones de `registros.blade.php`. Click → modo/listado cola (query `cola=nuevos-sin-curso` **o** modal/panel dedicado). Preferencia Arquitecto: **misma página Registros** con query `cola=nuevos-sin-curso` que reemplaza el listado de cursos por el de pendientes (análogo a pills Pendientes\|En ficha), para no inventar tablero nuevo. | Decisión 6–7; mínimo JS. |
| Acción «Agregar curso» | Desde fila de cola: abrir modal existente «Nuevo registro» precargando cédula/nombre (mismo `lookup`/modal Alpine). Al `store` exitoso → resolve. | Reusa CRUD actual. |
| Omitir | POST dedicado + Form Request; confirmación UI (SweetAlert o modal) con textarea motivo opcional. | Decisión 2. |
| Audit | Extender `CursosAuditLogService`: eventos `employee_curso_pending` actions `enqueue` (opcional, puede ser ruidoso en import → log agregado en import ficha o solo omit/resolve), `omit`, `resolve`. Módulo `cursos` ya en `config/audit.php` (sin cambio obligatorio). | AGENTS.md auditoría. |
| Import Cursos | Tras cada **insert** exitoso en `EmployeeCursoImportService`, invocar resolve-by-cedula si había `pending`. Updates/renovaciones no. | Regla 4. |
| Import Ficha | Tras alta/move a ficha de una cédula, `enqueueIfEligible` (idempotente por fila). | Regla 9 + fuentes de ingreso. |
| Repository | **No**. | Convención proyecto. |
| Migrate | Solo `php artisan migrate` incremental. **Prohibido** `migrate:fresh` / wipe. | Proteccion de datos. |

### Por qué no basta un query derivado

```text
❌ WHERE employment_status = activo AND NOT EXISTS (employee_cursos) AND NOT omitted
```

Eso **incluye** el stock pre-deploy. La decisión de negocio exige cola **solo post go-live** → hace falta **enqueue explícito** en los puntos de ingreso a ficha. La tabla `employee_curso_pending` es la fuente de verdad de «fue encolado después del deploy».

### Diagrama de ciclo de vida

```text
[Entrada a ficha post-deploy]
        │
        ▼
 enqueueIfEligible?
   ├── tiene employee_cursos?     → no
   ├── omitted/resolved previo? → no
   ├── pending abierta?         → no (idempotente)
   └── sí → INSERT status=pending
        │
        ├──── omitir (motivo opcional) ──► status=omitted  (no UI deshacer V1)
        │
        └──── primer curso (UI/import) ──► status=resolved
```

## Alcance

### Incluye

- Migración + modelo `EmployeeCursoPending` (+ factory).
- Servicio `EmployeeCursoPendingService` (enqueue, resolveByDocument, omit, countPendingActivos, listPendingActivos).
- Hooks en Ficha: `FichaEmpleadosController::store` (manual + `ficha_entry_id`) y `EmployeeFichaImportService` al setear `moved_to_ficha_at` / crear entrada en ficha.
- Hooks en Cursos: `CursosController::store` + `EmployeeCursoImportService` (solo inserts) → resolve.
- UI en `registros.blade.php`: icono + contador (solo `canEdit`), tooltip «Nuevos sin curso», listado de cola, omitir, atajo a crear curso.
- Rutas nuevas bajo el grupo existente `gestion-humana/cursos`.
- Auditoría omit/resolve (y enqueue agregado si aplica).
- Tests PHPUnit: enqueue guards, go-live vacío, reingreso con historial, omit, resolve por store/import, permisos (`cursos.view` no ve cola; `cursos.edit` sí), filtro solo activos, no reencolar al borrar último curso.
- Docs: actualizar `docs/modules/cursos.md` + `docs/user/cursos.md` (+ nota cruzada breve en `ficha-empleados` si Documentador lo estima). Documentador al cierre.

### Fuera de alcance

- Inventario/backfill de activos actuales sin curso.
- Encolar por vigencia ACTUALIZAR/VENCIDO o por renovación.
- UI de deshacer omitir / reencolar manual.
- Icono en Dashboard o Catálogo.
- Permiso nuevo / cambios a `config/access.php`.
- Export Excel dedicado de la cola; notificaciones correo.
- Cambiar KPIs/gráficos del dashboard de vigencia.
- Soft-delete de `employee_cursos` o historial de renovaciones.
- Select2 / `excelHtml5` / Repository.

## Reglas de negocio

### Encolado

1. Solo se crea fila `pending` cuando, **después del deploy de esta feature**, una persona **pasa a estar en ficha** vía:
   - Alta manual (`store` sin `ficha_entry_id`).
   - Completar pendiente / «Gestionar Empleado» (`store` con `ficha_entry_id` → `moved_to_ficha_at`).
   - Import masivo de Ficha que crea entrada o setea `moved_to_ficha_at` por primera vez.
2. Condiciones simultáneas para encolar: no hay `employee_cursos` para la cédula; no hay fila `omitted` ni `resolved` previa para esa cédula; no hay `pending` abierta.
3. Empleados ya en ficha el día del deploy **no** aparecen (tabla vacía + sin backfill).
4. Reingreso: si la cédula ya tuvo cursos en el sistema → **no** vuelve a cola aunque vuelva a ficha.

### Cola visible

5. Contador y listado: `status = pending` **y** perfil ficha asociado con `employment_status = activo` (join/`whereHas` por `document_number` o FK).
6. No activos (p. ej. desvinculado) con `pending`: no cuentan; la fila permanece por si reactivan.

### Salida

7. Primer **insert** de `employee_cursos` para esa cédula (UI o import) → `resolved`.
8. Omitir → `omitted`; motivo texto libre opcional (nullable, max razonable p. ej. 500–1000 chars).
9. V1: no hay botón «volver a cola» ni revertir omit.

### UI / permisos

10. Solo pestaña Registros; etiqueta accesible «Nuevos sin curso»; visualmente solo icono + número.
11. Solo `cursos.edit` (+ bypass admin): ver icono, contador, listado, omitir, abrir alta de curso desde la cola.
12. `cursos.view` sin edit: UI de cola **ausente** (403 en rutas de cola si se fuerza URL).
13. Al guardar curso (alta/edición desde cola o listado / import cursos): **nunca** actualizar datos personales del perfil de ficha (`employee_ficha_profiles`). Solo `employee_cursos` (+ resolve de cola).
14. Modal Nuevo/Agregar curso (también desde cola): cédula y nombre **solo lectura** desde ficha (lookup). El usuario **no** los edita (opción **A**, 2026-09-21).

## Permisos (`config/access.php`)

| Permiso | Rol(es) | Descripcion |
| --- | --- | --- |
| `cursos.edit` (existente) | Asignación actual / bypass `manage.users` | Ver cola «Nuevos sin curso», contador, listado, omitir, crear primer curso |
| `cursos.view` (existente) | — | **No** incluye cola |

**Sin permiso nuevo. Sin cambios a `config/access.php`.** Bypass runtime: `CursosAccessService::canEdit`.

## Rutas

Archivo: `routes/areas/gestion_humana.php` (grupo `gestion-humana/cursos`).

| Metodo | URI | Nombre | Archivo de rutas | Auth |
| --- | --- | --- | --- | --- |
| GET | `/registros` (query `cola=nuevos-sin-curso`) | `registros` (existente, extendido) | idem | `canEdit` para modo cola; sin edit → 403 o redirect sin query |
| POST | `/registros/pendientes/{pending}/omitir` | `registros.pendientes.omit` | idem | `cursos.edit` |
| *(opcional)* GET | `/registros/pendientes/count` | `registros.pendientes.count` | idem | `cursos.edit` — solo si se prefiere AJAX; V1 puede pasar contador en `registros()` |

Alternativa aceptable: listado cola en la misma `registros` vía query; el Feature puede omitir endpoint count separado.

No nuevas rutas en `routes/web.php`.

## Base de datos

| Tabla / cambio | Tipo | Notas |
| --- | --- | --- |
| `employee_curso_pending` | migracion create | Ver esquema abajo. **Sin backfill.** |

### Esquema sugerido `employee_curso_pending`

| Columna | Tipo | Notas |
| --- | --- | --- |
| `id` | bigint PK | |
| `document_number` | string(50) index | Cédula; normalizar igual que Ficha/Cursos |
| `full_name` | string(255) nullable | Snapshot al encolar |
| `employee_ficha_profile_id` | FK nullable → `employee_ficha_profiles`, `nullOnDelete` | |
| `personal_requisition_ficha_entry_id` | FK nullable → `personal_requisition_ficha_entries`, `nullOnDelete` | |
| `status` | string(20) | `pending` \| `omitted` \| `resolved` |
| `enqueued_at` | timestamp | |
| `enqueued_by` | FK nullable → `users`, `nullOnDelete` | null si import sistema |
| `omitted_at` | timestamp nullable | |
| `omitted_by` | FK nullable → `users` | |
| `omit_reason` | string(1000) nullable | Motivo opcional |
| `resolved_at` | timestamp nullable | |
| `resolved_by` | FK nullable → `users` | |
| `resolved_via` | string(30) nullable | `first_curso` \| (futuros) |
| `employee_curso_id` | FK nullable → `employee_cursos`, `nullOnDelete` | Primer curso que resolvió |
| `created_at` / `updated_at` | timestamps | |

**Índices / unicidad:**

- Unique parcial lógica: **como máximo una fila `pending` por `document_number`** (índice unique compuesto `(document_number, status)` no basta si hay omitted+resolved; mejor unique solo en app + índice `(document_number, status)` y validación en servicio, **o** unique `document_number` donde `status = pending` vía índice raw MySQL 8 / constraint en servicio).
- Recomendación Feature: índice `(status, document_number)` + en `enqueueIfEligible` `firstOrCreate` / lock + check; unique `document_number` **global no** (permitir historial omitted/resolved y un pending nuevo solo si no hay omitted/resolved — por regla de negocio **nunca** hay pending nuevo tras omitted/resolved).

## Capas a implementar

- [x] Migracion(es) — `employee_curso_pending`
- [x] Modelo(s) — `EmployeeCursoPending` (+ factory)
- [x] Servicio(s) — `EmployeeCursoPendingService`
- [x] Controlador(es) — extender `CursosController`; hooks `FichaEmpleadosController` + import ficha/cursos
- [x] Form Request(s) — p. ej. `OmitEmployeeCursoPendingRequest`
- [x] Vista(s) Blade — `cursos/registros.blade.php` (+ partial listado cola si aplica)
- [x] JavaScript — mínimo (Alpine existente modal nuevo; confirm omit)
- [ ] Export Excel — **no** en V1
- [x] Tests — Feature Cursos + regresión Ficha store/import enqueue

## Componentes reutilizables

- Patrón UI icono+contador de Ficha (`ficha-empleados-filters__pending-link` / clases equivalentes en CSS cursos).
- Modal «Nuevo registro» de Cursos (precarga cédula).
- `<x-searchable-select>` solo si el listado cola aporta filtros nuevos (no obligatorio V1).
- `CursosAccessService`, `CursosAuditLogService`.
- **No** `BaseExport` / `<x-export-excel>` en V1 para esta cola.

## Documentacion a actualizar

- [ ] `docs/modules/cursos.md` — cola, tabla, hooks, permisos UI
- [ ] `docs/user/cursos.md` — qué es «Nuevos sin curso», omitir, quién lo ve
- [ ] `docs/modules/ficha-empleados.md` — nota: al pasar a ficha se puede encolar en Cursos (Documentador)
- [ ] `docs/INDEX.md` — solo si se añade entrada nueva (probable no)
- [ ] `README.md` — no

## Archivos compartidos (`shared-files`)

| Archivo | ¿Se toca? | Notas |
| --- | --- | --- |
| `config/access.php` | **NO** | Sin permiso nuevo |
| `routes/web.php` | **NO** | |
| Layouts globales | **NO** | |
| Seeders de permisos | **NO** | |
| `routes/areas/gestion_humana.php` | **SÍ** | Ruta(s) omit (+ query en registros); ownership GH |
| `FichaEmpleadosController` | **SÍ** | Hook enqueue en `store` — **shared-files parcial** (módulo Ficha) |
| `EmployeeFichaImportService` | **SÍ** | Hook enqueue — mismo flag parcial |
| `config/audit.php` | **NO** (salvo Feature quiera label; módulo `cursos` ya existe) | |

**Flag `shared-files` en TASKS.md:** `parcial` — rutas GH cursos + hooks Ficha (`FichaEmpleadosController`, `EmployeeFichaImportService`). **No** `access.php`.

AgentSj debe secuenciar: migracion/servicio/UI Cursos primero; hooks Ficha en la misma Task Card o Task 2 inmediata con flag compartido, para no dejar enqueue huérfano.

## Criterios de aceptacion

1. Tras deploy + migrate, la cola está **vacía** aunque existan activos sin cursos en BD.
2. Al completar «Gestionar Empleado» / alta manual / import ficha que mete a ficha una cédula **sin** historial de cursos y **sin** omit previo → aparece en cola (si `activo`).
3. Si la cédula ya tiene ≥1 `employee_cursos` → **no** se encola (reingreso).
4. Contador e icono solo en **Registros**; tooltip «Nuevos sin curso»; solo icono + número visibles.
5. Usuario solo `cursos.view`: no ve icono/contador; POST omit → 403.
6. Usuario `cursos.edit`: ve contador, abre listado, puede omitir (motivo opcional) y crear primer curso desde la fila.
7. Al crear el primer curso (UI o import insert) la persona **sale** de la cola (`resolved`).
8. Al omitir, sale y **no** reaparece en V1 (incluso si sigue activo sin cursos).
9. Desvincular un `pending` lo quita del contador; no lo borra de BD.
10. Actualizar/renovar un curso existente o estados ACTUALIZAR/VENCIDO **no** generan filas nuevas en la cola.
11. Borrar el último curso de una cédula **no** la reencola.
12. Dashboard / Catálogo sin cambios de icono de esta cola.
13. Al guardar un curso (UI o import de cursos) **no** se modifica el perfil de ficha (nombre, documento u otros campos personales); solo datos de curso y estado de cola.
14. En el modal Nuevo/Agregar (incl. desde cola), cédula y nombre son **solo lectura** precargados desde ficha.
15. `php artisan test` de los tests nuevos/afectados en verde; solo `migrate` incremental.

## Validacion local

1. `php artisan migrate` (sin fresh).
2. Login con usuario `cursos.edit`: Registros → icono 0; promover un pendiente de ficha sin cursos → contador 1; omitir / crear curso → 0.
3. Login solo `cursos.view`: sin icono; URL forzada de omit → 403.
4. Reingreso: cédula con cursos previos → al volver a ficha no encola.
5. `php artisan test --compact` filtros Feature Cursos + Ficha store/import relacionados.

## Riesgos y dependencias

| Riesgo | Mitigacion |
| --- | --- |
| Hook Ficha olvidado en un camino de ingreso | Checklist: store manual, store `ficha_entry_id`, import ficha (`resolveFichaEntry` / create). Tests por camino. |
| Import ficha masivo encola stock si se re-importan filas ya en ficha | Solo enqueue cuando **transiciona** a ficha (`moved_to_ficha_at` era null → now) o alta nueva; no en cada update de perfil existente. |
| Contador desfasado vs listado | Misma query servicio (`count` = `list` count / same scope). |
| Confusión con estado trámite `PENDIENTE` | Etiqueta fija «Nuevos sin curso»; no usar la palabra «Pendientes» sola en UI. |
| Pedido posterior de inventario histórico | Fuera V1; requeriría comando backfill **explícito** y OK usuario (no en esta feature). |
| Deshacer omit | Fuera V1; queda rastro audit + filas `omitted`. |
| Carrera doble enqueue en import paralelo | Idempotencia en servicio + índice/unique pending por cédula. |

**Dependencias:** módulo Cursos (FEAT-032+) y Ficha empleados estables; no depende de FEAT-033.

## Task Cards sugeridas (para AgentSj)

| Task | Scope vertical | Notas |
| --- | --- | --- |
| **T1** | Migración + modelo + `EmployeeCursoPendingService` + resolve hooks en `CursosController::store` + `EmployeeCursoImportService` + UI Registros (icono, contador, listado query, omit) + Form Request + tests cola/cursos | Shared-files: rutas GH |
| **T2** | Hooks enqueue en `FichaEmpleadosController::store` + `EmployeeFichaImportService` + tests Ficha | **shared-files parcial** Ficha |
| **T3** *(opcional si T1 grande)* | Pulido UI/CSS + audit events + edge cases desvinculado | Puede fusionarse en T1 |

Revisor tras T1+T2. Documentador al cierre.

## Aprobacion

- [x] Analista — vacíos cerrados (`FEAT-034-analyst.md` + respuestas 2026-09-21)
- [x] Arquitecto — brief final (`docs/briefs/FEAT-034.md`)
- [x] Usuario — confirmación de las 5 preguntas de negocio

---

## Handoff a AgentSj

1. Actualizar `docs/TASKS.md`: Brief → `docs/briefs/FEAT-034.md`; fase → plan orquestación; `shared-files` = **parcial** (rutas GH + hooks Ficha; **no** `access.php`).
2. Generar `docs/briefs/FEAT-034-plan.md` (ORCHESTRATION_PLAN) con T1/T2.
3. Lanzar Agente Feature T1 → T2 → Revisor → Documentador.
)
