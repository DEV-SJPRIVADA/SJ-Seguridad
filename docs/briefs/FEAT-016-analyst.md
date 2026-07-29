# Analista — FEAT-016

> Salida del Agente Analista antes del Feature Brief final (`docs/briefs/FEAT-016.md`). Sin implementación de código.

## Contexto recibido

| Campo | Valor |
| --- | --- |
| **Feature ID** | FEAT-016 |
| **Origen** | Manuel, Comercial — `@agent-sj` (2026-07-29) |
| **Pantalla** | `/comercial/servicios` — `resources/views/areas/comercial/matriz-clientes/services/index.blade.php` |
| **Módulo** | matriz-clientes (área Comercial) |
| **Run log** | [`docs/runs/FEAT-016-run-log.md`](../runs/FEAT-016-run-log.md) |

**Decisiones ya confirmadas por el usuario (AgentSj):**

| Tema | Regla |
| --- | --- |
| Orden columnas | NIT, Cliente, Contrato, Tipo servicio, Portafolio, Asesor, Inicio, Fin, Vigencia, Acciones |
| **Inactivo** | Prioridad 1; criterio deseado: `commercial_services.is_active = 0` (**columna no existe hoy**) |
| **Vencido** | `contract_end` &lt; hoy (días calendario, `startOfDay`, timezone app) |
| **Por vencer** | `contract_end` ≥ hoy **y** `contract_end` ≤ hoy + 30 días calendario |
| **Activo** | `contract_end` &gt; hoy + 30 días **o** `contract_end` null (salvo Inactivo) |
| Quitar UI actual | Pills ≤30 / ≤60 días y lógica mezclada con documentación del cliente |
| Filtro `vigencia` | Alinear con reglas **solo contrato** si aplica |
| Export Excel | Revisar orden de columnas y etiquetas de vigencia |

---

## Alcance (propuesta analista)

### Dentro de alcance (v1)

1. **Tabla listado servicios:** reordenar columnas según lista acordada; renombrar encabezado «Tipo» → «Tipo servicio» (coherente con export).
2. **Columna Vigencia:** cuatro estados discretos con pills/badges: **Inactivo**, **Vencido**, **Por vencer**, **Activo** (prioridad en ese orden al evaluar).
3. **Modelo / dominio:** método centralizado en `CommercialService` para **etiqueta de vigencia por contrato** (y scopes o ajuste de `scopeFilterByVigencia` para filtros `expiring` / `expired` **sin** `documentationExpiring` / `documentationExpired` del cliente).
4. **Export Excel** (`CommercialServiceController::exportExcel`): mismo orden de columnas que la tabla y mismas etiquetas de vigencia que la UI.
5. **Etiquetas del filtro GET `vigencia`:** actualizar copy (p. ej. quitar «≤30 días» mezclado con doc); mantener valores `expiring` y `expired` salvo que Arquitecto proponga renombrar query params (no solicitado).
6. **Tests** en `CommercialMatrixTest`: vigencia por contrato, filtros, export (mínimo); retirar o reescribir tests que exigen mezcla documental en servicios.
7. **Documentación** (entrega Documentador): `docs/modules/matriz-clientes.md` — hoy dice que filtros de servicios consideran contrato **o** documentación del cliente; debe reflejar solo contrato tras FEAT-016.

### Fuera de alcance (v1 — salvo decisión explícita)

- Checklist documental por cliente (`/comercial/clientes/checklist-documental`) y job FEAT-015: **sin cambios**; la vigencia documental sigue ahí.
- Cambiar permisos, rutas nuevas, import MT-CO-01.
- Historial / reactivar servicios (no hay «activar» inverso hoy).

### Alineación recomendada (no bloqueante — preguntas opcionales)

