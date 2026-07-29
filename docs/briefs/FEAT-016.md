# Feature Brief — FEAT-016

> Versión final (Arquitecto). Decisiones de negocio cerradas 2026-07-29 (ver [`FEAT-016-analyst.md`](FEAT-016-analyst.md) y run log).

## Identificacion

| Campo | Valor |
| --- | --- |
| ID | FEAT-016 |
| Modulo / area | **Comercial** — matriz clientes, tablero **Servicios** (`comercial.matriz.services.*`) |
| Titulo | Listado servicios: orden de columnas y vigencia por contrato (sin mezcla documental) |
| Solicitante | Manuel (via `@agent-sj`) |
| Fecha | 2026-07-29 |

## Objetivo

Unificar el tablero **`/comercial/servicios`** con el criterio operativo del **contrato del servicio** (fecha fin, baja lógica e ventana fija de 30 días), reordenar columnas según operación MT-CO-01, y **dejar de mezclar** en esa pantalla la vigencia de **documentación del cliente** (ya cubierta en checklist documental y FEAT-015).

**Para quien:** usuarios Comercial con `comercial.matriz.view` / `manage` que filtran, exportan y dan de baja servicios.

**Resultado visible:** columna **Vigencia** con cuatro estados (**Inactivo**, **Vencido**, **Por vencer**, **Activo**); filtros GET `vigencia=expiring|expired` alineados a contrato; export Excel con **mismo orden y etiquetas** que la tabla.

**Dependencias:** matriz comercial existente (`commercial_services`, acción Inactivar, export `BaseExport`); FEAT-014/015 **no se modifican** en sus reglas documentales.

## Alcance

### Incluye (v1)

1. **Migración Opción A — `is_active`:** columna boolean `is_active` en `commercial_services`, default `true`; backfill `is_active = false` donde `portfolio = 'inactivos'`; reconciliar filas inconsistentes en la misma migración (cualquier `portfolio = inactivos` queda `is_active = false`).
2. **Modelo `CommercialService`:** `is_active` en `$fillable` y cast boolean; método centralizado de etiqueta de vigencia **solo contrato** (nombre orientativo: `contractVigenciaLabel(?Carbon $asOf = null): string` y/o constantes de estado); **prioridad de evaluación:** Inactivo → Vencido → Por vencer → Activo.
3. **`scopeFilterByVigencia`:** solo fechas de `contract_end`; **sin** `orWhereHas` documentación del cliente; mantener query params `expiring` y `expired`; ventana «por vencer» = 30 días calendario inclusive (parámetro `$days` default 30).
4. **Filtros inactivos en listado:** servicios con `is_active = false` **excluidos** de `expired` y `expiring` (coherente con comportamiento actual que excluía portafolio inactivos).
5. **Vista** `resources/views/areas/comercial/matriz-clientes/services/index.blade.php`: orden columnas acordado; encabezado «Tipo servicio»; pills/badges para los cuatro estados; **eliminar** pills ≤30 / ≤60 días y cualquier rama UI que use documentación del cliente en vigencia; actualizar copy del select `vigencia` (sin «≤30 días» mezclado con doc).
6. **Export** `CommercialServiceController::exportExcel`: columnas en orden **idéntico** a la tabla; celdas de vigencia = **texto** de las mismas etiquetas (no ≤30/≤60).
7. **Acción Inactivar:** además de `portfolio = inactivos`, persistir `is_active = false` (doble escritura; portafolio Inactivos se mantiene para import, KPI por hoja y filtros portafolio).
8. **Tests** en `tests/Feature/CommercialMatrixTest` (y/o helper testable): vigencia, filtros contract-only, export orden/etiquetas, backfill/migración si se prueba vía factory post-migrate; **reescribir o eliminar** tests que exigen mezcla documental en servicios.
9. **Documentación** (Documentador): `docs/modules/matriz-clientes.md` y `docs/user/matriz-clientes.md` — vigencia y filtros del tablero Servicios = solo contrato + `is_active`.

### Fuera de alcance (v1)

- Checklist documental, job FEAT-015, formulas `CommercialClient::isDocumentationExpiringSoon` / scopes documentales.
- Cambios en **ficha cliente** (`clients/show` — tabla servicios con UI ≤30/≤60 y criterio documental): **sin cambios** en v1; deuda de alineación en ticket futuro.
- **Dashboard** KPIs (`CommercialDashboardController`) que usan `isExpiringSoon` / `isExpired` con mezcla documental: **sin cambios** en v1.
- Rutas nuevas, permisos Spatie nuevos, import MT-CO-01, flujo «reactivar» servicio.
- Sincronización automática `is_active` ↔ portafolio en **edición manual** del formulario (salvo validación mínima documentada en reglas — ver edge cases).