| Superficie | Situación actual | Riesgo si no se toca en v1 |
| --- | --- | --- |
| Ficha cliente — tabla servicios (`clients/show.blade.php`) | Misma UI ≤30/≤60 + `isExpired`/`isExpiringSoon` documental | Usuario ve **dos criterios distintos** en Servicios vs ficha cliente |
| Dashboard KPIs (`CommercialDashboardController`) | `isExpiringSoon(30)` / `isExpired` con mezcla documental | KPIs «por vencer» / «vencidos» **no coinciden** con el listado de servicios |
| Métodos `isExpiringSoon` / `isExpired` en modelo | Usados por dashboard, export (hoy), tests | Refactor parcial deja comportamiento **dual** |

**Recomendación analista:** en la misma entrega, reutilizar el **mismo helper de etiqueta** en export + index; para dashboard y ficha cliente, o bien **misma regla contrato** (scope ampliado FEAT-016) o ticket de seguimiento documentado en el brief final.

---

## Estado actual vs deseado

### Columnas (tabla)

| # deseado | Columna | Orden actual (`services/index`) |
| --- | --- | --- |
| 1 | NIT | 2 |
| 2 | Cliente | 1 |
| 3 | Contrato | 4 |
| 4 | Tipo servicio | 5 («Tipo») |
| 5 | Portafolio | 3 |
| 6 | Asesor | 6 |
| 7 | Inicio | 7 |
| 8 | Fin | 8 |
| 9 | Vigencia | 9 |
| 10 | Acciones | 10 |

**Export actual** (`exportExcel`): Cliente, NIT, Portafolio, Contrato, Tipo servicio, Asesor, Inicio, Fin, Vigencia — mismo desorden respecto al deseado.

### Vigencia — lógica

| Aspecto | **Actual** | **Deseado** |
| --- | --- | --- |
| UI listado | Vencido; ≤30 días; ≤60 días; «—» | Inactivo; Vencido; Por vencer; Activo |
| Fuente de verdad | `contract_end` **+** `CommercialClient::isDocumentationExpiringSoon` / `isDocumentationExpired` vía `isExpiringSoon` / `isExpired` | **Solo** `contract_end` (+ flag inactivo); ventana fija **30** días para «Por vencer» |
| Servicios en portafolio `inactivos` | `isExpiringSoon` / `isExpired` retornan **false** → columna «—» | Etiqueta **Inactivo** (prioridad sobre fechas de contrato) |
| `contract_end` null | «—» si no hay señal documental | **Activo** (si no inactivo) |
| Acción Inactivar | `POST …/inactivar` → `portfolio = inactivos` | Usuario pide **`is_active = 0`**; acción hoy **no** escribe esa columna |

**Regla de evaluación deseada (pseudocódigo):**

```
if inactivo:     return 'Inactivo'
if end < today:  return 'Vencido'
if end >= today && end <= today+30: return 'Por vencer'
return 'Activo'  // incluye end > today+30 y end === null
```

Calendario: `today = now()->startOfDay()` (timezone `config('app.timezone')`).

### Filtro `vigencia` (controlador + `scopeFilterByVigencia`)

| Valor | **Actual** | **Deseado** |
| --- | --- | --- |
| `expired` | Contrato vencido **OR** cliente con documentación vencida; excluye `portfolio = inactivos` | Contrato vencido (`contract_end` not null y &lt; hoy); definir si servicios **Inactivo** entran o no (recomendación: **excluir**, coherente con hoy) |
| `expiring` | Contrato en [hoy, hoy+30] **OR** cliente `documentationExpiring`; excluye inactivos por portafolio | Contrato en [hoy, hoy+30] inclusive; **sin** rama documental |

**Nota:** el scope actual usa `$days = 30` por defecto en `filterByVigencia` — alineado con negocio para «Por vencer» en listado.

### Documentación viva (`docs/modules/matriz-clientes.md`)

- Línea ~20–29: listado servicios y filtros mezclan contrato y documentación del cliente → **desactualizado** respecto a FEAT-014 (checklist en cliente) y a esta feature (servicios = solo contrato).

### Referencias código