### Orden de columnas (tabla y export)

| # | Columna |
| --- | --- |
| 1 | NIT |
| 2 | Cliente |
| 3 | Contrato |
| 4 | Tipo servicio |
| 5 | Portafolio |
| 6 | Asesor |
| 7 | Inicio (`contract_start`) |
| 8 | Fin (`contract_end`) |
| 9 | Vigencia |
| 10 | Acciones *(solo tabla; no en Excel)* |

## Reglas de negocio

### Calendario y timezone

1. **«Hoy»** = `now()->startOfDay()` en `config('app.timezone')` (mismo criterio que el resto de matriz comercial).
2. Comparaciones de `contract_end` usan **días calendario** (fecha almacenada como date; normalizar a inicio de día).

### Etiqueta Vigencia (contrato + inactivo)

Evaluar en **este orden** (primera condición que aplique gana):

| Prioridad | Estado | Condición |
| --- | --- | --- |
| 1 | **Inactivo** | `is_active === false` |
| 2 | **Vencido** | `contract_end` not null **y** `contract_end` &lt; hoy |
| 3 | **Por vencer** | `contract_end` not null **y** `contract_end` ≥ hoy **y** `contract_end` ≤ hoy + 30 días |
| 4 | **Activo** | `contract_end` &gt; hoy + 30 días **o** `contract_end` null (si no Inactivo) |

3. Servicio inactivo con contrato vencido muestra **Inactivo**, no Vencido.
4. La columna Vigencia en servicios **no** consulta `documentation_expires_on` ni métodos documentales del cliente.

### Filtro GET `vigencia`

| Valor | Comportamiento deseado |
| --- | --- |
| *(vacío)* | Sin filtro de vigencia |
| `expired` | `contract_end` not null y &lt; hoy; **excluir** `is_active = false` |
| `expiring` | `contract_end` en [hoy, hoy + 30] inclusive; **excluir** `is_active = false`; **sin** rama documental |

5. Copy UI del filtro debe describir ventana de contrato (p. ej. «Por vencer (contrato)» / «Vencido (contrato)») sin referencia a documentación del cliente.

### Inactivar servicio

6. `POST …/inactivar` establece `portfolio = inactivos` **y** `is_active = false`.
7. Columna **Portafolio** sigue mostrando «Inactivos»; filtro por portafolio inactivos sigue incluyendo esos registros.

### `is_active` — Opción A (adoptada)

8. Fuente de verdad para etiqueta **Inactivo** en vigencia: **`is_active === false`** (tras backfill, alineado con histórico `portfolio = inactivos`).
9. No sustituir portafolio `inactivos` por solo `is_active` (preserva MT-CO-01, import, dashboard `inactiveServices` por portafolio).

### Edge cases (implementación)

10. Post-migración: todo `portfolio = inactivos` debe quedar `is_active = false`; si queda `is_active = true` con portafolio inactivos, la migración lo corrige.
11. **v1:** editar servicio con `manage` y cambiar portafolio fuera de «Inactivos» **no** reactiva automáticamente (`is_active` no se pone `true` salvo flujo explícito futuro); documentar en doc técnica. Inactivar sigue siendo la vía oficial de baja.

### Deprecación / convivencia

12. `isExpiringSoon` / `isExpired` en el modelo pueden seguir usándose por **dashboard y ficha cliente** en v1 con comportamiento legacy (mezcla documental); el tablero Servicios y export FEAT-016 usan **solo** `contractVigenciaLabel` (o equivalente) y scope contract-only. No refactorizar esos métodos en v1 salvo lo mínimo para no romper otros call sites.

## Permisos (`config/access.php`)

| Permiso | Uso en FEAT-016 |
| --- | --- |
| `comercial.matriz.view` | Ver listado servicios y exportar |
| `comercial.matriz.manage` | Crear/editar/inactivar (acción ya existente; ahora también escribe `is_active`) |

**Registro:** **no** añadir permisos ni entradas sidebar.

## Rutas

Sin rutas nuevas. Rutas existentes (middleware `password.changed` + permisos del controlador):

| Metodo | URI | Nombre | Controlador | Notas FEAT-016 |
| --- | --- | --- | --- | --- |
| GET | `/comercial/servicios` | `comercial.matriz.services.index` | `CommercialServiceController@index` | Columnas, vigencia UI, filtro copy |
| GET | `/comercial/servicios/exportar` | `comercial.matriz.services.export` | `CommercialServiceController@exportExcel` | Orden columnas + etiquetas vigencia |
| POST | `/comercial/servicios/{service}/inactivar` | `comercial.matriz.services.inactivate` | `CommercialServiceController@inactivate` | `is_active = false` + portafolio inactivos |

**Query params existentes** en index/export (sin renombrar en v1): `vigencia`, `portfolio`, búsqueda, etc. — solo cambia **semántica** de `vigencia`.

## Base de datos

### Migracion: `is_active` en `commercial_services`

| Columna | Tipo | Notas |
| --- | --- | --- |
| `is_active` | boolean, default `true`, not null | Tras crear columna: `UPDATE … SET is_active = 0 WHERE portfolio = 'inactivos'` |

**Nombre sugerido:** `YYYY_MM_DD_HHMMSS_add_is_active_to_commercial_services_table.php`

Índice: no obligatorio en v1 (filtros vigencia ya acotados por fechas).

## Capas a implementar

- [ ] Migración — `is_active` + backfill
- [ ] Modelo — cast/fillable; `contractVigenciaLabel()` (+ constantes opcionales); `scopeFilterByVigencia` contract-only
- [ ] Controlador — `exportExcel` columnas/labels; `inactivate` doble write; `filteredServicesQuery` sin cambio de firma
- [ ] Vista — `services/index.blade.php` (orden, badges, filtro, quitar ≤60 en **esta** tabla)
- [ ] CSS — reutilizar clases pill existentes; añadir en `app.css` solo si faltan variantes **Activo** / **Por vencer** / **Inactivo**
- [ ] Tests — `CommercialMatrixTest` (+ migración/backfill si aplica)
- [ ] Documentacion — modulo + usuario (Documentador)

**Shared-files:** **false** (no tocar `config/access.php`, `routes/web.php` layout global).

## Tareas de implementacion (vertical slice — 1 agente Feature)

### FEAT-016-T1 — Servicios: columnas, vigencia contrato, export, `is_active`

1. Crear y ejecutar migración `is_active` con backfill.
2. Implementar `contractVigenciaLabel()` (y helpers privados si hace falta) con `$asOf` opcional para tests.
3. Refactorizar `scopeFilterByVigencia` a solo contrato + exclusión `is_active = false`.
4. Actualizar vista index (orden columnas, cuatro badges, textos filtro; **sin** pill ≤60 ni ≤30 legacy en tabla servicios).
5. Alinear `exportExcel` con orden de columnas de tabla y strings de vigencia del helper.
6. Actualizar `inactivate()` para `is_active = false`.
7. PHPUnit: casos analista; eliminar/reescribir tests documentales en servicios; `Carbon::setTestNow()` en bordes.
8. `vendor/bin/pint --dirty` en PHP tocado.
9. Handoff Revisor → Documentador.

**No dividir** Backend/Frontend; **no** tocar `clients/show` ni `CommercialDashboardController` en este slice.

## Criterios de aceptacion

1. Tabla servicios muestra columnas en orden: NIT, Cliente, Contrato, Tipo servicio, Portafolio, Asesor, Inicio, Fin, Vigencia, Acciones.
2. Vigencia muestra exactamente uno de: **Inactivo**, **Vencido**, **Por vencer**, **Activo** según reglas de negocio; **no** aparecen pills ≤30 días ni ≤60 días en el listado servicios.
3. Servicio con `contract_end` null y `is_active = true` → **Activo**.
4. Servicio con `is_active = false` y contrato vencido → **Inactivo**.
5. Filtro `vigencia=expiring` lista solo servicios activos con fin de contrato en [hoy, hoy+30]; cliente con documentación vencida pero contrato futuro **no** aparece.
6. Filtro `vigencia=expired` lista solo contrato vencido; **no** por documentación del cliente; inactivos excluidos.
7. Export con mismos filtros que el listado: orden NIT → … → Vigencia (sin columna Acciones); valores vigencia = mismas etiquetas que la UI.
8. Inactivar servicio deja `portfolio = inactivos` y `is_active = false`; vigencia en listado **Inactivo**.
9. Migración: registros históricos `portfolio = inactivos` quedan con `is_active = false`.
10. Tests FEAT-016 en verde; tests que exigían documentación en vigencia de servicios actualizados o retirados.
11. Dashboard y ficha cliente pueden seguir mostrando criterio anterior — **aceptado en v1** (deuda documentada).