- Vista: `resources/views/areas/comercial/matriz-clientes/services/index.blade.php` (líneas ~177–216 vigencia; ~45–49 filtro).
- Controlador: `app/Http/Controllers/Comercial/CommercialServiceController.php` — `exportExcel`, `filteredServicesQuery` → `filterByVigencia`.
- Modelo: `app/Models/CommercialService.php` — `isExpiringSoon`, `isExpired`, `scopeFilterByVigencia`.
- Tests: `tests/Feature/CommercialMatrixTest.php` — `test_is_expired_true_when_client_documentation_expired_even_if_contract_ok`, `test_services_vigencia_filter_uses_client_documentation`.
- Schema: `commercial_services` en `2026_07_14_105904_create_commercial_matrix_tables.php` — **no** hay `is_active`.

---

## Dependencia `is_active` — recomendación analista

El usuario confirmó **Inactivo = `is_active = 0`**, pero el sistema hoy modela baja operativa moviendo **`portfolio` a `inactivos`** (`CommercialServiceController::inactivate`).

| Opción | Descripción | Pros | Contras |
| --- | --- | --- | --- |
| **A — Migración + doble write (recomendada)** | Migración: `is_active` boolean default `1`; backfill `is_active = 0` donde `portfolio = 'inactivos'`; `inactivate()` pone `is_active = 0` **y** mantiene `portfolio = inactivos` (comportamiento Excel/import/reportes por hoja INACTIVOS) | Alineado con usuario; datos históricos coherentes; KPI «inactivos» por portafolio sigue funcionando | Migración + actualizar fillable/casts; editar servicio podría reactivar si no se valida |
| **B — Solo mapeo portafolio (sin migración)** | **Inactivo** ⇔ `portfolio === inactivos` | Cero BD; coherente con acción actual | **No** cumple literalmente `is_active`; deuda si luego se separa «portafolio comercial» de «baja lógica» |
| **C — Migración sustituye portafolio** | `is_active` solo; inactivar ya no mueve portafolio | Modelo más puro | Rompe convención MT-CO-01, import, filtros por portafolio, orden «inactivos al final» |

**Recomendación para Arquitecto / Feature:** **Opción A**. La etiqueta **Inactivo** en vigencia debe evaluar **`is_active === false`** (o equivalente), con backfill desde `inactivos`. Mantener movimiento de portafolio en inactivar evita regresiones en dashboard (`inactiveServices`), import y filtro portafolio.

**Edge cases a diseñar:**

- Servicio con `is_active = 1` pero `portfolio = inactivos` (datos inconsistentes post-migración): vigencia **Inactivo** si `is_active = 0` **OR** regla de reconciliación en migración.
- Servicio inactivo con `contract_end` en el pasado: UI **Inactivo**, no **Vencido** (prioridad confirmada).
- Edición manual: usuario con `manage` puede cambiar portafolio desde formulario — definir si debe sincronizar `is_active` o bloquear salida de `inactivos` sin flujo explícito.

---

## Preguntas abiertas (solo las que siguen sin cerrar)

1. **(Opcional — consistencia UI)** ¿En la **misma entrega** se actualizan vigencia y filtros en **ficha cliente** (`clients/show`) y KPIs del **dashboard** para usar la misma regla solo-contrato, o solo el tablero **Servicios**? *(No bloquea diseño del helper; sí el tamaño del slice.)*
solo servicios
2. **(Opcional — post Opción A)** Tras agregar `is_active`, ¿un servicio inactivo debe **seguir** apareciendo bajo portafolio «Inactivos» en columna Portafolio y en filtro portafolio? *(Recomendación: **sí**, mantener status quo.)*
hay que separarlo de inactive del portafolio, ya que inactivos no es un portafolio, is_inactive debe aparecer inactivo si el usuario lo ha inactivado con el boton inactivar de la vista servicios y debe aparecer boton activar por si el usuario requiere activarlo de nuevo.