## Validacion local

1. `php artisan migrate`
2. Usuario con `comercial.matriz.view`: abrir `/comercial/servicios`, verificar orden y badges.
3. Probar filtros `vigencia=expiring` y `expired` con datos de contrato (sin depender de doc cliente).
4. Exportar y comparar orden/etiquetas con pantalla.
5. Inactivar un servicio activo; verificar portafolio, `is_active` y badge Inactivo.
6. `php artisan test --compact tests/Feature/CommercialMatrixTest.php` (o filtro FEAT-016).
7. `vendor/bin/pint --dirty` en PHP modificado.

## Tests (minimos)

Archivo principal: `tests/Feature/CommercialMatrixTest.php`.

| Test (nombre orientativo) | Intencion |
| --- | --- |
| `test_contract_vigencia_label_active_when_end_null` | Fin null, activo → Activo |
| `test_contract_vigencia_label_expired` | Fin ayer → Vencido |
| `test_contract_vigencia_label_expiring_today_and_plus_30` | Fin hoy y hoy+30 → Por vencer |
| `test_contract_vigencia_label_active_beyond_30_days` | Fin hoy+31 → Activo |
| `test_inactive_wins_over_expired_contract` | `is_active = false` + fin pasado → Inactivo |
| `test_services_vigencia_filter_expiring_contract_only` | Doc vencida, contrato en ventana → incluido; doc sola → no |
| `test_services_vigencia_filter_expired_contract_only` | Doc vencida, contrato futuro → no listado |
| `test_services_export_column_order_and_vigencia_labels` | GET export; assert orden headers/celdas y strings vigencia |
| `test_inactivate_sets_is_active_and_portfolio` | POST inactivar → DB |
| `test_migration_backfill_inactivos` | *(opcional)* portfolio inactivos → is_active 0 |

**Retirar o reescribir:**

- `test_is_expired_true_when_client_documentation_expired_even_if_contract_ok`
- `test_services_vigencia_filter_uses_client_documentation`

Usar `Carbon::setTestNow()` y, si el helper acepta `$asOf`, fijar bordes de fecha.

## Documentacion a actualizar

- [ ] `docs/modules/matriz-clientes.md` — listado servicios y filtros `vigencia`: **solo contrato** + `is_active`; orden columnas; export alineado; mencionar deuda dashboard/ficha cliente.
- [ ] `docs/user/matriz-clientes.md` — significado de Inactivo / Vencido / Por vencer / Activo en tablero Servicios; filtros; export.
- [ ] `docs/INDEX.md` — solo si falta referencia cruzada.

## Riesgos y dependencias

| Riesgo | Mitigacion |
| --- | --- |
| Dos semánticas vigencia (servicios vs dashboard/ficha) | Documentar deuda v1; helper reutilizable en ticket follow-up |
| Edición manual portafolio vs `is_active` | Regla v1 documentada; no auto-reactivar |
| `isExpiringSoon(60)` en otras pantallas | FEAT-016 solo quita ≤60 en **tabla servicios** |
| Regresión import MT-CO-01 | Mantener portafolio inactivos en inactivar |

## Deuda documentada (post-v1)

- Alinear vigencia en `clients/show` (tabla servicios) y KPIs dashboard al mismo helper contract-only.
- Flujo explícito de reactivación de servicio (`is_active = true`).

## Integracion FEAT-014 / FEAT-015

| Feature | Contrato |
| --- | --- |
| FEAT-014 | Checklist y scopes documentales del **cliente** sin cambios |
| FEAT-015 | Digest documental independiente; no usar doc cliente en vigencia de **servicios** tras FEAT-016 |

## Aprobacion

- [x] Analista — [`FEAT-016-analyst.md`](FEAT-016-analyst.md)
- [x] Usuario — reglas vigencia, orden columnas, Opción A `is_active` (2026-07-29)
- [x] Arquitecto — brief final
- [ ] Usuario — confirmacion explicita del brief
- [ ] AgentSj — Task Card en `docs/TASKS.md` y orquestacion Feature