Si no hay respuesta: **v1 = solo `/comercial/servicios` + export + modelo/filtro**; ficha cliente y dashboard quedan como deuda documentada en FEAT-016.

---

## Ideas de prueba (PHPUnit)

| # | Escenario | Expectativa |
| --- | --- | --- |
| 1 | Servicio activo, `contract_end` null | Etiqueta **Activo** (vista o helper unitario) |
| 2 | `contract_end` = ayer | **Vencido** |
| 3 | `contract_end` = hoy | **Por vencer** (≥ hoy) |
| 4 | `contract_end` = hoy + 30 | **Por vencer** (≤ límite inclusive) |
| 5 | `contract_end` = hoy + 31 | **Activo** |
| 6 | Inactivo (`is_active = 0` o portafolio `inactivos` según decisión) + contrato vencido | **Inactivo** gana |
| 7 | GET `vigencia=expiring` — contrato en ventana, doc cliente vencida | Aparece **solo** por contrato; **no** por documentación |
| 8 | GET `vigencia=expired` — doc vencida, contrato futuro | **No** listado |
| 9 | Export con mismos filtros | Orden columnas NIT→…→Vigencia; celdas vigencia = etiquetas texto (no ≤30/≤60) |
| 10 | Migración backfill (si Opción A) | Registros `portfolio = inactivos` → `is_active = 0` |

**Tests a retirar o reescribir:** `test_is_expired_true_when_client_documentation_expired_even_if_contract_ok`, `test_services_vigencia_filter_uses_client_documentation`.

**Herramienta:** `Carbon::setTestNow()` / parámetro `$asOf` en helper para bordes de fecha.

---

## Archivos tocados (estimación Feature)

| Archivo | Cambio |
| --- | --- |
| `resources/views/areas/comercial/matriz-clientes/services/index.blade.php` | Orden columnas, vigencia UI, textos filtro |
| `app/Http/Controllers/Comercial/CommercialServiceController.php` | Columnas export, posible uso de helper vigencia |
| `app/Models/CommercialService.php` | Helper etiqueta; `scopeFilterByVigencia` contract-only; posible `is_active` cast/fillable |
| `database/migrations/*_add_is_active_to_commercial_services.php` | **Si Opción A** |
| `app/Http/Controllers/Comercial/CommercialServiceController.php` (`inactivate`) | Set `is_active` |
| `tests/Feature/CommercialMatrixTest.php` | Nuevos casos; eliminar dependencia doc en servicios |
| `docs/modules/matriz-clientes.md` | Documentador — filtros/vigencia servicios |
| `docs/user/matriz-clientes.md` | Documentador — guía usuario tablero Servicios |

**Posibles (si scope ampliado):**

- `resources/views/areas/comercial/matriz-clientes/clients/show.blade.php`
- `app/Http/Controllers/Comercial/CommercialDashboardController.php`
- `resources/css/app.css` — clases pill para **Activo** / **Por vencer** / **Inactivo** si no se reutilizan existentes

**Shared-files:** layout no; `config/access.php` no.

---

## Entendimiento del analista (resumen ejecutivo)

Comercial quiere que el tablero **Servicios** refleje el **estado operativo del contrato** (incluida baja lógica e ventana fija de 30 días), con columnas en el orden del MT-CO-01 operativo, y dejar de mezclar en esa columna la **documentación del cliente** (ya cubierta en checklist y FEAT-015). El gap principal de diseño es **`is_active` vs portafolio `inactivos`**: se recomienda migración ligera con backfill y doble escritura en inactivar. El trabajo toca vista, export, modelo/filtros y tests; dashboard y ficha cliente deberían alinearse pronto para no duplicar semánticas.

---

## Estado

- [x] Análisis código y documentación
- [x] Reglas de vigencia y columnas confirmadas por usuario (run log)
- [ ] Preguntas opcionales de alcance (dashboard / ficha cliente) — **cerrado v1: solo servicios**
- [x] Brief final Arquitecto — [`FEAT-016.md`](FEAT-016.md)
